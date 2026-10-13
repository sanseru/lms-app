<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Forum\Models\Answer;
use Modules\Forum\Models\Question;
use Modules\Learning\Models\AssignmentSubmission;
use Modules\Learning\Models\Course;
use Modules\Notifications\Support\Notify;
use Modules\Sessions\Models\ClassSession;

beforeEach(function () {
    Carbon::setTestNow('2026-10-07 12:00:00');
    Storage::fake('local');

    $this->admin = userWithRole('admin');
    $this->teacher = userWithRole('teacher');
    $this->student = userWithRole('student');
    $this->guardian = userWithRole('guardian');
    $this->guardian->children()->attach($this->student->id);

    $this->class = ClassRoom::create([
        'code' => 'NTF-001', 'name' => 'Python Start', 'subject' => 'Python', 'teacher_id' => $this->teacher->id,
        'schedule_days' => [3], 'start_time' => '08:00', 'end_time' => '09:00', 'status' => 'active',
    ]);
    $this->class->students()->attach($this->student->id);

    // This morning's session is over and not reported yet.
    $this->session = ClassSession::create([
        'class_room_id' => $this->class->id, 'teacher_id' => $this->teacher->id,
        'starts_at' => '2026-10-07 08:00', 'ends_at' => '2026-10-07 09:00',
    ]);
});

afterEach(fn () => Carbon::setTestNow());

function notificationTitles($user): array
{
    return $user->fresh()->notifications()->get()->pluck('data.title')->all();
}

test('notify skips duplicates with the same key and inactive users', function () {
    $inactive = userWithRole('student', ['status' => 'inactive']);

    Notify::send([$this->student, $inactive], 'Hello', key: 'k1');
    Notify::send($this->student, 'Hello', key: 'k1');

    expect($this->student->notifications()->count())->toBe(1)
        ->and($inactive->notifications()->count())->toBe(0);
});

test('invoice and payment flow notifies guardians and admins', function () {
    $invoice = Invoice::create([
        'number' => Invoice::nextNumber(), 'student_id' => $this->student->id, 'title' => 'Tuition October',
        'amount' => 350000, 'due_date' => now()->addDays(5),
    ]);
    expect(notificationTitles($this->guardian))->toContain("New invoice {$invoice->number}");

    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 350000, 'paid_at' => today()->toDateString(), 'method' => 'transfer',
        'proof' => UploadedFile::fake()->image('proof.jpg'),
    ]);
    expect(notificationTitles($this->admin))->toContain('Transfer proof waiting for verification');

    $payment = Payment::firstOrFail();
    $this->actingAs($this->admin)->post("/finance/payments/{$payment->id}/reject", ['reason' => 'Amount does not match']);
    expect(notificationTitles($this->guardian))->toContain('Payment rejected');
});

test('completing a session sends a summary to guardians and homework to students once', function () {
    $session = $this->session;
    $report = ['topic' => 'Loops', 'homework' => 'Exercise 3', 'parent_note' => 'Very active',
        'attendances' => [['student_id' => $this->student->id, 'status' => 'present']]];

    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", $report)->assertRedirect();
    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", $report)->assertRedirect(); // correction

    $summary = $this->guardian->notifications()->get();
    expect($summary)->toHaveCount(1)
        ->and($summary->first()->data['title'])->toBe('Session summary: Python Start')
        ->and($summary->first()->data['body'])->toContain('Very active')
        ->and(notificationTitles($this->student))->toBe(['Homework from Python Start']);
});

test('teachers are reminded about unreported sessions', function () {
    $this->artisan('sessions:remind-reports')->assertSuccessful();
    $this->artisan('sessions:remind-reports')->assertSuccessful();

    expect($this->teacher->notifications()->count())->toBe(1)
        ->and(notificationTitles($this->teacher)[0])->toBe('Session report for Python Start is not filled in yet');
});

test('questions, answers, and graded assignments notify the right people', function () {
    $course = Course::create(['title' => 'Python', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lesson = $course->lessons()->create(['title' => 'Assignment 1', 'type' => 'assignment', 'position' => 1, 'is_published' => true]);

    $question = Question::create(['class_room_id' => $this->class->id, 'author_id' => $this->student->id, 'title' => 'Why the error?', 'body' => 'x', 'visibility' => 'class']);
    expect(notificationTitles($this->teacher))->toBe(['New question in Python Start']);

    Answer::create(['question_id' => $question->id, 'author_id' => $this->teacher->id, 'body' => 'Check the indentation', 'by_staff' => true]);
    expect(notificationTitles($this->student))->toBe(['Your teacher answered your question']);

    $submission = AssignmentSubmission::create(['lesson_id' => $lesson->id, 'student_id' => $this->student->id, 'body' => 'my answer', 'status' => 'submitted', 'submitted_at' => now()]);
    $submission->update(['status' => 'graded', 'score' => 90, 'graded_by' => $this->teacher->id, 'graded_at' => now()]);

    expect(notificationTitles($this->student))->toContain('Assignment graded: Assignment 1')
        ->and(notificationTitles($this->guardian))->toContain('Assignment graded: Assignment 1');
});

test('users read their notifications and the bell shows the unread count', function () {
    Notify::send($this->student, 'One', url: '/dashboard');
    Notify::send($this->student, 'Two');

    $this->actingAs($this->student)->get('/notifications')
        ->assertInertia(fn ($page) => $page->component('Notifications::Index')
            ->has('items.data', 2)
            ->where('notifications.unread', 2));

    // `data` is a text column (Laravel default), so filter in PHP rather than with a JSON path that PostgreSQL rejects.
    $first = $this->student->notifications()->get()->firstWhere(fn ($n) => $n->data['title'] === 'One');
    $this->actingAs($this->student)->get("/notifications/{$first->id}")->assertRedirect('/dashboard');
    expect($first->fresh()->read_at)->not->toBeNull();

    // Other people's notifications cannot be opened.
    $this->actingAs($this->guardian)->get("/notifications/{$first->id}")->assertNotFound();

    $this->actingAs($this->student)->post('/notifications/read-all');
    expect($this->student->unreadNotifications()->count())->toBe(0);
});
