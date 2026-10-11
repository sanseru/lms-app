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

    // Sesi pagi ini sudah lewat dan belum diisi.
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

    Notify::send([$this->student, $inactive], 'Halo', key: 'k1');
    Notify::send($this->student, 'Halo', key: 'k1');

    expect($this->student->notifications()->count())->toBe(1)
        ->and($inactive->notifications()->count())->toBe(0);
});

test('invoice and payment flow notifies guardians and admins', function () {
    $invoice = Invoice::create([
        'number' => Invoice::nextNumber(), 'student_id' => $this->student->id, 'title' => 'SPP Oktober',
        'amount' => 350000, 'due_date' => now()->addDays(5),
    ]);
    expect(notificationTitles($this->guardian))->toContain("Tagihan baru {$invoice->number}");

    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 350000, 'paid_at' => today()->toDateString(), 'method' => 'transfer',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);
    expect(notificationTitles($this->admin))->toContain('Bukti transfer menunggu verifikasi');

    $payment = Payment::firstOrFail();
    $this->actingAs($this->admin)->post("/finance/payments/{$payment->id}/reject", ['reason' => 'Nominal tidak sesuai']);
    expect(notificationTitles($this->guardian))->toContain('Pembayaran ditolak');
});

test('completing a session sends a summary to guardians and homework to students once', function () {
    $session = $this->session;
    $report = ['topic' => 'Perulangan', 'homework' => 'Latihan 3', 'parent_note' => 'Aktif sekali',
        'attendances' => [['student_id' => $this->student->id, 'status' => 'present']]];

    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", $report)->assertRedirect();
    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", $report)->assertRedirect(); // koreksi

    $summary = $this->guardian->notifications()->get();
    expect($summary)->toHaveCount(1)
        ->and($summary->first()->data['title'])->toBe('Ringkasan sesi Python Start')
        ->and($summary->first()->data['body'])->toContain('Aktif sekali')
        ->and(notificationTitles($this->student))->toBe(['PR dari sesi Python Start']);
});

test('teachers are reminded about unreported sessions', function () {
    $this->artisan('sessions:remind-reports')->assertSuccessful();
    $this->artisan('sessions:remind-reports')->assertSuccessful();

    expect($this->teacher->notifications()->count())->toBe(1)
        ->and(notificationTitles($this->teacher)[0])->toBe('Laporan sesi Python Start belum diisi');
});

test('questions, answers, and graded assignments notify the right people', function () {
    $course = Course::create(['title' => 'Python', 'subject' => 'Python', 'level' => 'beginner', 'color' => 'violet', 'status' => 'published']);
    $lesson = $course->lessons()->create(['title' => 'Tugas 1', 'type' => 'assignment', 'position' => 1, 'is_published' => true]);

    $question = Question::create(['class_room_id' => $this->class->id, 'author_id' => $this->student->id, 'title' => 'Kenapa error?', 'body' => 'x', 'visibility' => 'class']);
    expect(notificationTitles($this->teacher))->toBe(['Pertanyaan baru di Python Start']);

    Answer::create(['question_id' => $question->id, 'author_id' => $this->teacher->id, 'body' => 'Cek indentasi', 'by_staff' => true]);
    expect(notificationTitles($this->student))->toBe(['Guru menjawab pertanyaanmu']);

    $submission = AssignmentSubmission::create(['lesson_id' => $lesson->id, 'student_id' => $this->student->id, 'body' => 'jawaban', 'status' => 'submitted', 'submitted_at' => now()]);
    $submission->update(['status' => 'graded', 'score' => 90, 'graded_by' => $this->teacher->id, 'graded_at' => now()]);

    expect(notificationTitles($this->student))->toContain('Tugas dinilai: Tugas 1')
        ->and(notificationTitles($this->guardian))->toContain('Tugas dinilai: Tugas 1');
});

test('users read their notifications and the bell shows the unread count', function () {
    Notify::send($this->student, 'Satu', url: '/dashboard');
    Notify::send($this->student, 'Dua');

    $this->actingAs($this->student)->get('/notifikasi')
        ->assertInertia(fn ($page) => $page->component('Notifications::Index')
            ->has('items.data', 2)
            ->where('notifications.unread', 2));

    $first = $this->student->notifications()->where('data->title', 'Satu')->first();
    $this->actingAs($this->student)->get("/notifikasi/{$first->id}")->assertRedirect('/dashboard');
    expect($first->fresh()->read_at)->not->toBeNull();

    // Notifikasi orang lain tidak bisa dibuka.
    $this->actingAs($this->guardian)->get("/notifikasi/{$first->id}")->assertNotFound();

    $this->actingAs($this->student)->post('/notifikasi/baca-semua');
    expect($this->student->unreadNotifications()->count())->toBe(0);
});
