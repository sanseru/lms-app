<?php

namespace Modules\Finance\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Modules\Classes\Models\ClassRoom;
use Modules\Sessions\Models\SessionAttendance;

/**
 * A student's session quota. Usage is counted from attendance pointing at this package,
 * not from a counter, so attendance corrections give quota back automatically.
 */
class StudentPackage extends Model
{
    public const STATUSES = [
        'pending_payment' => 'Awaiting payment',
        'active' => 'Active',
        'used_up' => 'Used up',
        'expired' => 'Expired',
        'cancelled' => 'Cancelled',
    ];

    protected $fillable = ['student_id', 'package_id', 'class_room_id', 'invoice_id', 'name', 'sessions_total', 'price', 'valid_days', 'starts_at', 'expires_at', 'status'];

    protected function casts(): array
    {
        return ['starts_at' => 'date', 'expires_at' => 'date', 'sessions_total' => 'integer', 'price' => 'integer'];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(SessionAttendance::class, 'student_package_id');
    }

    /** Packages usable for a session on a given date. */
    public function scopeUsableOn(Builder $query, $date): Builder
    {
        return $query->where('status', 'active')
            ->whereDate('starts_at', '<=', $date)
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhereDate('expires_at', '>=', $date));
    }

    public function used(): int
    {
        return $this->usages_count ?? $this->usages()->count();
    }

    public function remaining(): int
    {
        return max(0, $this->sessions_total - $this->used());
    }

    public function activate(?\DateTimeInterface $from = null): void
    {
        $start = Carbon::parse($from ?? today());

        $this->update([
            'status' => 'active',
            'starts_at' => $start,
            'expires_at' => $start->copy()->addDays($this->valid_days),
        ]);
    }

    /** Sync the active/used-up status after usage changes. */
    public function refreshUsage(): void
    {
        if (! in_array($this->status, ['active', 'used_up'])) {
            return;
        }

        $this->update(['status' => $this->usages()->count() >= $this->sessions_total ? 'used_up' : 'active']);
    }

    public function toSummary(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status],
            'total' => $this->sessions_total,
            'used' => $this->used(),
            'remaining' => $this->remaining(),
            'expires_at' => $this->expires_at?->translatedFormat('d M Y'),
            'class' => $this->classRoom?->name,
            'invoice_id' => $this->invoice_id,
        ];
    }
}
