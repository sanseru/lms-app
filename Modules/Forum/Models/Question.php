<?php

namespace Modules\Forum\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;
use Modules\Classes\Models\ClassRoom;
use Modules\Learning\Models\Lesson;

class Question extends Model
{
    protected $table = 'forum_questions';

    protected $fillable = [
        'class_room_id', 'lesson_id', 'author_id', 'title', 'body', 'code', 'image_path', 'visibility', 'anonymous',
        'pinned', 'accepted_answer_id', 'resolved_at', 'staff_answered_at', 'last_activity_at',
    ];

    public const VISIBILITIES = ['class' => 'Semua teman sekelas', 'private' => 'Hanya guru'];

    public const STATUSES = ['unanswered' => 'Belum dijawab', 'answered' => 'Ada jawaban', 'resolved' => 'Terjawab'];

    protected function casts(): array
    {
        return [
            'anonymous' => 'boolean',
            'pinned' => 'boolean',
            'resolved_at' => 'datetime',
            'staff_answered_at' => 'datetime',
            'last_activity_at' => 'datetime',
        ];
    }

    public function classRoom(): BelongsTo
    {
        return $this->belongsTo(ClassRoom::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function answers(): HasMany
    {
        return $this->hasMany(Answer::class);
    }

    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'votable');
    }

    /**
     * Pertanyaan yang boleh dilihat user: guru melihat semua di kelasnya, siswa melihat
     * pertanyaan kelas (bukan yang khusus guru) plus miliknya sendiri, orang tua melihat
     * pertanyaan anaknya.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->hasRole('admin')) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->whereIn('class_room_id', $user->teachingClasses()->select('id'))
                ->orWhere('author_id', $user->id)
                ->orWhereIn('author_id', $user->children()->select('users.id'))
                ->orWhere(fn (Builder $q) => $q->where('visibility', 'class')
                    ->whereIn('class_room_id', $user->enrolledClasses()->select('class_rooms.id')));
        });
    }

    public function scopeStatus(Builder $query, ?string $status): Builder
    {
        return match ($status) {
            'unanswered' => $query->whereNull('resolved_at')->whereNull('staff_answered_at'),
            'answered' => $query->whereNull('resolved_at')->where('answers_count', '>', 0),
            'resolved' => $query->whereNotNull('resolved_at'),
            default => $query,
        };
    }

    /** Guru kelas ini atau admin. */
    public function isStaff(User $user): bool
    {
        return $user->hasRole('admin') || $this->classRoom->teacher_id === $user->id;
    }

    public function status(): string
    {
        return match (true) {
            $this->resolved_at !== null => 'resolved',
            $this->answers_count > 0 => 'answered',
            default => 'unanswered',
        };
    }

    public function bodyHtml(): string
    {
        return Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]);
    }

    /** Nama penanya; anonim untuk teman sekelas, tetapi guru tetap tahu. */
    public function authorFor(User $viewer): array
    {
        $hidden = $this->anonymous && $viewer->id !== $this->author_id && ! $this->isStaff($viewer)
            && ! $viewer->children()->whereKey($this->author_id)->exists();

        return $hidden
            ? ['id' => 0, 'name' => 'Teman sekelas', 'initials' => '?', 'anonymous' => true]
            : ['id' => $this->author->id, 'name' => $this->author->name, 'initials' => $this->author->initials(), 'anonymous' => $this->anonymous];
    }

    /** Ringkasan untuk daftar. */
    public function toItem(User $viewer, array $votedIds = []): array
    {
        $status = $this->status();

        return [
            'id' => $this->id,
            'title' => $this->title,
            'excerpt' => Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($this->bodyHtml())))), 140),
            'has_code' => filled($this->code),
            'has_image' => filled($this->image_path),
            'author' => $this->authorFor($viewer),
            'class' => ['id' => $this->classRoom->id, 'name' => $this->classRoom->name, 'color' => $this->classRoom->color],
            'lesson' => $this->lesson ? ['id' => $this->lesson->id, 'title' => $this->lesson->title] : null,
            'visibility' => $this->visibility,
            'pinned' => $this->pinned,
            'status' => $status,
            'status_label' => self::STATUSES[$status],
            'staff_answered' => $this->staff_answered_at !== null,
            'answers_count' => $this->answers_count,
            'votes_count' => $this->votes_count,
            'voted' => in_array($this->id, $votedIds, true),
            'mine' => $this->author_id === $viewer->id,
            'created_at' => $this->created_at->diffForHumans(),
            'activity_at' => ($this->last_activity_at ?? $this->created_at)->diffForHumans(),
        ];
    }

    public function touchActivity(): void
    {
        $this->forceFill(['last_activity_at' => now()])->saveQuietly();
    }
}
