<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Package;
use Modules\Finance\Models\PayrollItem;
use Modules\Finance\Models\StudentPackage;
use Modules\Finance\Models\TeacherPayout;

beforeEach(function () {
    Carbon::setTestNow('2026-10-07 12:00:00');
    Storage::fake('local');

    $this->admin = userWithRole('admin');
    $this->teacher = userWithRole('teacher');
    $this->teacher->teacherProfile()->create(['employee_code' => 'GRU-1', 'rate_per_session' => 120000]);
    $this->student = userWithRole('student');
    $this->student->studentProfile()->create(['student_code' => 'SIS-1']);
    $this->package = Package::create(['name' => 'Reguler', 'sessions_count' => 2, 'price' => 200000, 'valid_days' => 30]);

    $this->class = ClassRoom::create([
        'code' => 'PYT-001', 'name' => 'Python Start', 'subject' => 'Python', 'teacher_id' => $this->teacher->id,
        'schedule_days' => [1, 3, 5], 'start_time' => '16:00', 'end_time' => '17:00', 'status' => 'active',
    ]);
    $this->class->students()->attach($this->student->id);
});

afterEach(fn () => Carbon::setTestNow());

function completeNext($test, string $status = 'present')
{
    $session = $test->class->sessions()->where('status', 'scheduled')->orderBy('starts_at')->first();
    $test->actingAs($test->teacher)->put("/sessions/{$session->id}", [
        'topic' => 'Materi', 'attendances' => [['student_id' => $test->student->id, 'status' => $status]],
    ]);

    return $session->fresh();
}

test('selling a package creates an invoice and activates the package once paid', function () {
    $this->actingAs($this->admin)->post('/finance/packages/assign', [
        'package_id' => $this->package->id, 'student_id' => $this->student->id, 'due_date' => '2026-10-14',
    ])->assertSessionHas('success');

    $studentPackage = StudentPackage::firstOrFail();
    $invoice = Invoice::firstOrFail();
    expect($studentPackage->status)->toBe('pending_payment')
        ->and($invoice->amount)->toBe(200000);

    $this->actingAs($this->admin)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 200000, 'paid_at' => '2026-10-07', 'method' => 'transfer',
    ]);

    expect($studentPackage->fresh())
        ->status->toBe('active')
        ->starts_at->toDateString()->toBe('2026-10-07')
        ->expires_at->toDateString()->toBe('2026-11-06');
});

test('free trial packages are active immediately without an invoice', function () {
    $trial = Package::create(['name' => 'Trial', 'sessions_count' => 1, 'price' => 0, 'valid_days' => 14]);

    $this->actingAs($this->admin)->post('/finance/packages/assign', [
        'package_id' => $trial->id, 'student_id' => $this->student->id, 'due_date' => '2026-10-14',
    ]);

    expect(Invoice::count())->toBe(0)
        ->and(StudentPackage::first()->status)->toBe('active');
});

test('completed sessions use up the quota and excused ones give it back', function () {
    $sp = StudentPackage::create([
        'student_id' => $this->student->id, 'package_id' => $this->package->id, 'name' => 'Reguler',
        'sessions_total' => 2, 'price' => 200000, 'valid_days' => 30,
    ]);
    $sp->activate();

    $first = completeNext($this);
    expect($sp->fresh()->remaining())->toBe(1);

    // Koreksi: ternyata izin, kuota kembali.
    $this->actingAs($this->teacher)->put("/sessions/{$first->id}", [
        'topic' => 'Materi', 'attendances' => [['student_id' => $this->student->id, 'status' => 'excused']],
    ]);
    expect($sp->fresh()->remaining())->toBe(2);

    // Masuk sesi pertama lagi lalu dua sesi berikutnya: kuota habis, sesi ketiga tidak tercatat.
    $this->actingAs($this->teacher)->put("/sessions/{$first->id}", [
        'topic' => 'Materi', 'attendances' => [['student_id' => $this->student->id, 'status' => 'present']],
    ]);
    completeNext($this);
    $third = completeNext($this);

    expect($sp->fresh())->status->toBe('used_up')
        ->and($sp->fresh()->remaining())->toBe(0)
        ->and($third->attendances()->first()->student_package_id)->toBeNull();
});

test('class specific packages are used before general ones', function () {
    $general = StudentPackage::create(['student_id' => $this->student->id, 'name' => 'Umum', 'sessions_total' => 5, 'price' => 0, 'valid_days' => 30]);
    $general->activate(now()->subDays(10));
    $specific = StudentPackage::create(['student_id' => $this->student->id, 'class_room_id' => $this->class->id, 'name' => 'Python', 'sessions_total' => 5, 'price' => 0, 'valid_days' => 30]);
    $specific->activate();

    $session = completeNext($this);

    expect($session->attendances()->first()->student_package_id)->toBe($specific->id);
});

test('expired packages are not used', function () {
    $sp = StudentPackage::create(['student_id' => $this->student->id, 'name' => 'Lama', 'sessions_total' => 5, 'price' => 0, 'valid_days' => 5]);
    $sp->activate(now()->subDays(30));

    $session = completeNext($this);

    expect($session->attendances()->first()->student_package_id)->toBeNull();
});

test('each completed session records one tutor fee at the teacher rate', function () {
    $session = completeNext($this);
    // Simpan ulang (koreksi) tidak menggandakan honor.
    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", ['topic' => 'Revisi', 'attendances' => []]);

    expect(PayrollItem::count())->toBe(1)
        ->and(PayrollItem::first())->amount->toBe(120000)->teacher_id->toBe($this->teacher->id);
});

test('admin bundles a month of fees into a slip and marks it paid', function () {
    completeNext($this);
    completeNext($this);

    $this->actingAs($this->admin)->get('/finance/payroll?month=2026-10')
        ->assertInertia(fn ($page) => $page->where('unpaid.0.sessions_count', 2)->where('unpaid.0.total', 240000));

    $this->actingAs($this->admin)->post('/finance/payroll', [
        'teacher_id' => $this->teacher->id, 'month' => '2026-10', 'bonus' => 50000, 'deduction' => 10000,
    ])->assertRedirect();

    $payout = TeacherPayout::firstOrFail();
    expect($payout)->total->toBe(280000)->sessions_count->toBe(2)
        ->and(PayrollItem::whereNull('teacher_payout_id')->count())->toBe(0);

    // Slip kedua untuk periode yang sama tidak punya item lagi.
    $this->actingAs($this->admin)->post('/finance/payroll', ['teacher_id' => $this->teacher->id, 'month' => '2026-10'])
        ->assertSessionHas('error');

    $this->actingAs($this->admin)->post("/finance/payroll/{$payout->id}/paid", [
        'paid_at' => '2026-10-07', 'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ])->assertSessionHas('success');

    expect($payout->fresh()->status)->toBe('paid');
    $this->actingAs($this->teacher)->get("/finance/payroll/{$payout->id}/proof")->assertOk();
});

test('teachers see only their own fees and slips', function () {
    completeNext($this);
    $this->actingAs($this->admin)->post('/finance/payroll', ['teacher_id' => $this->teacher->id, 'month' => '2026-10']);
    $payout = TeacherPayout::firstOrFail();

    $this->actingAs($this->teacher)->get('/finance/my-fees')->assertOk()
        ->assertInertia(fn ($page) => $page->component('Finance::Payroll/Mine')->has('payouts', 1));
    $this->actingAs($this->teacher)->get("/finance/payroll/{$payout->id}")->assertOk();

    $other = userWithRole('teacher');
    $this->actingAs($other)->get("/finance/payroll/{$payout->id}")->assertForbidden();
    $this->actingAs($this->teacher)->get('/finance/payroll')->assertForbidden();
});

test('deleting an unpaid slip returns sessions to the unbundled list', function () {
    completeNext($this);
    $this->actingAs($this->admin)->post('/finance/payroll', ['teacher_id' => $this->teacher->id, 'month' => '2026-10']);
    $payout = TeacherPayout::firstOrFail();

    $this->actingAs($this->admin)->delete("/finance/payroll/{$payout->id}")->assertRedirect();

    expect(TeacherPayout::count())->toBe(0)
        ->and(PayrollItem::whereNull('teacher_payout_id')->count())->toBe(1);
});

test('guardian dashboard shows remaining quota and the last session note', function () {
    $guardian = userWithRole('guardian');
    $guardian->children()->attach($this->student->id);
    $sp = StudentPackage::create(['student_id' => $this->student->id, 'name' => 'Reguler', 'sessions_total' => 8, 'price' => 0, 'valid_days' => 30]);
    $sp->activate();

    $session = $this->class->sessions()->orderBy('starts_at')->first();
    $this->actingAs($this->teacher)->put("/sessions/{$session->id}", [
        'topic' => 'Fungsi', 'parent_note' => 'Hebat!', 'internal_note' => 'rahasia',
        'attendances' => [['student_id' => $this->student->id, 'status' => 'present']],
    ]);

    $this->actingAs($guardian)->get('/dashboard')
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard::Guardian')
            ->where('children.0.packages.0.remaining', 7)
            ->where('children.0.lastSession.topic', 'Fungsi')
            ->where('children.0.lastSession.parent_note', 'Hebat!'));

    $this->actingAs($guardian)->get("/sessions/{$session->id}")
        ->assertInertia(fn ($page) => $page->where('session.internal_note', null));
});
