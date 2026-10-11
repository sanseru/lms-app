<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    public const TYPES = ['single' => 'Satu jawaban', 'multiple' => 'Beberapa jawaban'];

    protected $fillable = ['lesson_id', 'position', 'prompt', 'type', 'options', 'explanation', 'points'];

    protected function casts(): array
    {
        return ['options' => 'array', 'points' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    /** Indeks opsi yang benar, terurut. */
    public function correctIndexes(): array
    {
        return collect($this->options)->keys()->filter(fn ($i) => (bool) ($this->options[$i]['correct'] ?? false))->values()->all();
    }

    /** @param  array<int>|int|null  $answer */
    public function isCorrect(array|int|null $answer): bool
    {
        $given = collect((array) $answer)->map(fn ($v) => (int) $v)->unique()->sort()->values()->all();

        return $given !== [] && $given === $this->correctIndexes();
    }

    /** Bentuk untuk siswa: tanpa kunci jawaban. */
    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'type' => $this->type,
            'points' => $this->points,
            'options' => collect($this->options)->map(fn ($o) => $o['text'])->all(),
        ];
    }
}
