<?php

use Illuminate\Support\Carbon;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Models\ClassSession;

beforeEach(function () {
    Carbon::setTestNow('2026-10-07 12:00:00'); // Rabu

    $this->teacher = userWithRole('teacher');
    $this->teacher->teacherProfile()->create(['employee_code' => 'GRU-1', 'rate_per_session' => 100000]);
    $this->student = userWithRole('student');
    $this->student->studentProfile()->create(['student_code' => 'SIS-1']);

    $this->class = ClassRoom::create([
        'code' => 'PYT-001', 'name' => 'Python Start', 'subject' => 'Python', 'teacher_id' => $this->teacher->id,
        'schedule_days' => [1, 3], 'start_time' => '16:00', 'end_time' => '17:30', 'status' => 'active',
        'zoom_url' => 'https://zoom.us/j/1',
    ]);
    $this->class->students()->attach($this->student->id);
});

afterEach(fn () => Carbon::setTestNow());

function sessionReport(array $overrides = [], array $attendances = []): array
{
    return $overrides + ['topic' => 'Perulangan', 'attendances' => $attendances];
}

test('creating a class generates upcoming sessions from its weekly schedule', function () {
    // 7 Okt s/d 4 Nov: 5 Rabu + 4 Senin. Sesi hari ini pukul 16:00 belum lewat.
    expect($this->class->sessions()->count())->toBe(9)
        ->and($this->class->sessions()->orderBy('starts_at')->first()->starts_at->toDateTimeString())->toBe('2026-10-07 16:00:00');
});

test('changing the schedule rebuilds future sessions only', function () {
    $past = ClassSession::create(['class_room_id' => $this->class->id, 'teacher_id' => $this->teacher->id, 'starts_at' => '2026-10-05 16:00', 'ends_at' => '2026-10-05 17:30']);

    $this->class->update(['schedule_days' => [6]]);

    expect($past->fresh())->not->toBeNull()
        ->and($this->class->sessions()->where('starts_at', '>', now())->get()->every(fn ($s) => $s->starts_at->isSaturday()))->toBeTrue();
});

test('teacher completes a session with attendance and notes in one step', function () {
    $session = $this->class->sessions()->orderBy('starts_at')->first();

    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", sessionReport(
        ['homework' => 'Latihan 3', 'parent_note' => 'Aktif sekali', 'flagged' => true],
        [['student_id' => $this->student->id, 'status' => 'present']],
    ))->assertRedirect(route('sessions.show', $session));

    $session->refresh();
    expect($session->status)->toBe('completed')
        ->and($session->topic)->toBe('Perulangan')
        ->and($session->flagged)->toBeTrue()
        ->and($session->attendances()->first()->status)->toBe('present')
        ->and($session->attendances()->first()->counts_quota)->toBeTrue();
});

test('excused absence does not count against the quota', function () {
    $session = $this->class->sessions()->first();

    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", sessionReport([], [['student_id' => $this->student->id, 'status' => 'excused']]));

    expect($session->attendances()->first()->counts_quota)->toBeFalse();
});

test('students outside the class are ignored in attendance', function () {
    $stranger = userWithRole('student');
    $session = $this->class->sessions()->first();

    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", sessionReport([], [['student_id' => $stranger->id, 'status' => 'present']]));

    expect($session->attendances()->count())->toBe(0);
});

test('only the session teacher or admin can fill the report', function () {
    $session = $this->class->sessions()->first();

    $this->actingAs(userWithRole('teacher'))->put("/sessions/{$session->id}", sessionReport())->assertForbidden();
    $this->actingAs($this->student)->put("/sessions/{$session->id}", sessionReport())->assertForbidden();
    $this->actingAs(userWithRole('admin'))->put("/sessions/{$session->id}", sessionReport())->assertRedirect();
});

test('cancelled sessions cannot be reported', function () {
    $session = $this->class->sessions()->first();
    $admin = userWithRole('admin');

    $this->actingAs($admin)->post("/sessions/{$session->id}/cancel", ['reason' => 'Libur'])->assertSessionHas('success');
    $this->actingAs($admin)->put("/sessions/{$session->id}", sessionReport())->assertForbidden();

    expect($session->fresh()->status)->toBe('cancelled');
});

test('past unreported sessions appear in the needs report list', function () {
    Carbon::setTestNow('2026-10-08 09:00:00');

    $this->actingAs($this->teacher)->get('/sessions')
        ->assertInertia(fn ($page) => $page->component('Sessions::Sessions/Index')->where('counts.needs_report', 1));
});

test('students and guardians can read a session but not other classes sessions', function () {
    $session = $this->class->sessions()->first();
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($this->student->id);

    $this->actingAs($this->student)->get("/sessions/{$session->id}")->assertOk()->assertInertia(fn ($page) => $page->where('can.report', false));
    $this->actingAs($guardian)->get("/sessions/{$session->id}")->assertOk();
    $this->actingAs(userWithRole('student'))->get("/sessions/{$session->id}")->assertForbidden();
    $this->actingAs($this->student)->get('/sessions')->assertForbidden();
});

test('weekly schedule shows generated sessions', function () {
    $this->actingAs($this->student)->get('/schedule?week=2026-10-05')
        ->assertInertia(fn ($page) => $page
            ->has('days.0.sessions', 0) // Senin 5 Okt sudah lewat saat kelas dibuat
            ->has('days.2.sessions', 1));
});

test('enrolling a student into a clashing class is rejected', function () {
    $other = ClassRoom::create([
        'code' => 'SCR-001', 'name' => 'Scratch', 'subject' => 'Scratch', 'schedule_days' => [3],
        'start_time' => '17:00', 'end_time' => '18:00', 'status' => 'active',
    ]);

    $this->actingAs(userWithRole('admin'))->post("/classes/{$other->id}/students", ['student_ids' => [$this->student->id]])
        ->assertSessionHas('error');

    expect($other->students()->count())->toBe(0);
});

test('teacher moves a session and the replacement links back to the original', function () {
    $session = $this->class->sessions()->orderBy('starts_at')->first(); // Rabu 7 Okt 16:00

    $this->actingAs($this->teacher)->post("/sessions/{$session->id}/reschedule", [
        'date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:30', 'reason' => 'Siswa ujian sekolah',
    ])->assertRedirect();

    $replacement = ClassSession::where('replaces_session_id', $session->id)->firstOrFail();
    expect($session->fresh()->status)->toBe('rescheduled')
        ->and($session->fresh()->cancel_reason)->toBe('Siswa ujian sekolah')
        ->and($replacement->starts_at->toDateTimeString())->toBe('2026-10-10 09:00:00')
        ->and($replacement->teacher_id)->toBe($this->teacher->id)
        ->and($replacement->status)->toBe('scheduled');

    // Sesi yang sudah dipindah tidak bisa dipindah lagi atau diisi absensinya.
    $this->actingAs($this->teacher)->post("/sessions/{$session->id}/reschedule", [
        'date' => '2026-10-11', 'start_time' => '09:00', 'end_time' => '10:30', 'reason' => 'Lagi',
    ])->assertSessionHasErrors('date');
    $this->actingAs(userWithRole('admin'))->put("/sessions/{$session->id}", sessionReport())->assertForbidden();

    $this->actingAs($this->student)->get("/sessions/{$replacement->id}")
        ->assertInertia(fn ($page) => $page->where('session.replaces.id', $session->id));
});

test('replacement sessions survive a class schedule change and are not regenerated twice', function () {
    $session = $this->class->sessions()->orderBy('starts_at')->first();

    $this->actingAs($this->teacher)->post("/sessions/{$session->id}/reschedule", [
        'date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:30', 'reason' => 'Pindah',
    ]);

    $this->artisan('sessions:generate')->assertSuccessful();
    expect(ClassSession::where('class_room_id', $this->class->id)->where('starts_at', '2026-10-07 16:00:00')->count())->toBe(1);

    $this->class->update(['schedule_days' => [5]]);
    expect(ClassSession::where('replaces_session_id', $session->id)->exists())->toBeTrue();
});

test('cancelled sessions can get a makeup session but clashing times are rejected', function () {
    $admin = userWithRole('admin');
    $session = $this->class->sessions()->orderBy('starts_at')->first();
    $this->actingAs($admin)->post("/sessions/{$session->id}/cancel", ['reason' => 'Guru sakit']);

    // Senin 12 Okt 16:00 sudah ada sesi kelas ini untuk guru yang sama.
    $this->actingAs($admin)->post("/sessions/{$session->id}/reschedule", [
        'date' => '2026-10-12', 'start_time' => '16:30', 'end_time' => '17:30', 'reason' => 'Pengganti',
    ])->assertSessionHasErrors('start_time');

    $this->actingAs($admin)->post("/sessions/{$session->id}/reschedule", [
        'date' => '2026-10-13', 'start_time' => '16:00', 'end_time' => '17:30', 'reason' => 'Pengganti',
    ])->assertRedirect();

    expect($session->fresh()->status)->toBe('cancelled')
        ->and($session->replacement()->exists())->toBeTrue();
});

test('only the session teacher or admin can reschedule', function () {
    $session = $this->class->sessions()->first();
    $payload = ['date' => '2026-10-10', 'start_time' => '09:00', 'end_time' => '10:00', 'reason' => 'x'];

    $this->actingAs(userWithRole('teacher'))->post("/sessions/{$session->id}/reschedule", $payload)->assertForbidden();
    $this->actingAs($this->student)->post("/sessions/{$session->id}/reschedule", $payload)->assertForbidden();
});

test('users get a private iCal feed of their sessions', function () {
    $this->actingAs($this->student)->post('/kalender')->assertSessionHas('success');
    $token = $this->student->fresh()->calendar_token;

    $ics = $this->get("/kalender/{$token}.ics")
        ->assertOk()
        ->assertHeader('Content-Type', 'text/calendar; charset=utf-8')
        ->getContent();

    expect($ics)->toContain('BEGIN:VCALENDAR')
        ->and(substr_count($ics, 'BEGIN:VEVENT'))->toBe(9)
        ->and($ics)->toContain('DTSTART:20261007T090000Z') // 16:00 WIB
        ->and($ics)->toContain('SUMMARY:Python Start');

    // Siswa lain tidak melihat sesi kelas ini; token salah ditolak.
    $other = userWithRole('student');
    $this->actingAs($other)->post('/kalender');
    expect(substr_count($this->get('/kalender/'.$other->fresh()->calendar_token.'.ics')->getContent(), 'BEGIN:VEVENT'))->toBe(0);
    $this->get('/kalender/'.str_repeat('a', 40).'.ics')->assertNotFound();
});
