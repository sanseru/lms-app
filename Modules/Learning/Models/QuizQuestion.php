<?php

namespace Modules\Learning\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    public const TYPES = [
        'single' => 'Satu jawaban',
        'multiple' => 'Beberapa jawaban',
        'true_false' => 'True / false',
        'short' => 'Short answer',
    ];

    protected $fillable = ['lesson_id', 'position', 'prompt', 'type', 'options', 'explanation', 'points', 'concept'];

    protected function casts(): array
    {
        return ['options' => 'array', 'points' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function isShort(): bool
    {
        return $this->type === 'short';
    }

    /** Indeks opsi yang benar, terurut. */
    public function correctIndexes(): array
    {
        return collect($this->options)->keys()->filter(fn ($i) => (bool) ($this->options[$i]['correct'] ?? false))->values()->all();
    }

    /** Kunci jawaban: indeks opsi, atau daftar jawaban yang diterima untuk soal isian. */
    public function answerKey(): array
    {
        return $this->isShort()
            ? collect($this->options)->pluck('text')->values()->all()
            : $this->correctIndexes();
    }

    /** Jawaban siswa dalam bentuk yang disimpan: teks untuk isian, indeks untuk pilihan. */
    public function normalizeGiven(mixed $answer): string|array
    {
        if ($this->isShort()) {
            return is_scalar($answer) ? mb_substr(trim((string) $answer), 0, 200) : '';
        }

        return collect((array) $answer)->filter(fn ($v) => is_numeric($v))->map(fn ($v) => (int) $v)->unique()->sort()->values()->all();
    }

    public function isCorrect(mixed $answer): bool
    {
        $given = $this->normalizeGiven($answer);

        if ($this->isShort()) {
            return $given !== '' && collect($this->options)->contains(fn ($o) => self::sameText($given, (string) $o['text']));
        }

        return $given !== [] && $given === $this->correctIndexes();
    }

    /** Membandingkan jawaban isian: abaikan huruf besar, spasi berlebih, titik di akhir; angka dibandingkan nilainya. */
    public static function sameText(string $a, string $b): bool
    {
        $clean = fn (string $s) => rtrim(preg_replace('/\s+/u', ' ', mb_strtolower(trim($s))), '. ');
        [$a, $b] = [$clean($a), $clean($b)];
        $number = fn (string $s) => is_numeric($n = str_replace(',', '.', $s)) ? (float) $n : null;

        if ($number($a) !== null && $number($b) !== null) {
            return abs($number($a) - $number($b)) < 1e-9;
        }

        return $a === $b;
    }

    /** Bentuk untuk siswa: tanpa kunci jawaban. */
    public function toPublic(): array
    {
        return [
            'id' => $this->id,
            'prompt' => $this->prompt,
            'type' => $this->type,
            'points' => $this->points,
            'options' => $this->isShort() ? [] : collect($this->options)->map(fn ($o) => $o['text'])->all(),
        ];
    }
}
