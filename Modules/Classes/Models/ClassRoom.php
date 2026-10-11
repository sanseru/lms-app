<?php

namespace Modules\Classes\Models;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Online (Zoom) class with a fixed weekly schedule.
 * Named "ClassRoom" because "Class" is a PHP keyword.
 */
class ClassRoom extends Model
{
    use SoftDeletes;

    public const DAYS = [1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday', 5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday'];

    public const LEVELS = ['beginner' => 'Beginner', 'intermediate' => 'Intermediate', 'advanced' => 'Advanced'];

    public const STATUSES = ['upcoming' => 'Upcoming', 'active' => 'Running', 'finished' => 'Finished'];

    public const COLORS = ['violet', 'sky', 'emerald', 'amber', 'rose', 'indigo'];

    protected $fillable = [
        'code', 'name', 'subject', 'level', 'description', 'teacher_id',
        'zoom_url', 'zoom_meeting_id', 'zoom_passcode',
        'schedule_days', 'start_time', 'end_time', 'start_date', 'end_date',
        'capacity', 'status', 'color',
    ];

    protected function casts(): array
    {
        return [
            'schedule_days' => 'array',
            'start_date' => 'date',
            'end_date' => 'date',
            'capacity' => 'integer',
        ];
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    public function students(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'class_students', 'class_room_id', 'student_id')
            ->withPivot('status', 'joined_at')
            ->withTimestamps();
    }

    /** Classes the user can see based on their role. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('classes.manage')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('teacher_id', $user->id)
                ->orWhereHas('students', fn ($q) => $q->whereKey($user->id))
                ->orWhereHas('students', fn ($q) => $q->whereIn('users.id', $user->children()->select('users.id')));
        });
    }

    public function scopeOnDay(Builder $query, CarbonInterface $date): Builder
    {
        return $query->where('status', 'active')
            ->whereJsonContains('schedule_days', $date->dayOfWeekIso)
            ->where(fn ($q) => $q->whereNull('start_date')->orWhereDate('start_date', '<=', $date))
            ->where(fn ($q) => $q->whereNull('end_date')->orWhereDate('end_date', '>=', $date));
    }

    public function scheduleLabel(): string
    {
        $days = collect($this->schedule_days)->sort()->map(fn ($d) => self::DAYS[$d] ?? $d)->implode(', ');

        return $days.' · '.substr($this->start_time, 0, 5).'–'.substr($this->end_time, 0, 5);
    }

    /** Find another class of the same teacher whose schedule clashes. */
    public static function conflictsFor(?int $teacherId, array $days, string $start, string $end, ?int $ignoreId = null): ?self
    {
        if (! $teacherId) {
            return null;
        }

        return static::query()
            ->where('teacher_id', $teacherId)
            ->whereIn('status', ['active', 'upcoming'])
            ->when($ignoreId, fn ($q) => $q->whereKeyNot($ignoreId))
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->get()
            ->first(fn (self $class) => array_intersect($class->schedule_days, $days) !== []);
    }

    public static function nextCode(string $subject): string
    {
        $prefix = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $subject) ?: 'KLS', 0, 3));
        $count = static::withTrashed()->where('code', 'like', "{$prefix}-%")->count() + 1;

        return $prefix.'-'.str_pad((string) $count, 3, '0', STR_PAD_LEFT);
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'subject' => $this->subject,
            'level' => $this->level,
            'level_label' => self::LEVELS[$this->level] ?? $this->level,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'color' => $this->color,
            'schedule' => $this->scheduleLabel(),
            'schedule_days' => $this->schedule_days,
            'start_time' => substr($this->start_time, 0, 5),
            'end_time' => substr($this->end_time, 0, 5),
            'zoom_url' => $this->zoom_url,
            'teacher' => $this->relationLoaded('teacher') && $this->teacher ? ['id' => $this->teacher->id, 'name' => $this->teacher->name, 'initials' => $this->teacher->initials()] : null,
            'students_count' => $this->students_count ?? null,
            'capacity' => $this->capacity,
        ];
    }
}
