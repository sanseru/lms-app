<?php

namespace Modules\Learning\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Classes\Models\ClassRoom;

/**
 * Kursus = kurikulum berurutan (materi, kuis, tugas) yang dipakai satu atau
 * beberapa kelas. Siswa melihat kursus dari kelas yang ia ikuti.
 */
class Course extends Model
{
    use SoftDeletes;

    public const STATUSES = ['draft' => 'Draf', 'published' => 'Terbit'];

    protected $fillable = ['title', 'subject', 'level', 'description', 'color', 'status', 'created_by'];

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class)->orderBy('position')->orderBy('id');
    }

    public function classes(): HasMany
    {
        return $this->hasMany(ClassRoom::class);
    }

    /** Kursus yang boleh dibuka user: pengelola semua, lainnya lewat kelas. */
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
