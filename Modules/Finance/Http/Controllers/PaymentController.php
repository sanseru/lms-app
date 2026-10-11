<?php

namespace Modules\Finance\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Modules\Finance\Http\Requests\PaymentRequest;
use Modules\Finance\Models\Invoice;
use Modules\Finance\Models\Payment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PaymentController extends Controller
{
    /**
     * A parent uploads transfer proof (pending verification),
     * or an admin records a payment that is verified straight away.
     */
    public function store(PaymentRequest $request, Invoice $invoice): RedirectResponse
    {
        $isAdmin = $request->user()->can('finance.manage');
        $data = $request->validated();

        DB::transaction(function () use ($request, $invoice, $data, $isAdmin) {
            $invoice->payments()->create([
                ...collect($data)->except('proof')->all(),
                'proof_path' => $request->file('proof')?->store("payment-proofs/{$invoice->id}", 'local'),
                'status' => $isAdmin ? 'verified' : 'pending',
                'submitted_by' => $request->user()->id,
                'verified_by' => $isAdmin ? $request->user()->id : null,
                'verified_at' => $isAdmin ? now() : null,
            ]);

            $invoice->recalculate();
        });

        return back()->with('success', $isAdmin
            ? 'Payment recorded and invoice updated.'
            : 'Transfer proof sent. An admin will verify your payment.');
    }

    public function verify(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422);

        DB::transaction(function () use ($request, $payment) {
            $payment->update(['status' => 'verified', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'rejection_reason' => null]);
            $payment->invoice->recalculate();
        });

        return back()->with('success', 'Payment verified.');
    }

    public function reject(Request $request, Payment $payment): RedirectResponse
    {
        abort_unless($payment->status === 'pending', 422);
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']], [], ['reason' => 'reason']);

        $payment->update(['status' => 'rejected', 'verified_by' => $request->user()->id, 'verified_at' => now(), 'rejection_reason' => $data['reason']]);

        return back()->with('success', 'Payment rejected.');
    }

    public function proof(Payment $payment): StreamedResponse
    {
        Gate::authorize('view', $payment->invoice);
        abort_unless($payment->proof_path && Storage::disk('local')->exists($payment->proof_path), 404);

        return Storage::disk('local')->response($payment->proof_path);
    }
}
