<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Classes\Models\ClassRoom;

/**
 * Course = an ordered curriculum (lessons, quizzes, assignments) used by one or
 * more classes. Students see a course through the classes they are enrolled in.
 */
class Course extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published'];

    protected $fillable = ['title', 'subject', 'level', 'description', 'color', 'status', 'created_by'];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    /** Courses the user may open: managers all, everyone else through their classes. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('learning.manage')) {
            return $query;
        }

        $studentIds = $user->hasRole('guardian')
            ? $user->children()->pluck('users.id')->all()
            : [$user->id];

        return $query->where('status', 'published')
            ->whereHas('classes', fn (Builder $q) => $q->where(fn (Builder $q) => $q
                ->where('teacher_id', $user->id)
                ->orWhereHas('students', fn ($q) => $q->whereIn('users.id', $studentIds))));
    }

    public function nextPosition(): int
    {
        return (int) $this->lessons()->max('position') + 1;
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subject' => $this->subject,
            'level' => $this->level,
            'level_label' => ClassRoom::LEVELS[$this->level] ?? $this->level,
            'description' => $this->description,
            'color' => $this->color,
            'status' => $this->status,
            'status_label' => self::STATUSES[$this->status] ?? $this->status,
            'lessons_count' => $this->lessons_count ?? null,
            'classes_count' => $this->classes_count ?? null,
        ];
    }
}
