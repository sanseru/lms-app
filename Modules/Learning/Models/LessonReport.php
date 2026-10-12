<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** A student report that something is wrong in a lesson or exercise. */
class LessonReport extends Model
{
    public const REASONS = [
        'wrong_answer' => 'The answer or a test seems wrong',
        'unclear' => 'The instructions are unclear',
        'broken' => 'Something is broken or has a typo',
        'other' => 'Something else',
    ];

    public const STATUSES = ['open' => 'Open', 'resolved' => 'Fixed', 'dismissed' => 'No change needed'];

    protected $fillable = ['lesson_id', 'user_id', 'reason', 'message', 'status', 'response', 'resolved_by', 'resolved_at'];

    protected function casts(): array
    {
        return ['resolved_at' => 'datetime'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Reports a user may see and close: admins all of them, teachers only reports on
     * courses they created or teach (a class of theirs uses the course).
     */
    public function scopeReviewableBy(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->whereHas('lesson.course', fn (Builder $q) => $q
            ->where('created_by', $user->id)
            ->orWhereHas('classes', fn (Builder $q) => $q->where('teacher_id', $user->id)));
    }
}
