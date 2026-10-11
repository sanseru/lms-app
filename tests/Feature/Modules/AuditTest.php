<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Audit\Models\Activity;
use Modules\Audit\Support\AuditTrail;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = userWithRole('admin', ['name' => 'Admin Kantor']);
    $this->student = userWithRole('student', ['name' => 'Aldi']);
    $this->guardian = userWithRole('guardian');
    $this->guardian->children()->attach($this->student->id);
});

test('verifying a payment leaves a trail of who changed the invoice', function () {
    $invoice = Invoice::create([
        'number' => Invoice::nextNumber(), 'student_id' => $this->student->id, 'title' => 'SPP Oktober',
        'amount' => 350000, 'due_date' => now()->addDays(5),
    ]);

    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 350000, 'paid_at' => today()->toDateString(), 'method' => 'transfer', 'bank_name' => 'BCA',
        'proof' => UploadedFile::fake()->image('bukti.jpg'),
    ]);
    $payment = Payment::firstOrFail();
    $this->actingAs($this->admin)->post("/finance/payments/{$payment->id}/verify")->assertSessionHas('success');

    $verified = Activity::where('category', 'Pembayaran')->where('event', 'updated')->firstOrFail();
    expect($verified->causer_id)->toBe($this->admin->id)
        ->and($verified->changes['status'])->toBe(['Menunggu verifikasi', 'Terverifikasi'])
        ->and($verified->subject_label)->toContain($invoice->number);

    $paid = Activity::where('category', 'Tagihan')->where('event', 'updated')->firstOrFail();
    expect($paid->changes['status'][1])->toBe('Lunas')
        ->and($paid->subject_label)->toBe("{$invoice->number} · Aldi");
});

test('passwords are never stored, only that they changed', function () {
    $this->actingAs($this->admin);
    $this->student->update(['password' => 'rahasia-baru-123']);

    $log = Activity::where('subject_id', $this->student->id)->where('event', 'updated')->firstOrFail();
    expect($log->changes['password'])->toBe(['••••', '••••'])
        ->and(json_encode($log->changes))->not->toContain('rahasia');
});

test('untouched fields do not create noise', function () {
    $this->student->update(['last_login_at' => now()]);

    expect(Activity::where('event', 'updated')->count())->toBe(0);
});

test('logins and failed logins are recorded', function () {
    userWithRole('student', ['username' => 'nadia']);

    $this->post('/login', ['login' => 'nadia', 'password' => 'salah']);
    $this->post('/login', ['login' => 'nadia', 'password' => 'password']);

    expect(Activity::where('event', 'login_failed')->value('subject_label'))->toBe('nadia')
        ->and(Activity::where('event', 'login')->whereNotNull('causer_id')->count())->toBe(1);
});

test('the trail is append only', function () {
    $log = Activity::create(['event' => 'login', 'category' => 'Akun']);

    expect(fn () => $log->update(['category' => 'Lain']))->toThrow(LogicException::class)
        ->and(fn () => $log->delete())->toThrow(LogicException::class);
});

test('recording can be paused for seeding', function () {
    app(AuditTrail::class)->withoutRecording(fn () => userWithRole('student'));

    expect(Activity::where('event', 'created')->where('category', 'Akun')->count())->toBe(3);
});

test('only admins can read the trail and filters work', function () {
    $this->actingAs($this->admin);
    $this->student->update(['phone' => '0812']);
    $this->guardian->update(['phone' => '0813']);

    $this->actingAs($this->admin)->get('/audit?search=Aldi')
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Audit::Index')
            ->has('activities.data', 2) // dibuat + diubah
            ->where('activities.data.0.changes.0.to', '0812')
            ->where('activities.data.0.causer', 'Admin Kantor'));

    $this->actingAs($this->admin)->get('/audit?event=updated&category=Akun')
        ->assertInertia(fn ($page) => $page->has('activities.data', 2));

    $this->actingAs($this->guardian)->get('/audit')->assertForbidden();
    $this->actingAs($this->student)->get('/audit')->assertForbidden();
});

test('status values are stored with their Indonesian labels', function () {
    $invoice = Invoice::create([
        'number' => Invoice::nextNumber(), 'student_id' => $this->student->id, 'title' => 'SPP',
        'amount' => 100000, 'due_date' => now(),
    ]);
    $invoice->fresh()->update(['status' => 'cancelled']); // seperti request nyata: dimuat ulang dari database

    expect(Activity::where('event', 'updated')->firstOrFail()->changes['status'])->toBe(['Belum bayar', 'Dibatalkan']);
});
