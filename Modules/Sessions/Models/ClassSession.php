<?php

namespace Modules\Sessions\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Modules\Classes\Models\ClassRoom;

/**
 * One scheduled Zoom meeting. The hub for attendance, notes,
 * quota deduction and tutor fees.
 */
class ClassSession extends Model
{
    public const STATUSES = [
        'scheduled' => 'Scheduled',
        'completed' => 'Completed',
        'cancelled' => 'Cancelled',
        'rescheduled' => 'Rescheduled',
    ];

    protected $fillable = [
        'class_room_id', 'replaces_session_id', 'teacher_id', 'starts_at', 'ends_at', 'zoom_url', 'status',
        'topic', 'homework', 'parent_note', 'internal_note', 'flagged', 'recording_url',
        'cancel_reason', 'completed_at', 'completed_by',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'completed_at' => 'datetime',
            'flagged' => 'boolean',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(SessionAttendance::class);
    }

    /** The original session this one replaces. */
    public function replaces(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaces_session_id');
    }

    /** The replacement for this session (when rescheduled or cancelled). */
    public function replacement(): HasOne
    {
        return $this->hasOne(self::class, 'replaces_session_id');
    }

    /** Attendance only applies to sessions that actually run (not cancelled or rescheduled). */
    public function isReportable(): bool
    {
        return in_array($this->status, ['scheduled', 'completed']);
    }

    /** Scheduled sessions can be moved; cancelled ones can be replaced. Each only once. */
    public function canBeRescheduled(): bool
    {
        return in_array($this->status, ['scheduled', 'cancelled']) && ! $this->replacement()->exists();
    }

    /** Another session of the same teacher that overlaps in time. */
    public static function clashFor(?int $teacherId, Carbon $startsAt, Carbon $endsAt, ?int $ignoreId = null): ?self
    {
        if (! $teacherId) {
            return null;
        }

        return self::query()
            ->with('classRoom:id,name')
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['scheduled', 'completed'])
            ->where('starts_at', '<', $endsAt)
            ->where('ends_at', '>', $startsAt)
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->first();
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('classes.manage')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('teacher_id', $user->id)
                ->orWhereHas('classRoom', fn ($q) => $q->visibleTo($user));
        });
    }

    /** Past sessions with no attendance filled in yet. */
    public function scopeNeedsReport(Builder $query): Builder
    {
        return $query->where('status', 'scheduled')->where('ends_at', '<', now());
    }

    public function zoomLink(): ?string
    {
        return $this->zoom_url ?: $this->loadMissing('classRoom')->classRoom?->zoom_url;
    }

    public function durationMinutes(): int
    {
        return (int) $this->starts_at->diffInMinutes($this->ends_at);
    }

    public function toItem(): array
    {
        $class = $this->loadMissing('classRoom')->classRoom;

        return [
            'id' => $this->id,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status],
            'date' => $this->starts_at->toDateString(),
            'date_label' => $this->starts_at->translatedFormat('l, d M Y'),
            'start_time' => $this->starts_at->format('H:i'),
            'end_time' => $this->ends_at->format('H:i'),
            'topic' => $this->topic,
            'flagged' => $this->flagged,
            'needs_report' => $this->status === 'scheduled' && $this->ends_at->isPast(),
            'zoom_url' => $this->status === 'scheduled' ? $this->zoomLink() : null,
            'class' => $class ? [
                'id' => $class->id,
                'name' => $class->name,
                'subject' => $class->subject,
                'color' => $class->color,
            ] : null,
            'teacher' => $this->relationLoaded('teacher') && $this->teacher ? ['id' => $this->teacher->id, 'name' => $this->teacher->name] : null,
        ];
    }
}
