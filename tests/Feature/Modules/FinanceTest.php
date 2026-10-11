<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Classes\Models\ClassRoom;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Modules\Finance\Models\StudentPackage;
use Modules\Finance\Support\AmountInWords;

beforeEach(function () {
    Storage::fake('local');
    $this->admin = userWithRole('admin');
    $this->student = userWithRole('student');
    $this->student->studentProfile()->create(['student_code' => 'SIS-00001']);
    $this->guardian = userWithRole('guardian');
    $this->guardian->children()->attach($this->student->id);
});

function invoiceFor($student, int $amount = 350000): Invoice
{
    return Invoice::create([
        'number' => Invoice::nextNumber(),
        'student_id' => $student->id,
        'title' => 'October tuition',
        'amount' => $amount,
        'due_date' => now()->addDays(5),
    ]);
}

test('admin can bill every student in a class at once', function () {
    $other = userWithRole('student');
    $class = ClassRoom::create([
        'code' => 'PYT-001', 'name' => 'Python', 'subject' => 'Python', 'schedule_days' => [1],
        'start_time' => '10:00', 'end_time' => '11:00',
    ]);
    $class->students()->attach([$this->student->id => ['status' => 'active'], $other->id => ['status' => 'active']]);

    $this->actingAs($this->admin)->post('/finance/invoices', [
        'target' => 'class', 'class_room_id' => $class->id, 'title' => 'October 2026 tuition',
        'period' => '2026-10', 'amount' => 350000, 'due_date' => '2026-10-10',
    ])->assertRedirect(route('finance.invoices.index'));

    expect(Invoice::count())->toBe(2)
        ->and(Invoice::pluck('number')->unique())->toHaveCount(2)
        ->and(Invoice::first()->period->toDateString())->toBe('2026-10-01');
});

test('guardian uploads transfer proof and admin verifies it', function () {
    $invoice = invoiceFor($this->student);

    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 350000, 'paid_at' => today()->toDateString(), 'method' => 'transfer', 'bank_name' => 'BCA',
        'proof' => UploadedFile::fake()->image('proof.jpg'),
    ])->assertSessionHas('success');

    $payment = Payment::firstOrFail();
    expect($payment->status)->toBe('pending')
        ->and($invoice->fresh()->status)->toBe('unpaid');
    Storage::disk('local')->assertExists($payment->proof_path);

    $this->actingAs($this->guardian)->get("/finance/payments/{$payment->id}/proof")->assertOk();

    $this->actingAs($this->admin)->post("/finance/payments/{$payment->id}/verify")->assertSessionHas('success');

    expect($invoice->fresh())
        ->status->toBe('paid')
        ->paid_amount->toBe(350000);
});

test('guardian must attach proof of transfer', function () {
    $invoice = invoiceFor($this->student);

    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 350000, 'paid_at' => today()->toDateString(), 'method' => 'transfer',
    ])->assertSessionHasErrors('proof');
});

test('partial payments and rejections update the invoice correctly', function () {
    $invoice = invoiceFor($this->student, 300000);

    $this->actingAs($this->admin)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 100000, 'paid_at' => today()->toDateString(), 'method' => 'cash',
    ]);
    expect($invoice->fresh())->status->toBe('partial')->paid_amount->toBe(100000);

    $this->actingAs($this->admin)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 250000, 'paid_at' => today()->toDateString(), 'method' => 'cash',
    ])->assertSessionHasErrors('amount');

    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 200000, 'paid_at' => today()->toDateString(), 'method' => 'transfer', 'proof' => UploadedFile::fake()->create('proof.pdf', 100, 'application/pdf'),
    ]);
    $pending = Payment::where('status', 'pending')->firstOrFail();

    $this->actingAs($this->admin)->post("/finance/payments/{$pending->id}/reject", ['reason' => 'Amount not received']);

    expect($pending->fresh()->status)->toBe('rejected')
        ->and($invoice->fresh())->status->toBe('partial')->paid_amount->toBe(100000);
});

test('guardians only see and pay their own children invoices', function () {
    $mine = invoiceFor($this->student);
    $stranger = userWithRole('student');
    $theirs = invoiceFor($stranger);

    $this->actingAs($this->guardian)->get('/finance/invoices')
        ->assertInertia(fn ($page) => $page->has('invoices.data', 1)->where('invoices.data.0.id', $mine->id));

    $this->actingAs($this->guardian)->get("/finance/invoices/{$theirs->id}")->assertForbidden();
    $this->actingAs($this->guardian)->post("/finance/invoices/{$theirs->id}/payments", [
        'amount' => 350000, 'paid_at' => today()->toDateString(), 'method' => 'transfer', 'proof' => UploadedFile::fake()->image('x.jpg'),
    ])->assertForbidden();
});

test('guardians cannot verify payments or pay a settled invoice', function () {
    $invoice = invoiceFor($this->student);
    $payment = $invoice->payments()->create(['amount' => 350000, 'paid_at' => today(), 'status' => 'pending']);

    $this->actingAs($this->guardian)->post("/finance/payments/{$payment->id}/verify")->assertForbidden();

    $invoice->update(['status' => 'paid', 'paid_amount' => 350000]);
    $this->actingAs($this->guardian)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 1000, 'paid_at' => today()->toDateString(), 'method' => 'transfer', 'proof' => UploadedFile::fake()->image('x.jpg'),
    ])->assertForbidden();
});

test('admin cannot record payment on a paid invoice either', function () {
    $invoice = invoiceFor($this->student);
    $invoice->update(['status' => 'paid', 'paid_amount' => 350000]);

    $this->actingAs($this->admin)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 1000, 'paid_at' => today()->toDateString(), 'method' => 'cash',
    ])->assertForbidden();
});

test('finance overview summarises outstanding and pending payments', function () {
    $invoice = invoiceFor($this->student);
    $invoice->payments()->create(['amount' => 350000, 'paid_at' => today(), 'status' => 'pending']);

    $this->actingAs($this->admin)->get('/finance')
        ->assertInertia(fn ($page) => $page
            ->component('Finance::Overview')
            ->where('stats.outstanding', 350000)
            ->where('stats.pending_count', 1)
            ->has('months', 6));
});

test('guardian can print a receipt once a payment is verified', function () {
    $invoice = invoiceFor($this->student, 1250000);

    $this->actingAs($this->guardian)->get("/finance/invoices/{$invoice->id}/receipt")->assertNotFound();

    $this->actingAs($this->admin)->post("/finance/invoices/{$invoice->id}/payments", [
        'amount' => 1250000, 'paid_at' => today()->toDateString(), 'method' => 'cash',
    ]);

    $this->actingAs($this->guardian)->get("/finance/invoices/{$invoice->id}/receipt")
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Finance::Invoices/Receipt')
            ->where('receipt.number', "RCP-{$invoice->number}")
            ->where('receipt.paid_in_full', true)
            ->where('receipt.in_words', 'One million two hundred fifty thousand rupiah')
            ->where('receipt.payer', $this->guardian->name));

    $this->actingAs(userWithRole('guardian'))->get("/finance/invoices/{$invoice->id}/receipt")->assertForbidden();
});

test('amounts are spelled out in English for receipts', function (int $amount, string $words) {
    expect(AmountInWords::rupiah($amount))->toBe($words);
})->with([
    [0, 'Zero rupiah'],
    [11, 'Eleven rupiah'],
    [21, 'Twenty-one rupiah'],
    [115, 'One hundred fifteen rupiah'],
    [1000, 'One thousand rupiah'],
    [150000, 'One hundred fifty thousand rupiah'],
    [350000, 'Three hundred fifty thousand rupiah'],
    [2001500, 'Two million one thousand five hundred rupiah'],
    [1000000000, 'One billion rupiah'],
]);

test('finance overview lists packages that are almost used up', function () {
    StudentPackage::create([
        'student_id' => $this->student->id, 'name' => '8-session package', 'sessions_total' => 2, 'price' => 0,
        'valid_days' => 90, 'status' => 'active', 'starts_at' => today(), 'expires_at' => today()->addDays(90),
    ]);
    StudentPackage::create([
        'student_id' => userWithRole('student')->id, 'name' => '16-session package', 'sessions_total' => 16, 'price' => 0,
        'valid_days' => 90, 'status' => 'active', 'starts_at' => today(), 'expires_at' => today()->addDays(90),
    ]);

    $this->actingAs($this->admin)->get('/finance')
        ->assertInertia(fn ($page) => $page->has('lowQuota', 1)->where('lowQuota.0.student.id', $this->student->id));
});
