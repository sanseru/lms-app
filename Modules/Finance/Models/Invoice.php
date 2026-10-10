<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Classes\Models\ClassRoom;

class Invoice extends Model
{
    public const STATUSES = [
        'unpaid' => 'Belum bayar',
        'partial' => 'Sebagian',
        'paid' => 'Lunas',
        'cancelled' => 'Dibatalkan',
    ];

    protected $fillable = ['number', 'student_id', 'class_room_id', 'title', 'period', 'amount', 'paid_amount', 'due_date', 'status', 'notes', 'issued_by'];

    protected function casts(): array
    {
        return [
            'period' => 'date',
            'due_date' => 'date',
            'amount' => 'integer',
            'paid_amount' => 'integer',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('paid_at');
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('finance.manage')) {
            return $query;
        }

        return $query->whereIn('student_id', $user->children()->select('users.id'));
    }

    public function scopeOutstanding(Builder $query): Builder
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }

    public function remaining(): int
    {
        return max(0, $this->amount - $this->paid_amount);
    }

    public function isOverdue(): bool
    {
        return in_array($this->status, ['unpaid', 'partial']) && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    /** Hitung ulang total terbayar dari pembayaran terverifikasi. */
    public function recalculate(): void
    {
        if ($this->status === 'cancelled') {
            return;
        }

        $paid = (int) $this->payments()->where('status', 'verified')->sum('amount');

        $this->forceFill([
            'paid_amount' => $paid,
            'status' => match (true) {
                $paid >= $this->amount => 'paid',
                $paid > 0 => 'partial',
                default => 'unpaid',
            },
        ])->save();

        // Paket kuota aktif setelah tagihannya lunas.
        if ($this->status === 'paid') {
            $this->studentPackages()->where('status', 'pending_payment')->get()->each->activate();
        }
    }

    public function studentPackages(): HasMany
    {
        return $this->hasMany(StudentPackage::class);
    }

    public static function nextNumber(): string
    {
        $prefix = 'INV-'.now()->format('Ym').'-';
        $last = static::query()->where('number', 'like', $prefix.'%')->max('number');
        $seq = $last ? ((int) substr($last, -4)) + 1 : 1;

        return $prefix.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
    }
}
