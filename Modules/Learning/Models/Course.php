<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Support\LevelAccess;

/**
 * Course = an ordered curriculum (lessons, quizzes, assignments) used by one or
 * more classes. Students see a course through the classes they are enrolled in.
 */
class Course extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft' => 'Draft', 'published' => 'Published'];

    protected $fillable = ['title', 'subject', 'level', 'description', 'color', 'status', 'created_by', 'program_id', 'level_order'];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(CourseUnit::class)->orderBy('id');
    }

    public function interests(): HasMany
    {
        return $this->hasMany(CourseInterest::class);
    }

    /** A student takes this course when a class of theirs uses it or they unlocked it in its program. */
    public function isTakenBy(User $student): bool
    {
        return app(LevelAccess::class)->allows($student, $this->id);
    }

    /** Courses the user may open: managers all, everyone else through their classes. */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->can('learning.manage')) {
            return $query;
        }

        // Students (and parents, for their children) also see the levels they unlocked in a program.
        $learners = $user->hasRole('guardian') ? $user->children()->get() : collect([$user]);
        $access = app(LevelAccess::class);
        $unlocked = $learners->flatMap(fn (User $learner) => $access->courseIds($learner))->unique()->all();

        return $query->where('status', 'published')
            ->where(fn (Builder $q) => $q
                ->whereIn('id', $unlocked)
                ->orWhereHas('classes', fn (Builder $q) => $q->where('teacher_id', $user->id)));
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
            'program_id' => $this->program_id,
            'level_order' => $this->level_order,
            'lessons_count' => $this->lessons_count ?? null,
            'classes_count' => $this->classes_count ?? null,
        ];
    }
}
