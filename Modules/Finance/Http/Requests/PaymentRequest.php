<?php

namespace Modules\Finance\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $invoice = $this->route('invoice');

        // Dicek eksplisit karena admin melewati policy lewat Gate::before.
        return in_array($invoice->status, ['unpaid', 'partial'])
            && $this->user()->can('pay', $invoice);
    }

    public function rules(): array
    {
        $isAdmin = $this->user()->can('finance.manage');
        $invoice = $this->route('invoice');

        return [
            'amount' => ['required', 'integer', 'min:1000', 'max:'.$invoice->remaining()],
            'paid_at' => ['required', 'date', 'before_or_equal:today'],
            'method' => ['required', Rule::in(['transfer', 'cash'])],
            'bank_name' => ['nullable', 'string', 'max:60'],
            'account_name' => ['nullable', 'string', 'max:120'],
            'proof' => [$isAdmin ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:4096'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'amount' => 'nominal',
            'paid_at' => 'tanggal transfer',
            'proof' => 'bukti transfer',
            'bank_name' => 'bank',
            'account_name' => 'nama pemilik rekening',
        ];
    }

    public function messages(): array
    {
        return ['amount.max' => 'Nominal melebihi sisa tagihan.'];
    }
}
