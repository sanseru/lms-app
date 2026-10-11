<?php

use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LessonProgress;

test('the student course page shows the class they take and the learning path with progress', function () {
    $teacher = userWithRole('teacher', ['name' => 'Budi Santoso']);
    $student = userWithRole('student');
    $course = Course::create(['title' => 'Python Start', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lessons = collect(['Hello', 'Variables', 'Loops'])->map(fn ($t, $i) => $course->lessons()->create(['title' => $t, 'type' => 'material', 'position' => $i + 1, 'is_published' => true]));
    $course->lessons()->create(['title' => 'Draft', 'type' => 'material', 'position' => 9, 'is_published' => false]);
    $class = ClassRoom::create([
        'code' => 'PY-101', 'name' => 'Python Saturday', 'subject' => 'Python', 'level' => 'beginner', 'teacher_id' => $teacher->id,
        'schedule_days' => [6], 'start_time' => '09:00', 'end_time' => '10:00', 'status' => 'active', 'color' => 'violet', 'zoom_url' => 'https://zoom.us/j/1',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach($student->id);
    LessonProgress::create(['lesson_id' => $lessons[0]->id, 'student_id' => $student->id, 'completed_at' => now()]);

    $this->actingAs($student)->get('/courses')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Learning::Courses/Mine')
            ->where('selected', $course->id)
            ->where('courses.0.classes.0.name', 'Python Saturday')
            ->where('courses.0.classes.0.teacher.name', 'Budi Santoso')
            ->where('courses.0.classes.0.zoom_url', 'https://zoom.us/j/1')
            ->has('courses.0.lessons', 3)
            ->where('courses.0.lessons.0.done', true)
            ->where('courses.0.next.title', 'Variables')
            ->where('courses.0.percent', 33)
            ->has('nextSessions'));
});

test('the course page only lists classes the student is enrolled in', function () {
    $student = userWithRole('student');
    $course = Course::create(['title' => 'Web', 'subject' => 'Web Development', 'level' => 'beginner', 'color' => 'sky', 'status' => 'published']);
    $make = fn ($code) => tap(ClassRoom::create([
        'code' => $code, 'name' => "Class {$code}", 'subject' => 'Web', 'level' => 'beginner', 'teacher_id' => userWithRole('teacher')->id,
        'schedule_days' => [1], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'sky',
    ]), fn ($c) => $c->forceFill(['course_id' => $course->id])->saveQuietly());
    $mine = $make('W-1');
    $make('W-2');
    $mine->students()->attach($student->id);

    $this->actingAs($student)->get('/courses')
        ->assertInertia(fn ($p) => $p->has('courses.0.classes', 1)->where('courses.0.classes.0.code', 'W-1'));
});
