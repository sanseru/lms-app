<?php

use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\ClassLessonOpen;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\Program;

/** Level with Lesson 1 (two tasks), Lesson 2 (one task) and a loose item at the end. */
function steppedCourse(): array
{
    [$teacher, $student, $course, $class] = learningSetup();
    $u1 = $course->units()->create(['title' => 'Start']);
    $u2 = $course->units()->create(['title' => 'Next']);
    $a = $course->lessons()->create(['title' => 'A', 'type' => 'material', 'position' => 1, 'is_published' => true, 'unit_id' => $u1->id]);
    $b = $course->lessons()->create(['title' => 'B', 'type' => 'material', 'position' => 2, 'is_published' => true, 'unit_id' => $u1->id]);
    $c = $course->lessons()->create(['title' => 'C', 'type' => 'material', 'position' => 3, 'is_published' => true, 'unit_id' => $u2->id]);
    $d = $course->lessons()->create(['title' => 'D', 'type' => 'material', 'position' => 4, 'is_published' => true]);

    return compact('teacher', 'student', 'course', 'class', 'u1', 'u2', 'a', 'b', 'c', 'd');
}

test('lessons open one after another inside a level', function () {
    ['student' => $student, 'course' => $course, 'a' => $a, 'b' => $b, 'c' => $c, 'd' => $d] = steppedCourse();

    // Both tasks of Lesson 1 are open; Lesson 2 and the loose item wait.
    $this->actingAs($student)->get("/lessons/{$b->id}")->assertOk();
    $this->actingAs($student)->get("/lessons/{$c->id}")->assertRedirect(route('learning.courses.index', ['course' => $course->id]))->assertSessionHas('error');
    $this->actingAs($student)->post("/lessons/{$c->id}/complete")->assertForbidden();

    $this->actingAs($student)->get('/courses')->assertInertia(fn ($page) => $page
        ->where('courses.0.lessons.0.locked', false)
        ->where('courses.0.lessons.2.locked', true)
        ->where('courses.0.lessons.3.locked', true));

    $this->actingAs($student)->post("/lessons/{$a->id}/complete");
    $this->actingAs($student)->get("/lessons/{$c->id}")->assertRedirect();
    $this->actingAs($student)->post("/lessons/{$b->id}/complete");
    $this->actingAs($student)->get("/lessons/{$c->id}")->assertOk();
    $this->actingAs($student)->get("/lessons/{$d->id}")->assertRedirect();
    $this->actingAs($student)->post("/lessons/{$c->id}/complete");
    $this->actingAs($student)->get("/lessons/{$d->id}")->assertOk();
});

test('the class teacher opens a lesson early for the whole class and can lock it again', function () {
    ['teacher' => $teacher, 'student' => $student, 'class' => $class, 'course' => $course, 'u2' => $u2, 'c' => $c] = steppedCourse();
    $otherStudent = userWithRole('student');
    $otherClass = ClassRoom::create([
        'code' => 'PYT-950', 'name' => 'Other class', 'subject' => 'Python', 'level' => 'beginner', 'teacher_id' => userWithRole('teacher')->id,
        'schedule_days' => [5], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $otherClass->forceFill(['course_id' => $course->id])->saveQuietly();
    $otherClass->students()->attach($otherStudent->id);

    $this->actingAs($teacher)->get("/classes/{$class->id}/lessons")->assertInertia(fn ($page) => $page
        ->component('Learning::LessonAccess/Show')
        ->has('levels.0.nodes', 3)
        ->where('levels.0.nodes.1.key', "u{$u2->id}")
        ->where('levels.0.nodes.1.title', 'Next')
        ->where('levels.0.nodes.1.opened', false));

    $this->actingAs($teacher)->put("/classes/{$class->id}/lessons", ['course_id' => $course->id, 'node_key' => "u{$u2->id}", 'open' => true])->assertRedirect();
    $this->actingAs($student)->get("/lessons/{$c->id}")->assertOk();
    // Only this class: the other class still goes step by step.
    $this->actingAs($otherStudent)->get("/lessons/{$c->id}")->assertRedirect();

    $this->actingAs($teacher)->put("/classes/{$class->id}/lessons", ['course_id' => $course->id, 'node_key' => "u{$u2->id}", 'open' => false]);
    $this->actingAs($student)->get("/lessons/{$c->id}")->assertRedirect();

    $this->actingAs($teacher)->put("/classes/{$class->id}/lessons/all", ['course_id' => $course->id, 'open' => true])->assertSessionHas('success');
    expect(ClassLessonOpen::where('class_room_id', $class->id)->count())->toBe(3);
    $this->actingAs($teacher)->put("/classes/{$class->id}/lessons/all", ['course_id' => $course->id, 'open' => false]);
    expect(ClassLessonOpen::count())->toBe(0);

    // Unknown circles, other teachers and students are refused.
    $this->actingAs($teacher)->put("/classes/{$class->id}/lessons", ['course_id' => $course->id, 'node_key' => 'u999999', 'open' => true])->assertStatus(422);
    $this->actingAs(userWithRole('teacher'))->get("/classes/{$class->id}/lessons")->assertForbidden();
    $this->actingAs($student)->get("/classes/{$class->id}/lessons")->assertForbidden();
});

test('opening a lesson of the next level lets the class into just that lesson', function () {
    ['teacher' => $teacher, 'student' => $student, 'class' => $class, 'course' => $start1] = steppedCourse();
    $program = Program::create(['name' => 'Python']);
    $start1->update(['program_id' => $program->id, 'level_order' => 1]);
    $start2 = Course::create(['title' => 'Start 2', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published', 'program_id' => $program->id, 'level_order' => 2]);
    $first = $start2->lessons()->create(['title' => 'Loops', 'type' => 'material', 'position' => 1, 'is_published' => true]);
    $second = $start2->lessons()->create(['title' => 'Lists', 'type' => 'material', 'position' => 2, 'is_published' => true]);

    $this->actingAs($student)->get("/lessons/{$first->id}")->assertForbidden();

    $this->actingAs($teacher)->get("/classes/{$class->id}/lessons")->assertInertia(fn ($page) => $page->has('levels', 2)->where('levels.1.id', $start2->id));
    $this->actingAs($teacher)->put("/classes/{$class->id}/lessons", ['course_id' => $start2->id, 'node_key' => "l{$second->id}", 'open' => true]);

    $this->actingAs($student)->get("/lessons/{$second->id}")->assertOk();
    // The level is open only for the lesson the teacher picked, not from the start.
    $this->actingAs($student)->get("/lessons/{$first->id}")->assertRedirect();
    $this->actingAs($student)->get('/courses')->assertInertia(fn ($page) => $page->where('programs.0.levels.1.unlocked', true));
});

test('the class page links teachers to lesson access', function () {
    ['teacher' => $teacher, 'student' => $student, 'class' => $class] = steppedCourse();

    $this->actingAs($teacher)->get("/classes/{$class->id}")->assertInertia(fn ($page) => $page->where('can.lessonAccess', true));
    $this->actingAs($student)->get("/classes/{$class->id}")->assertInertia(fn ($page) => $page->where('can.lessonAccess', false));
    expect(LessonProgress::count())->toBe(0);
});
