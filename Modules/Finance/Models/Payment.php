<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    public const STATUSES = [
        'pending' => 'Awaiting verification',
        'verified' => 'Verified',
        'rejected' => 'Rejected',
    ];

    protected $fillable = ['invoice_id', 'amount', 'paid_at', 'method', 'bank_name', 'account_name', 'proof_path', 'status', 'notes', 'rejection_reason', 'submitted_by', 'verified_by', 'verified_at'];

    protected function casts(): array
    {
        return [
            'paid_at' => 'date',
            'verified_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
