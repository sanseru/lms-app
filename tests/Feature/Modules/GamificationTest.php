<?php

use Modules\Classes\Models\ClassRoom;
use Modules\Forum\Models\Question;
use Modules\Gamification\Actions\Achievements;
use Modules\Gamification\Actions\SyncXp;
use Modules\Gamification\Models\XpEvent;
use Modules\Gamification\Support\Levels;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LessonProgress;

function xpSetup(): array
{
    $teacher = userWithRole('teacher');
    [$a, $b] = [userWithRole('student'), userWithRole('student')];
    $course = Course::create(['title' => 'Python', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lessons = collect(range(1, 3))->map(fn ($i) => $course->lessons()->create(['title' => "L{$i}", 'type' => 'material', 'position' => $i, 'is_published' => true]));
    $class = ClassRoom::create([
        'code' => 'XP-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Python 1', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach([$a->id, $b->id]);

    return compact('teacher', 'a', 'b', 'class', 'course', 'lessons');
}

test('levels follow the threshold curve', function () {
    expect(Levels::for(0))->level->toBe(1)->to->toBe(100)
        ->and(Levels::for(99)['level'])->toBe(1)
        ->and(Levels::for(100)['level'])->toBe(2)
        ->and(Levels::for(450))->level->toBe(3)->percent->toBe(50);
});

test('xp is synced from activity, idempotent, and revoked when the source disappears', function () {
    $s = xpSetup();
    LessonProgress::markDone($s['lessons'][0]->id, $s['a']->id);
    LessonProgress::markDone($s['lessons'][1]->id, $s['a']->id);
    $sync = app(SyncXp::class);

    $sync->handle($s['a']);
    $sync->handle($s['a']);
    expect(XpEvent::where('user_id', $s['a']->id)->where('rule', 'lesson_done')->sum('points'))->toEqual(20);

    LessonProgress::where('lesson_id', $s['lessons'][1]->id)->delete();
    $sync->handle($s['a']);
    expect(XpEvent::where('user_id', $s['a']->id)->sum('points'))->toEqual(10);
});

test('badges unlock from rule counts', function () {
    $s = xpSetup();
    LessonProgress::markDone($s['lessons'][0]->id, $s['a']->id);
    $this->actingAs($s['a'])->post('/tanya', ['class_room_id' => $s['class']->id, 'title' => 'Pertanyaan pertamaku', 'body' => 'Halo', 'visibility' => 'class']);

    $badges = collect(app(Achievements::class)->summary($s['a'])['badges'])->keyBy('key');

    expect($badges['langkah_pertama']['earned_at'])->not->toBeNull()
        ->and($badges['berani_bertanya']['earned_at'])->not->toBeNull()
        ->and($badges['kutu_buku'])->earned_at->toBeNull()->progress->toBe(1)->target->toBe(10);
});

test('accepted forum answers give xp to the helper', function () {
    $s = xpSetup();
    $this->actingAs($s['a'])->post('/tanya', ['class_room_id' => $s['class']->id, 'title' => 'Tolong bantu ya', 'body' => '?', 'visibility' => 'class']);
    $q = Question::first();
    $this->actingAs($s['b'])->post("/tanya/{$q->id}/jawaban", ['body' => 'Begini caranya']);
    $this->actingAs($s['a'])->post('/tanya/jawaban/'.$q->answers()->first()->id.'/terbaik');

    app(SyncXp::class)->handle($s['b']);
    expect(XpEvent::where('user_id', $s['b']->id)->where('rule', 'forum_answer_accepted')->sum('points'))->toEqual(20);
});

test('student sees own achievements and class leaderboard; others are protected', function () {
    $s = xpSetup();
    LessonProgress::markDone($s['lessons'][0]->id, $s['b']->id);
    app(SyncXp::class)->handle($s['b']);

    $this->actingAs($s['a'])->get('/prestasi')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Gamification::Achievements')->where('self', true)
            ->where('leaderboards.0.top.0.id', $s['b']->id)->where('leaderboards.0.top.0.name', explode(' ', $s['b']->name)[0]));

    $this->actingAs($s['a'])->get("/prestasi/siswa/{$s['b']->id}")->assertForbidden();
    $this->actingAs($s['teacher'])->get("/prestasi/siswa/{$s['b']->id}")->assertOk();
    $this->actingAs(userWithRole('teacher'))->get("/prestasi/siswa/{$s['b']->id}")->assertForbidden();

    $guardian = userWithRole('guardian');
    $guardian->children()->attach($s['b']->id, ['relationship' => 'ibu']);
    $this->actingAs($guardian)->get('/prestasi')->assertRedirect("/prestasi/siswa/{$s['b']->id}");
});

test('teacher sees xp table for their class and the student dashboard shows the level', function () {
    $s = xpSetup();
    LessonProgress::markDone($s['lessons'][0]->id, $s['a']->id);

    $this->actingAs($s['teacher'])->get('/prestasi/kelas')->assertOk()
        ->assertInertia(fn ($p) => $p->has('rows', 2)->where('rows.0.id', $s['a']->id)->where('rows.0.level.xp', 10));
    $this->actingAs($s['a'])->get('/prestasi/kelas')->assertForbidden();
    $this->actingAs($s['a'])->get('/dashboard')->assertInertia(fn ($p) => $p->where('achievements.level.xp', 10));
});

test('xp:sync command recalculates every student', function () {
    $s = xpSetup();
    LessonProgress::markDone($s['lessons'][0]->id, $s['a']->id);

    $this->artisan('xp:sync')->assertSuccessful();
    expect(XpEvent::where('user_id', $s['a']->id)->count())->toBe(1);
});
