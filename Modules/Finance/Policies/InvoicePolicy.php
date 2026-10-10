<?php

namespace Modules\Finance\Policies;

use App\Models\User;
use Modules\Finance\Models\Invoice;

class InvoicePolicy
{
    public function view(User $user, Invoice $invoice): bool
    {
        return $user->can('finance.manage') || $this->isGuardianOf($user, $invoice);
    }

    /** Orang tua mengunggah bukti transfer. */
    public function pay(User $user, Invoice $invoice): bool
    {
        return in_array($invoice->status, ['unpaid', 'partial'])
            && ($user->can('finance.manage') || ($user->can('finance.pay') && $this->isGuardianOf($user, $invoice)));
    }

    private function isGuardianOf(User $user, Invoice $invoice): bool
    {
        return $user->children()->whereKey($invoice->student_id)->exists();
    }
}
