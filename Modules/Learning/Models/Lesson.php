<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Modules\Learning\Contracts\LessonTypeHandler;
use Modules\Learning\Support\LessonTypes;

class Lesson extends Model
{
    public const TYPES = ['material' => 'Materi', 'quiz' => 'Kuis', 'assignment' => 'Tugas'];

    public const ICONS = ['material' => 'book-open', 'quiz' => 'list-checks', 'assignment' => 'file-pen'];

    public const ASSESSMENTS = ['pre' => 'Misi Pemanasan', 'post' => 'Misi Akhir'];

    /** Semua jenis isi: bawaan + yang didaftarkan modul lain. */
    public static function types(): array
    {
        return self::TYPES + collect(app(LessonTypes::class)->all())->map->label()->all();
    }

    public static function iconFor(string $type): string
    {
        return self::ICONS[$type] ?? app(LessonTypes::class)->get($type)?->icon() ?? 'book-open';
    }

    public function handler(): ?LessonTypeHandler
    {
        return app(LessonTypes::class)->get($this->type);
    }

    protected $fillable = [
        'course_id', 'position', 'title', 'type', 'body', 'video_url', 'duration_minutes',
        'pass_score', 'max_attempts', 'max_score', 'is_published', 'assessment',
    ];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('position')->orderBy('id');
    }

    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class);
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }

    public function passScore(): int
    {
        return $this->pass_score ?? 70;
    }

    public function maxScore(): int
    {
        return $this->max_score ?? 100;
    }

    /** Markdown materi menjadi HTML aman (HTML mentah di-escape). */
    public function bodyHtml(): ?string
    {
        return $this->body ? Str::markdown($this->body, ['html_input' => 'escape', 'allow_unsafe_links' => false]) : null;
    }

    /** Ubah link YouTube/Vimeo biasa menjadi URL embed. */
    public function videoEmbedUrl(): ?string
    {
        $url = $this->video_url;

        if (! $url) {
            return null;
        }

        if (preg_match('~(?:youtube\.com/(?:watch\?v=|embed/|shorts/)|youtu\.be/)([\w-]{11})~', $url, $m)) {
            return 'https://www.youtube-nocookie.com/embed/'.$m[1];
        }

        if (preg_match('~vimeo\.com/(\d+)~', $url, $m)) {
            return 'https://player.vimeo.com/video/'.$m[1];
        }

        return null;
    }

    public function toItem(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'type' => $this->type,
            'type_label' => self::types()[$this->type] ?? $this->type,
            'icon' => self::iconFor($this->type),
            'assessment' => $this->assessment,
            'assessment_label' => self::ASSESSMENTS[$this->assessment] ?? null,
            'position' => $this->position,
            'duration_minutes' => $this->duration_minutes,
            'is_published' => $this->is_published,
            'questions_count' => $this->questions_count ?? null,
        ];
    }
}
