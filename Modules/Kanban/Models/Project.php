<?php

namespace Modules\Kanban\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Classes\Models\ClassRoom;

/** Proyek untuk satu kelas; setiap siswa mendapat papan kanban sendiri. */
class Project extends Model
{
    protected $fillable = ['class_room_id', 'created_by', 'title', 'description', 'emoji', 'due_date', 'requires_review', 'status'];

    protected function casts(): array
    {
        return ['due_date' => 'date', 'requires_review' => 'boolean'];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function templateTasks(): HasMany
    {
        return $this->hasMany(TemplateTask::class)->orderBy('position');
    }

    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where(fn (Builder $q) => $q
            ->whereIn('class_room_id', $user->teachingClasses()->select('id'))
            ->orWhere(fn (Builder $q) => $q->where('status', 'active')->where(fn (Builder $q) => $q
                ->whereIn('class_room_id', $user->enrolledClasses()->select('class_rooms.id'))
                ->orWhereIn('class_room_id', ClassRoom::query()->whereHas('students', fn ($s) => $s->whereIn('users.id', $user->children()->select('users.id')))->select('id')))));
    }

    /** Guru kelas atau admin. */
    public function isStaff(User $user): bool
    {
        return $user->hasRole('admin') || $this->classRoom->teacher_id === $user->id;
    }

    public function isOverdue(): bool
    {
        return $this->due_date !== null && $this->due_date->isPast() && ! $this->due_date->isToday();
    }

    public function toCard(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'emoji' => $this->emoji,
            'description' => $this->description,
            'due_date' => $this->due_date?->translatedFormat('d M Y'),
            'due_in' => $this->due_date?->isFuture() ? $this->due_date->diffForHumans(['parts' => 1]) : null,
            'is_overdue' => $this->isOverdue(),
            'requires_review' => $this->requires_review,
            'status' => $this->status,
            'class' => ['id' => $this->classRoom->id, 'name' => $this->classRoom->name, 'color' => $this->classRoom->color],
        ];
    }
}
