<?php

use Modules\Learning\Models\Course;
use Modules\Learning\Models\CourseInterest;

function otherCourse(array $attributes = []): Course
{
    $course = Course::create($attributes + ['title' => 'Web Development', 'subject' => 'Web', 'level' => 'beginner', 'color' => 'sky', 'status' => 'published', 'description' => 'Build your own website.']);
    $course->lessons()->create(['title' => 'HTML basics', 'type' => 'material', 'position' => 1, 'is_published' => true, 'body' => 'Secret lesson body', 'duration_minutes' => 20]);
    $course->lessons()->create(['title' => 'Unpublished draft', 'type' => 'material', 'position' => 2, 'is_published' => false]);

    return $course;
}

test('the catalog lists every published course and marks the student own course', function () {
    [, $student, $course] = learningSetup();
    $other = otherCourse();
    Course::create(['title' => 'Hidden draft', 'subject' => 'X', 'level' => 'beginner', 'color' => 'sky', 'status' => 'draft']);

    $this->actingAs($student)->get('/courses/explore')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Learning::Courses/Explore')
            ->has('courses', 2)
            ->where('courses.0.id', $course->id)
            ->where('courses.0.mine', true)
            ->where('courses.1.id', $other->id)
            ->where('courses.1.mine', false)
            ->where('courses.1.lessons_count', 1)
            ->where('current.id', $course->id));
});

test('only students open the catalog', function () {
    learningSetup();

    $this->actingAs(userWithRole('teacher'))->get('/courses/explore')->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->get('/courses/explore')->assertForbidden();
});

test('a course the student does not take is a read-only preview with locked lessons', function () {
    [, $student] = learningSetup();
    $other = otherCourse();
    $lesson = $other->lessons()->first();

    $this->actingAs($student)->get("/courses/{$other->id}")
        ->assertInertia(fn ($page) => $page->component('Learning::Courses/Preview')
            ->has('lessons', 1)
            ->where('lessons.0.title', 'HTML basics')
            ->missing('lessons.0.body')
            ->where('interested', false));

    // The lesson itself stays locked until the student joins a class with this course.
    $this->actingAs($student)->get("/lessons/{$lesson->id}")->assertForbidden();
});

test('a student can say they are interested once, and admins and parents are told', function () {
    [, $student] = learningSetup();
    $other = otherCourse();
    $admin = userWithRole('admin');
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($student->id);

    $this->actingAs($student)->post("/courses/{$other->id}/interest")->assertRedirect()->assertSessionHas('success');
    $this->actingAs($student)->post("/courses/{$other->id}/interest")->assertRedirect();

    expect(CourseInterest::where('course_id', $other->id)->where('student_id', $student->id)->count())->toBe(1)
        ->and($admin->notifications()->count())->toBe(1)
        ->and($guardian->notifications()->count())->toBe(1)
        ->and($admin->notifications()->first()->data['title'])->toContain('Web Development');

    $this->actingAs($student)->get("/courses/{$other->id}")
        ->assertInertia(fn ($page) => $page->where('interested', true));

    // The teacher sees who is interested on the course page.
    $this->actingAs($admin)->get("/courses/{$other->id}")
        ->assertInertia(fn ($page) => $page->component('Learning::Courses/Show')->has('interests', 1)->where('interests.0.name', $student->name));
});

test('interest is refused for own, draft and invisible courses and for non-students', function () {
    [, $student, $course] = learningSetup();
    $draft = otherCourse(['title' => 'Draft course', 'status' => 'draft']);

    $this->actingAs($student)->post("/courses/{$course->id}/interest")->assertSessionHas('error');
    $this->actingAs($student)->post("/courses/{$draft->id}/interest")->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->post('/courses/'.otherCourse()->id.'/interest')->assertForbidden();

    expect(CourseInterest::count())->toBe(0);
});

test('lesson pages tell the page a student is learning so it opens in focus mode', function () {
    [, $student, $course, $class] = learningSetup();
    $lesson = $course->lessons()->create(['title' => 'Intro', 'type' => 'material', 'position' => 1, 'is_published' => true]);

    $this->actingAs($student)->get("/lessons/{$lesson->id}")
        ->assertInertia(fn ($page) => $page->where('can.learn', true)->has('outline', 1));
    $this->actingAs($class->teacher)->get("/lessons/{$lesson->id}")
        ->assertInertia(fn ($page) => $page->where('can.learn', false));
});
