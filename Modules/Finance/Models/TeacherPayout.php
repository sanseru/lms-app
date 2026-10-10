<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Slip honor tutor untuk satu periode. */
class TeacherPayout extends Model
{
    public const STATUSES = ['unpaid' => 'Belum dibayar', 'paid' => 'Sudah dibayar'];

    protected $fillable = ['number', 'teacher_id', 'period_start', 'period_end', 'sessions_count', 'subtotal', 'bonus', 'deduction', 'total', 'status', 'paid_at', 'proof_path', 'notes', 'created_by'];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'paid_at' => 'date',
            'subtotal' => 'integer',
            'bonus' => 'integer',
            'deduction' => 'integer',
            'total' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PayrollItem::class)->orderBy('session_date');
    }

    public static function nextNumber(): string
    {
        $prefix = 'HNR-'.now()->format('Ym').'-';
        $last = static::query()->where('number', 'like', $prefix.'%')->max('number');

        return $prefix.str_pad((string) ($last ? ((int) substr($last, -3)) + 1 : 1), 3, '0', STR_PAD_LEFT);
    }
}
