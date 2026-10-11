<?php

use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Course;
use Modules\Learning\Models\LessonProgress;
use Modules\Learning\Models\QuizAttempt;
use Modules\Reports\Models\MonthlyReportNote;
use Modules\Sessions\Models\ClassSession;

beforeEach(function () {
    $this->travelTo('2026-10-20 10:00');
    $this->admin = userWithRole('admin');
    $this->teacher = userWithRole('teacher', ['name' => 'Ms Sari']);
    $this->otherTeacher = userWithRole('teacher');
    $this->student = userWithRole('student', ['name' => 'Aldi']);
    $this->student->studentProfile()->create(['student_code' => 'SIS-00001', 'grade' => '5']);
    $this->guardian = userWithRole('guardian');
    $this->guardian->children()->attach($this->student->id);

    $this->class = ClassRoom::create([
        'code' => 'PYT-101', 'name' => 'Python Beginners', 'subject' => 'Python', 'teacher_id' => $this->teacher->id,
        'schedule_days' => [2], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active',
    ]);
    $this->class->students()->attach($this->student->id, ['status' => 'active']);
});

function completedSession(ClassRoom $class, string $at, string $status, array $extra = []): ClassSession
{
    $session = ClassSession::create([
        'class_room_id' => $class->id, 'teacher_id' => $class->teacher_id, 'starts_at' => $at, 'ends_at' => date('Y-m-d H:i', strtotime($at) + 3600),
        'status' => 'completed', 'topic' => 'For loops', 'parent_note' => 'Aldi asked lots of questions.', 'homework' => 'Exercise 3',
    ] + $extra);
    $session->attendances()->create(['student_id' => $class->students()->first()->id, 'status' => $status]);

    return $session;
}

test('a monthly report gathers sessions, lessons and quiz scores for that month only', function () {
    completedSession($this->class, '2026-10-06 16:00', 'present');
    completedSession($this->class, '2026-10-13 16:00', 'sick');
    completedSession($this->class, '2026-09-29 16:00', 'present'); // last month

    $course = Course::create(['title' => 'Python Start', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lesson = $course->lessons()->create(['title' => 'Variables', 'type' => 'material', 'position' => 1]);
    $quiz = $course->lessons()->create(['title' => 'Variables quiz', 'type' => 'quiz', 'position' => 2, 'pass_score' => 70]);
    LessonProgress::create(['lesson_id' => $lesson->id, 'student_id' => $this->student->id, 'completed_at' => '2026-10-07 19:00']);
    QuizAttempt::create(['lesson_id' => $quiz->id, 'student_id' => $this->student->id, 'answers' => [], 'score' => 60, 'earned_points' => 3, 'total_points' => 5, 'passed' => false]);
    QuizAttempt::create(['lesson_id' => $quiz->id, 'student_id' => $this->student->id, 'answers' => [], 'score' => 90, 'earned_points' => 9, 'total_points' => 10, 'passed' => true]);

    $this->actingAs($this->guardian)->get("/monthly-reports/{$this->student->id}/2026-10")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Reports::Show')
            ->where('report.month.label', 'October 2026')
            ->where('report.attendance.total', 2)
            ->where('report.attendance.present', 1)
            ->where('report.attendance.sick', 1)
            ->where('report.attendance.rate', 50)
            ->where('report.sessions.0.parent_note', 'Aldi asked lots of questions.')
            ->where('report.lessons.0.title', 'Variables')
            ->where('report.quizzes.0.score', 90)
            ->where('report.quizzes.0.tries', 2)
            ->has('editable', 0));

    $this->actingAs($this->guardian)->get("/monthly-reports/{$this->student->id}/2026-09")
        ->assertInertia(fn ($page) => $page->where('report.attendance.total', 1));
});

test('the class teacher writes a monthly note that shows on the report', function () {
    $this->actingAs($this->teacher)->get("/monthly-reports/{$this->student->id}/2026-10")
        ->assertInertia(fn ($page) => $page->has('editable', 1)->where('editable.0.id', $this->class->id));

    $this->actingAs($this->teacher)->put("/monthly-reports/{$this->student->id}/2026-10/note", [
        'class_room_id' => $this->class->id, 'highlights' => 'Uses for loops confidently.', 'focus' => 'Reading error messages.',
    ])->assertSessionHas('success');

    // Saving again updates the same note.
    $this->actingAs($this->teacher)->put("/monthly-reports/{$this->student->id}/2026-10/note", [
        'class_room_id' => $this->class->id, 'highlights' => 'Uses for and while loops confidently.', 'focus' => 'Reading error messages.',
    ])->assertSessionHas('success');
    expect(MonthlyReportNote::count())->toBe(1);

    $this->actingAs($this->guardian)->get("/monthly-reports/{$this->student->id}/2026-10")
        ->assertInertia(fn ($page) => $page
            ->where("report.notes.{$this->class->id}.highlights", 'Uses for and while loops confidently.')
            ->where("report.notes.{$this->class->id}.author", 'Ms Sari'));
});

test('only people connected to the student can read or annotate the report', function () {
    $stranger = userWithRole('guardian');
    $otherStudent = userWithRole('student');

    $this->actingAs($this->student)->get("/monthly-reports/{$this->student->id}/2026-10")->assertOk();
    $this->actingAs($this->admin)->get("/monthly-reports/{$this->student->id}/2026-10")->assertOk();
    $this->actingAs($stranger)->get("/monthly-reports/{$this->student->id}/2026-10")->assertForbidden();
    $this->actingAs($otherStudent)->get("/monthly-reports/{$this->student->id}/2026-10")->assertForbidden();
    $this->actingAs($this->otherTeacher)->get("/monthly-reports/{$this->student->id}/2026-10")->assertForbidden();

    $note = ['class_room_id' => $this->class->id, 'highlights' => 'x'];
    $this->actingAs($this->otherTeacher)->put("/monthly-reports/{$this->student->id}/2026-10/note", $note)->assertForbidden();
    $this->actingAs($this->guardian)->put("/monthly-reports/{$this->student->id}/2026-10/note", $note)->assertForbidden();
});

test('future or malformed months are not found', function () {
    $this->actingAs($this->guardian)->get("/monthly-reports/{$this->student->id}/2026-11")->assertNotFound();
    $this->actingAs($this->guardian)->get("/monthly-reports/{$this->student->id}/2026-13")->assertNotFound();
});

test('teachers see their students with missing notes, guardians see only their children', function () {
    completedSession($this->class, '2026-10-06 16:00', 'present');
    $sibling = userWithRole('student');
    $unrelated = userWithRole('student');

    $this->actingAs($this->teacher)->get('/monthly-reports?month=2026-10')
        ->assertInertia(fn ($page) => $page->component('Reports::Index')
            ->where('staff', true)
            ->has('students.data', 1)
            ->where('students.data.0.present', 1)
            ->where('students.data.0.notes_missing', 1));

    $this->actingAs($this->guardian)->get('/monthly-reports')
        ->assertInertia(fn ($page) => $page->where('staff', false)->has('students.data', 1)->where('students.data.0.name', 'Aldi'));

    $this->actingAs($this->admin)->get('/monthly-reports')
        ->assertInertia(fn ($page) => $page->has('students.data', 3));
});

test('guardians are notified once when last month report is ready', function () {
    $this->travelTo('2026-11-01 08:00');

    $this->artisan('reports:monthly')->assertSuccessful();
    $this->artisan('reports:monthly')->assertSuccessful();

    $notes = $this->guardian->notifications()->get();
    expect($notes)->toHaveCount(1)
        ->and($notes->first()->data['title'])->toBe("Aldi's learning report for October 2026")
        ->and($notes->first()->data['url'])->toEndWith("/monthly-reports/{$this->student->id}/2026-10");
});
