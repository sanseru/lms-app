<?php

use Modules\Certificates\Models\Certificate;
use Modules\Classes\Models\ClassRoom;
use Modules\Gamification\Actions\Achievements;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LessonProgress;

function certSetup(): array
{
    $teacher = userWithRole('teacher');
    [$a, $b] = [userWithRole('student', ['name' => 'Aldi Saputra']), userWithRole('student')];
    $course = Course::create(['title' => 'Python Start', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lessons = collect(range(1, 2))->map(fn ($i) => $course->lessons()->create(['title' => "L{$i}", 'type' => 'material', 'position' => $i, 'is_published' => true]));
    $class = ClassRoom::create([
        'code' => 'CT-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Python 1', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach([$a->id, $b->id]);
    $lessons->each(fn ($l) => LessonProgress::markDone($l->id, $a->id));
    LessonProgress::markDone($lessons[0]->id, $b->id);

    return compact('teacher', 'a', 'b', 'class', 'course');
}

test('students who finished every lesson are listed as eligible', function () {
    $s = certSetup();

    $this->actingAs($s['teacher'])->get('/sertifikat-saya')
        ->assertInertia(fn ($p) => $p->where('mode', 'staff')->has('eligible', 1)->where('eligible.0.student.id', $s['a']->id));
});

test('teacher issues a certificate that the student can view and the public can verify', function () {
    $s = certSetup();

    $this->actingAs($s['teacher'])->post('/sertifikat-saya', ['student_id' => $s['a']->id, 'class_room_id' => $s['class']->id, 'course_id' => $s['course']->id])
        ->assertSessionHasNoErrors();
    $cert = Certificate::first();

    expect($cert)->title->toBe('Python Start')->signer_name->toBe($s['teacher']->name)
        ->and($cert->number)->toStartWith('ALP/'.now()->format('Y/m').'/0001')
        ->and(strlen($cert->code))->toBe(10);

    $this->actingAs($s['a'])->get("/sertifikat-saya/{$cert->id}")->assertOk();
    $this->actingAs($s['b'])->get("/sertifikat-saya/{$cert->id}")->assertForbidden();
    $this->actingAs($s['teacher'])->get('/sertifikat-saya')->assertInertia(fn ($p) => $p->has('eligible', 0)->has('certificates', 1));

    auth()->logout();
    $this->get("/sertifikat/{$cert->code}")->assertOk()
        ->assertInertia(fn ($p) => $p->component('Certificates::Verify')->where('certificate.valid', true)->where('certificate.student', 'Aldi S.'));
    $this->get('/sertifikat/ZZZZZZZZZZ')->assertInertia(fn ($p) => $p->where('certificate', null));
});

test('certificates give xp and the graduate badge; revoking removes them', function () {
    $s = certSetup();
    $this->actingAs($s['teacher'])->post('/sertifikat-saya', ['student_id' => $s['a']->id, 'class_room_id' => $s['class']->id, 'course_id' => $s['course']->id]);
    $cert = Certificate::first();

    $badges = collect(app(Achievements::class)->summary($s['a'])['badges'])->keyBy('key');
    expect($badges['lulusan']['earned_at'])->not->toBeNull();

    $this->actingAs($s['teacher'])->post("/sertifikat-saya/{$cert->id}/cabut")->assertRedirect();
    expect($cert->fresh()->revoked_at)->not->toBeNull();
    $this->get("/sertifikat/{$cert->code}")->assertInertia(fn ($p) => $p->where('certificate.valid', false));

    $badges = collect(app(Achievements::class)->summary($s['a'])['badges'])->keyBy('key');
    expect($badges['lulusan']['earned_at'])->toBeNull();
});

test('teachers can only issue for their own class and enrolled students', function () {
    $s = certSetup();
    $stranger = userWithRole('student');

    $this->actingAs(userWithRole('teacher'))->post('/sertifikat-saya', ['student_id' => $s['a']->id, 'class_room_id' => $s['class']->id, 'title' => 'X'])
        ->assertSessionHasErrors('class_room_id');
    $this->actingAs($s['teacher'])->post('/sertifikat-saya', ['student_id' => $stranger->id, 'class_room_id' => $s['class']->id, 'title' => 'X'])
        ->assertSessionHasErrors('student_id');
    $this->actingAs($s['a'])->post('/sertifikat-saya', ['student_id' => $s['a']->id, 'class_room_id' => $s['class']->id, 'title' => 'X'])
        ->assertForbidden();
    $this->actingAs($s['teacher'])->post('/sertifikat-saya', ['student_id' => $s['b']->id, 'class_room_id' => $s['class']->id, 'title' => 'Juara Lomba', 'achievement' => 'sebagai Juara 1'])
        ->assertSessionHasNoErrors();
});

test('guardian sees their child certificates only', function () {
    $s = certSetup();
    $this->actingAs($s['teacher'])->post('/sertifikat-saya', ['student_id' => $s['a']->id, 'class_room_id' => $s['class']->id, 'course_id' => $s['course']->id]);
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($s['a']->id, ['relationship' => 'ayah']);
    $other = userWithRole('guardian');

    $this->actingAs($guardian)->get('/sertifikat-saya')->assertInertia(fn ($p) => $p->where('mode', 'guardian')->has('certificates', 1));
    $this->actingAs($other)->get('/sertifikat-saya')->assertInertia(fn ($p) => $p->has('certificates', 0));
});
