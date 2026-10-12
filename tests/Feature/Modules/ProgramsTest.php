<?php

use App\Models\User;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseUnit;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\Program;

/**
 * A Python program with three levels; the student's class uses level 1.
 *
 * @return array{0: User, 1: User, 2: Program, 3: array<int, Course>}
 */
function pythonProgram(): array
{
    [$teacher, $student, $start1] = learningSetup(['title' => 'Python Start 1']);
    $program = Program::create(['name' => 'Python', 'color' => 'violet']);
    $start1->update(['program_id' => $program->id, 'level_order' => 1]);
    $levels = [$start1];

    foreach ([2, 3] as $n) {
        $levels[] = Course::create(['title' => "Python Start {$n}", 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published', 'program_id' => $program->id, 'level_order' => $n]);
    }

    foreach ($levels as $level) {
        foreach ([1, 2] as $p) {
            $level->lessons()->create(['title' => "{$level->title} item {$p}", 'type' => 'material', 'position' => $p, 'is_published' => true]);
        }
    }

    return [$teacher, $student, $program, $levels];
}

function finishLevel(Course $course, User $student): void
{
    $course->lessons()->get()->each(fn (Lesson $l) => LessonProgress::create(['lesson_id' => $l->id, 'student_id' => $student->id, 'completed_at' => now()]));
}

test('the next level opens only after every item of the level before is done', function () {
    [, $student, , [$start1, $start2, $start3]] = pythonProgram();
    $item2 = $start2->lessons()->first();

    $this->actingAs($student)->get("/lessons/{$item2->id}")->assertForbidden();
    $this->actingAs($student)->get("/courses/{$start2->id}")
        ->assertInertia(fn ($page) => $page->component('Learning::Courses/Preview')->where('unlockAfter', 'Python Start 1'));

    // Half of level 1 is not enough.
    LessonProgress::create(['lesson_id' => $start1->lessons()->first()->id, 'student_id' => $student->id, 'completed_at' => now()]);
    $this->actingAs($student)->get("/lessons/{$item2->id}")->assertForbidden();

    LessonProgress::create(['lesson_id' => $start1->lessons()->skip(1)->first()->id, 'student_id' => $student->id, 'completed_at' => now()]);
    $this->actingAs($student)->get("/lessons/{$item2->id}")->assertOk()->assertInertia(fn ($page) => $page->where('can.learn', true));
    $this->actingAs($student)->post("/lessons/{$item2->id}/complete")->assertRedirect();

    // Level 3 still waits for level 2 to be finished, step by step.
    $this->actingAs($student)->get('/lessons/'.$start3->lessons()->first()->id)->assertForbidden();

    $this->actingAs($student)->get('/courses')->assertInertia(fn ($page) => $page->component('Learning::Courses/Mine')
        ->has('courses', 2)
        ->where('programs.0.name', 'Python')
        ->where('programs.0.levels.0.completed', true)
        ->where('programs.0.levels.1.unlocked', true)
        ->where('programs.0.levels.2.unlocked', false));
});

test('a student placed in a higher level can still open the levels below it', function () {
    [, , , [$start1, $start2, $start3]] = pythonProgram();
    $placed = userWithRole('student');
    $class = ClassRoom::create([
        'code' => 'PYT-900', 'name' => 'Python Start 2 class', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $start1->classes()->first()->teacher_id, 'schedule_days' => [3], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $start2->id])->saveQuietly();
    $class->students()->attach($placed->id);

    $this->actingAs($placed)->get('/lessons/'.$start1->lessons()->first()->id)->assertOk();
    $this->actingAs($placed)->get('/lessons/'.$start2->lessons()->first()->id)->assertOk();
    $this->actingAs($placed)->get('/lessons/'.$start3->lessons()->first()->id)->assertForbidden();
});

test('parents see the levels their child unlocked', function () {
    [, $student, , [$start1, $start2]] = pythonProgram();
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($student->id);

    $this->actingAs($guardian)->get("/courses/{$start2->id}")->assertForbidden();
    finishLevel($start1, $student);
    $this->actingAs($guardian)->get("/courses/{$start2->id}")->assertOk();
});

test('teachers manage programs and put courses in them as levels', function () {
    [$teacher, , $course] = learningSetup();

    $this->actingAs($teacher)->post('/programs', ['name' => 'Python', 'color' => 'violet', 'description' => 'Step by step'])->assertSessionHasNoErrors();
    $program = Program::sole();
    $this->actingAs($teacher)->put("/programs/{$program->id}", ['name' => 'Python Track', 'color' => 'sky'])->assertSessionHasNoErrors();
    expect($program->fresh())->name->toBe('Python Track')->color->toBe('sky');

    $data = $course->only('title', 'subject', 'level', 'description', 'color', 'status');
    $this->actingAs($teacher)->put("/courses/{$course->id}", $data + ['program_id' => $program->id])->assertSessionHasErrors('level_order');
    $this->actingAs($teacher)->put("/courses/{$course->id}", $data + ['program_id' => $program->id, 'level_order' => 1])->assertSessionHasNoErrors();
    expect($course->fresh())->program_id->toBe($program->id)->level_order->toBe(1);

    $this->actingAs($teacher)->get('/programs')->assertInertia(fn ($page) => $page->component('Learning::Programs/Index')->where('programs.0.levels.0.id', $course->id));

    $this->actingAs($teacher)->delete("/programs/{$program->id}")->assertRedirect();
    expect($course->fresh()->program_id)->toBeNull();

    $this->actingAs(userWithRole('student'))->get('/programs')->assertForbidden();
});

test('teachers group items into numbered lessons and students see them as tasks', function () {
    [$teacher, $student, $course] = learningSetup();
    $a = $course->lessons()->create(['title' => 'Theory', 'type' => 'material', 'position' => 1, 'is_published' => true]);
    $b = $course->lessons()->create(['title' => 'Practice', 'type' => 'material', 'position' => 2, 'is_published' => true]);
    $c = $course->lessons()->create(['title' => 'Loose item', 'type' => 'material', 'position' => 3, 'is_published' => true]);

    $this->actingAs($teacher)->post("/courses/{$course->id}/units", ['title' => 'Getting started'])->assertSessionHasNoErrors();
    $unit = CourseUnit::sole();
    $this->actingAs($teacher)->put("/lessons/{$a->id}/unit", ['unit_id' => $unit->id])->assertSessionHasNoErrors();
    $this->actingAs($teacher)->put("/lessons/{$b->id}/unit", ['unit_id' => $unit->id])->assertSessionHasNoErrors();

    // A lesson from another course cannot be used.
    $other = Course::create(['title' => 'Other', 'subject' => 'X', 'level' => 'beginner', 'color' => 'sky', 'status' => 'published']);
    $foreign = $other->units()->create(['title' => 'Foreign']);
    $this->actingAs($teacher)->put("/lessons/{$c->id}/unit", ['unit_id' => $foreign->id])->assertSessionHasErrors('unit_id');

    $this->actingAs($student)->get('/courses')->assertInertia(fn ($page) => $page
        ->where('courses.0.units.0.title', 'Getting started')
        ->where('courses.0.lessons.0.unit_id', $unit->id)
        ->where('courses.0.lessons.2.unit_id', null));

    // Inside a lesson, the dots in the top bar are only its tasks.
    $this->actingAs($student)->get("/lessons/{$b->id}")->assertInertia(fn ($page) => $page
        ->where('unit.title', 'Getting started')
        ->where('unit.number', 1)
        ->has('outline', 2)
        ->where('next.id', $c->id));

    $this->actingAs($teacher)->put("/units/{$unit->id}", ['title' => 'Start here'])->assertSessionHasNoErrors();
    expect($unit->fresh()->title)->toBe('Start here');

    $this->actingAs($teacher)->delete("/units/{$unit->id}")->assertRedirect();
    expect($a->fresh()->unit_id)->toBeNull()->and(Lesson::count())->toBe(3);

    $this->actingAs($student)->post("/courses/{$course->id}/units", ['title' => 'Nope'])->assertForbidden();
});

test('the catalog groups levels by program and marks locked levels', function () {
    [, $student, $program, [$start1, $start2]] = pythonProgram();

    $this->actingAs($student)->get('/courses/explore')->assertInertia(fn ($page) => $page
        ->where('courses.0.id', $start1->id)
        ->where('courses.0.program.name', 'Python')
        ->where('courses.0.mine', true)
        ->where('courses.1.id', $start2->id)
        ->where('courses.1.unlock_after', 'Python Start 1'));
});
