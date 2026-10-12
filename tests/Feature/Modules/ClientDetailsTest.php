<?php

use Illuminate\Support\Facades\DB;
use Modules\Gamification\Actions\Achievements;
use Modules\Lab\Models\LabProject;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Program;
use Modules\Sessions\Models\ClassSession;

/** Small details from the client prototype: header search, trending periods, Online Lesson cards, badges. */
test('students search courses in the catalog and lessons only in levels they can open', function () {
    [, $student, $course] = learningSetup(['title' => 'Python Start 1']);
    $course->lessons()->create(['title' => 'For loops', 'type' => 'material', 'position' => 1, 'is_published' => true]);
    $course->lessons()->create(['title' => 'Loops draft', 'type' => 'material', 'position' => 2, 'is_published' => false]);
    $other = Course::create(['title' => 'Loops in Scratch', 'subject' => 'Scratch', 'level' => 'beginner', 'color' => 'amber', 'status' => 'published']);
    $other->lessons()->create(['title' => 'Forever loops', 'type' => 'material', 'position' => 1, 'is_published' => true]);

    $this->actingAs($student)->get('/search?q=loop')->assertInertia(fn ($p) => $p
        ->component('Learning::Search/Index')
        ->has('courses', 1)->where('courses.0.title', 'Loops in Scratch')->where('courses.0.mine', false)
        ->has('lessons', 1)->where('lessons.0.title', 'For loops'));

    $this->actingAs($student)->get('/search?q=python')->assertInertia(fn ($p) => $p->where('courses.0.mine', true));
    $this->actingAs($student)->get('/search?q=a')->assertInertia(fn ($p) => $p->has('courses', 0)->has('lessons', 0));
    $this->actingAs(userWithRole('teacher'))->get('/search?q=loop')->assertForbidden();
});

test('community trending can be shown for today, the week or the month', function () {
    $owner = userWithRole('student');
    $fan = userWithRole('student');
    $old = LabProject::create(['user_id' => $owner->id, 'title' => 'Old hit', 'language' => 'python', 'code' => '']);
    $new = LabProject::create(['user_id' => $owner->id, 'title' => 'New hit', 'language' => 'python', 'code' => '']);
    foreach ([$old, $new] as $p) {
        $p->forceFill(['shared_at' => now()->subDays(40)])->save();
    }
    $old->likers()->attach($fan->id, ['created_at' => now()->subDays(20)]);
    $new->likers()->attach($fan->id, ['created_at' => now()]);

    $this->actingAs($fan)->get('/community?period=today')->assertInertia(fn ($p) => $p->where('filters.period', 'today')->has('trending', 1)->where('trending.0.title', 'New hit'));
    $this->actingAs($fan)->get('/community')->assertInertia(fn ($p) => $p->where('filters.period', 'week')->has('trending', 1));
    $this->actingAs($fan)->get('/community?period=month')->assertInertia(fn ($p) => $p->has('trending', 2));
    $this->actingAs($fan)->get('/community?period=year')->assertInertia(fn ($p) => $p->where('filters.period', 'week'));
});

test('the student online lesson page shows the live class, the next one and the latest recording', function () {
    [$teacher, $student, , $class] = learningSetup();
    $session = fn (array $a) => ClassSession::create($a + ['class_room_id' => $class->id, 'teacher_id' => $teacher->id]);
    $session(['starts_at' => now()->subMinutes(20), 'ends_at' => now()->addMinutes(40), 'topic' => 'Loops']);
    $session(['starts_at' => now()->addDays(2), 'ends_at' => now()->addDays(2)->addHour()]);
    $session(['starts_at' => now()->subWeek(), 'ends_at' => now()->subWeek()->addHour(), 'status' => 'completed', 'recording_url' => 'https://zoom.us/rec/share/abc']);

    $this->actingAs($student)->get('/schedule')->assertInertia(fn ($p) => $p
        ->where('highlights.live.topic', 'Loops')
        ->where('highlights.next.status', 'scheduled')
        ->where('highlights.recording.recording_url', 'https://zoom.us/rec/share/abc')
        ->where('highlights.recording.minutes', 60));

    $this->actingAs($teacher)->get('/schedule')->assertInertia(fn ($p) => $p->where('highlights', null));
});

test('the client badges come from lab activity, python lessons and solved tasks', function () {
    $student = userWithRole('student');
    $friend = userWithRole('student');
    $mine = LabProject::create(['user_id' => $student->id, 'title' => 'Mine', 'language' => 'python', 'code' => '']);
    $mine->forceFill(['shared_at' => now()])->save();
    $theirs = LabProject::create(['user_id' => $friend->id, 'title' => 'Theirs', 'language' => 'python', 'code' => '']);
    $theirs->forceFill(['shared_at' => now()])->save();

    $mine->likers()->attach($friend->id, ['created_at' => now()]);
    $mine->likers()->attach($student->id, ['created_at' => now()]); // liking your own project doesn't count twice
    $theirs->comments()->create(['user_id' => $student->id, 'body' => 'Nice!']);

    $program = Program::create(['name' => 'Python', 'color' => 'violet']);
    $course = Course::create(['title' => 'Python Start 1', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published', 'program_id' => $program->id, 'level_order' => 1]);
    $lesson = $course->lessons()->create(['title' => 'Hello', 'type' => 'material', 'position' => 1, 'is_published' => true]);
    DB::table('lesson_progress')->insert(['lesson_id' => $lesson->id, 'student_id' => $student->id, 'completed_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

    $summary = app(Achievements::class)->summary($student);
    $badges = collect($summary['badges'])->keyBy('key');

    expect($badges['first_project']['earned_at'])->not->toBeNull()
        ->and($badges['three_projects']['progress'])->toBe(1)
        ->and($badges['first_like']['earned_at'])->not->toBeNull()
        ->and($badges['first_comment']['earned_at'])->not->toBeNull()
        ->and($badges['python_starter']['earned_at'])->not->toBeNull()
        ->and($badges['ten_solved']['target'])->toBe(10)
        // The Python milestone gives no extra XP and stays out of the XP lists.
        ->and(collect($summary['by_rule'])->pluck('label'))->not->toContain('Finished a Python lesson')
        // Lesson 10 + shared 15 + liked 5 + commented 2 + Python milestone 0.
        ->and($summary['level']['xp'])->toBe(32);
});
