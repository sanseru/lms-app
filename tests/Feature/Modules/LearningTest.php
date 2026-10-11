<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\Lesson;
use Modules\Learning\Models\LessonProgress;

/** Kelas + kursus terbit + siswa terdaftar. */
function learningSetup(array $course = []): array
{
    $teacher = userWithRole('teacher');
    $student = userWithRole('student');
    $course = Course::create($course + ['title' => 'Python Start', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $class = ClassRoom::create([
        'code' => 'PYT-'.fake()->unique()->numberBetween(100, 999), 'name' => 'Python 1', 'subject' => 'Python', 'level' => 'beginner',
        'teacher_id' => $teacher->id, 'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active', 'color' => 'violet',
    ]);
    $class->forceFill(['course_id' => $course->id])->saveQuietly();
    $class->students()->attach($student->id);

    return [$teacher, $student, $course, $class];
}

function makeQuiz(Course $course, array $attributes = []): Lesson
{
    $quiz = $course->lessons()->create($attributes + ['title' => 'Quiz 1', 'type' => 'quiz', 'position' => 1, 'pass_score' => 70]);
    $quiz->questions()->create(['position' => 1, 'prompt' => '1 + 1?', 'type' => 'single', 'points' => 1, 'options' => [['text' => '2', 'correct' => true], ['text' => '3', 'correct' => false]]]);
    $quiz->questions()->create(['position' => 2, 'prompt' => 'Even numbers?', 'type' => 'multiple', 'points' => 1, 'options' => [['text' => '2', 'correct' => true], ['text' => '3', 'correct' => false], ['text' => '4', 'correct' => true]]]);

    return $quiz->fresh('questions');
}

test('teacher can create a course and a quiz with questions', function () {
    $teacher = userWithRole('teacher');

    $this->actingAs($teacher)->post('/courses', ['title' => 'Scratch Basics', 'subject' => 'Scratch', 'level' => 'beginner', 'color' => 'amber', 'status' => 'draft'])
        ->assertRedirect();
    $course = Course::firstWhere('title', 'Scratch Basics');

    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", [
        'title' => 'Scratch quiz', 'type' => 'quiz', 'pass_score' => 60, 'is_published' => true,
        'questions' => [
            ['prompt' => 'A character is called?', 'type' => 'single', 'points' => 2, 'options' => [['text' => 'Sprite', 'correct' => true], ['text' => 'Stage', 'correct' => false]]],
        ],
    ])->assertRedirect()->assertSessionHasNoErrors();

    $lesson = $course->lessons()->first();
    expect($lesson->type)->toBe('quiz')
        ->and($lesson->questions)->toHaveCount(1)
        ->and($lesson->questions->first()->correctIndexes())->toBe([0]);
});

test('quiz question needs exactly one correct answer for single choice', function () {
    [$teacher, , $course] = learningSetup();

    $this->actingAs($teacher)->post("/courses/{$course->id}/lessons", [
        'title' => 'Quiz', 'type' => 'quiz',
        'questions' => [
            ['prompt' => 'A?', 'type' => 'single', 'points' => 1, 'options' => [['text' => 'x', 'correct' => false], ['text' => 'y', 'correct' => false]]],
            ['prompt' => 'B?', 'type' => 'single', 'points' => 1, 'options' => [['text' => 'x', 'correct' => true], ['text' => 'y', 'correct' => true]]],
        ],
    ])->assertSessionHasErrors(['questions.0.options', 'questions.1.options']);
});

test('updating a quiz keeps, edits, and removes questions', function () {
    [$teacher, , $course] = learningSetup();
    $quiz = makeQuiz($course);
    [$first] = $quiz->questions;

    $this->actingAs($teacher)->put("/lessons/{$quiz->id}", [
        'title' => 'New quiz', 'type' => 'quiz', 'is_published' => true,
        'questions' => [['id' => $first->id, 'prompt' => '2 + 2?', 'type' => 'single', 'points' => 1, 'options' => [['text' => '4', 'correct' => true], ['text' => '5', 'correct' => false]]]],
    ])->assertSessionHasNoErrors();

    expect($quiz->questions()->count())->toBe(1)
        ->and($quiz->questions()->first()->id)->toBe($first->id)
        ->and($quiz->questions()->first()->prompt)->toBe('2 + 2?');
});

test('students and parents only see published courses of their classes', function () {
    [, $student, $course] = learningSetup();
    $draft = Course::create(['title' => 'Draft', 'subject' => 'X', 'level' => 'beginner', 'color' => 'sky', 'status' => 'draft']);
    ClassRoom::first()->update([]);
    $other = Course::create(['title' => 'Other', 'subject' => 'Y', 'level' => 'beginner', 'color' => 'sky', 'status' => 'published']);
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($student->id);

    $this->actingAs($student)->get('/courses')
        ->assertInertia(fn ($page) => $page->component('Learning::Courses/Mine')->has('courses', 1)->where('courses.0.id', $course->id));
    $this->actingAs($student)->get("/courses/{$course->id}")->assertOk();
    $this->actingAs($student)->get("/courses/{$other->id}")->assertForbidden();
    $this->actingAs($student)->get("/courses/{$draft->id}")->assertForbidden();
    $this->actingAs($guardian)->get("/courses/{$course->id}")->assertOk();
    $this->actingAs(userWithRole('student'))->get("/courses/{$course->id}")->assertForbidden();
    $this->actingAs($student)->get('/courses/create')->assertForbidden();
});

test('quiz is graded automatically and answer key stays hidden until passing', function () {
    [, $student, $course] = learningSetup();
    $quiz = makeQuiz($course);
    [$q1, $q2] = $quiz->questions;

    $this->actingAs($student)->get("/lessons/{$quiz->id}")
        ->assertInertia(fn ($page) => $page->where('questions.0.options', ['2', '3'])->missing('answerKey'));

    // One right, one wrong: 50, not passed, key still hidden.
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", ['answers' => [$q1->id => 0, $q2->id => [0]]]);
    $this->actingAs($student)->get("/lessons/{$quiz->id}")
        ->assertInertia(fn ($page) => $page->where('result.score', 50)->where('result.passed', false)->where('result.reveal', false)->where("result.items.{$q1->id}.answer", null));
    expect(LessonProgress::where('student_id', $student->id)->exists())->toBeFalse();

    // All right (order of multiple-choice answers does not matter).
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", ['answers' => [$q1->id => 0, $q2->id => [2, 0]]]);
    $this->actingAs($student)->get("/lessons/{$quiz->id}")
        ->assertInertia(fn ($page) => $page->where('result.score', 100)->where('result.reveal', true)->where("result.items.{$q2->id}.answer", [0, 2]));
    expect(LessonProgress::where('student_id', $student->id)->where('lesson_id', $quiz->id)->exists())->toBeTrue();
});

test('quiz respects the attempt limit and only enrolled students can take it', function () {
    [, $student, $course] = learningSetup();
    $quiz = makeQuiz($course, ['max_attempts' => 1]);
    $answers = ['answers' => [$quiz->questions[0]->id => 1]];

    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", $answers);
    $this->actingAs($student)->post("/lessons/{$quiz->id}/quiz", $answers)->assertSessionHas('error');
    expect($quiz->attempts()->count())->toBe(1);

    $this->actingAs(userWithRole('student'))->post("/lessons/{$quiz->id}/quiz", $answers)->assertForbidden();
    $this->actingAs(userWithRole('guardian'))->post("/lessons/{$quiz->id}/quiz", $answers)->assertForbidden();
});

test('student submits an assignment and their teacher grades it', function () {
    Storage::fake('local');
    [$teacher, $student, $course] = learningSetup();
    $task = $course->lessons()->create(['title' => 'Project', 'type' => 'assignment', 'position' => 1, 'max_score' => 100]);

    $this->actingAs($student)->post("/lessons/{$task->id}/submission", [])->assertSessionHasErrors('body');
    $this->actingAs($student)->post("/lessons/{$task->id}/submission", [
        'body' => 'Done', 'file' => UploadedFile::fake()->create('game.py', 4, 'text/x-python'),
    ])->assertSessionHasNoErrors();

    $submission = AssignmentSubmission::first();
    expect($submission->status)->toBe('submitted')->and($submission->file_name)->toBe('game.py');
    Storage::disk('local')->assertExists($submission->file_path);

    // Another teacher cannot grade; the class teacher can.
    $this->actingAs(userWithRole('teacher'))->post("/submissions/{$submission->id}/grade", ['status' => 'graded', 'score' => 90])->assertForbidden();
    $this->actingAs($teacher)->post("/submissions/{$submission->id}/grade", ['status' => 'graded', 'score' => 120])->assertSessionHasErrors('score');
    $this->actingAs($teacher)->post("/submissions/{$submission->id}/grade", ['status' => 'graded', 'score' => 90, 'feedback' => 'Great work'])->assertSessionHasNoErrors();

    expect($submission->fresh())->status->toBe('graded')->score->toBe(90)->graded_by->toBe($teacher->id);

    // Already graded: cannot resubmit.
    $this->actingAs($student)->post("/lessons/{$task->id}/submission", ['body' => 'Revised'])->assertSessionHasErrors('body');

    $this->actingAs($student)->get("/submissions/{$submission->id}/file")->assertOk();
    $this->actingAs(userWithRole('student'))->get("/submissions/{$submission->id}/file")->assertForbidden();
});

test('revision request lets the student resubmit and needs feedback', function () {
    [$teacher, $student, $course] = learningSetup();
    $task = $course->lessons()->create(['title' => 'Project', 'type' => 'assignment', 'position' => 1]);
    $this->actingAs($student)->post("/lessons/{$task->id}/submission", ['link_url' => 'https://scratch.mit.edu/projects/1']);
    $submission = AssignmentSubmission::first();

    $this->actingAs($teacher)->post("/submissions/{$submission->id}/grade", ['status' => 'revision'])->assertSessionHasErrors('feedback');
    $this->actingAs($teacher)->post("/submissions/{$submission->id}/grade", ['status' => 'revision', 'feedback' => 'Add a score'])->assertSessionHasNoErrors();
    $this->actingAs($student)->post("/lessons/{$task->id}/submission", ['link_url' => 'https://scratch.mit.edu/projects/2'])->assertSessionHasNoErrors();

    expect($submission->fresh())->status->toBe('submitted')->link_url->toBe('https://scratch.mit.edu/projects/2');
});

test('grading queue shows only submissions from the teachers own classes', function () {
    [$teacher, $student, $course] = learningSetup();
    [$otherTeacher, $otherStudent, $otherCourse] = learningSetup(['title' => 'Web']);
    $task = $course->lessons()->create(['title' => 'A', 'type' => 'assignment', 'position' => 1]);
    $otherTask = $otherCourse->lessons()->create(['title' => 'B', 'type' => 'assignment', 'position' => 1]);
    $this->actingAs($student)->post("/lessons/{$task->id}/submission", ['body' => 'ok']);
    $this->actingAs($otherStudent)->post("/lessons/{$otherTask->id}/submission", ['body' => 'ok']);

    $this->actingAs($teacher)->get('/grading')
        ->assertInertia(fn ($page) => $page->component('Learning::Grading/Index')->has('submissions.data', 1)->where('submissions.data.0.lesson.title', 'A'));
    $this->actingAs(userWithRole('admin'))->get('/grading')->assertInertia(fn ($page) => $page->has('submissions.data', 2));
    $this->actingAs($student)->get('/grading')->assertForbidden();
});

test('progress report is visible to the student, their parent, and their teacher only', function () {
    [$teacher, $student, $course] = learningSetup();
    $material = $course->lessons()->create(['title' => 'Intro', 'type' => 'material', 'position' => 1, 'body' => "# Hello\n\n<script>alert(1)</script>"]);
    $course->lessons()->create(['title' => 'Next up', 'type' => 'material', 'position' => 2]);
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($student->id);

    $this->actingAs($student)->get("/lessons/{$material->id}")
        ->assertInertia(fn ($page) => $page->where('lesson.body_html', fn ($html) => str_contains($html, '<h1>Hello</h1>') && ! str_contains($html, '<script>')));
    $this->actingAs($student)->post("/lessons/{$material->id}/complete")->assertSessionHas('success');

    $this->actingAs($guardian)->get('/progress')->assertRedirect("/progress/{$student->id}");
    $this->actingAs($guardian)->get("/progress/{$student->id}")
        ->assertInertia(fn ($page) => $page->where('courses.0.percent', 50)->where('courses.0.next.title', 'Next up'));
    $this->actingAs($teacher)->get("/progress/{$student->id}")->assertOk();
    $this->actingAs($student)->get("/progress/{$student->id}")->assertOk();
    $this->actingAs(userWithRole('guardian'))->get("/progress/{$student->id}")->assertForbidden();
    $this->actingAs(userWithRole('teacher'))->get("/progress/{$student->id}")->assertForbidden();
});

test('lessons can be reordered and hidden drafts are not shown to students', function () {
    [$teacher, $student, $course] = learningSetup();
    $a = $course->lessons()->create(['title' => 'A', 'type' => 'material', 'position' => 1]);
    $b = $course->lessons()->create(['title' => 'B', 'type' => 'material', 'position' => 2]);
    $hidden = $course->lessons()->create(['title' => 'Secret', 'type' => 'material', 'position' => 3, 'is_published' => false]);

    $this->actingAs($teacher)->post("/lessons/{$b->id}/move", ['direction' => 'up']);
    expect($course->lessons()->pluck('title')->all())->toBe(['B', 'A', 'Secret']);

    $this->actingAs($student)->get("/courses/{$course->id}")->assertInertia(fn ($page) => $page->has('lessons', 2));
    $this->actingAs($student)->get("/lessons/{$hidden->id}")->assertForbidden();
});

test('dashboards include learning summaries', function () {
    [$teacher, $student, $course] = learningSetup();
    $task = $course->lessons()->create(['title' => 'Project', 'type' => 'assignment', 'position' => 1]);
    $this->actingAs($student)->post("/lessons/{$task->id}/submission", ['body' => 'ok']);
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($student->id);

    $this->actingAs($student)->get('/dashboard')->assertInertia(fn ($page) => $page->where('learning.0.percent', 100));
    $this->actingAs($guardian)->get('/dashboard')->assertInertia(fn ($page) => $page->where('children.0.learning.0.title', 'Python Start'));
    $this->actingAs($teacher)->get('/dashboard')->assertInertia(fn ($page) => $page->where('stats.to_grade', 1));
});

test('class room resolves its attached course', function () {
    [, , $course, $class] = learningSetup();

    expect($class->fresh()->course->id)->toBe($course->id);
});
