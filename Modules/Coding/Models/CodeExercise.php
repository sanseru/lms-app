<?php

namespace Modules\Coding\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Learning\Models\Lesson;

class CodeExercise extends Model
{
    public const LANGUAGES = ['python' => 'Python', 'javascript' => 'JavaScript', 'html' => 'HTML & CSS'];

    public const MODES = [
        'io' => 'Input → output',
        'function' => 'Fungsi',
        'html' => 'Cek HTML/CSS',
        'parsons' => 'Susun baris kode',
    ];

    /** Mode yang boleh dipakai tiap bahasa. */
    public const MODES_BY_LANGUAGE = [
        'python' => ['io', 'function', 'parsons'],
        'javascript' => ['io', 'function', 'parsons'],
        'html' => ['html'],
    ];

    public const HTML_RULES = [
        'exists' => 'Elemen ada',
        'text' => 'Teks elemen berisi',
        'attr' => 'Atribut elemen',
        'count' => 'Jumlah elemen minimal',
        'style' => 'Gaya CSS elemen',
    ];

    protected $fillable = [
        'lesson_id', 'language', 'mode', 'starter_code', 'solution_code', 'function_name', 'hints',
        'time_limit_ms', 'show_solution_after', 'case_sensitive', 'rules', 'version', 'concept', 'level', 'verified_hash', 'verified_at',
    ];

    protected function casts(): array
    {
        return ['hints' => 'array', 'rules' => 'array', 'case_sensitive' => 'boolean', 'verified_at' => 'datetime', 'version' => 'integer'];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function tests(): HasMany
    {
        return $this->hasMany(CodeTest::class)->orderBy('position')->orderBy('id');
    }

    /** Sidik isi yang dinilai: berubah bila solusi atau tes berubah, sehingga perlu diuji ulang. */
    public function contentHash(): string
    {
        $tests = $this->tests()->get()->map->only('kind', 'input', 'expected', 'property', 'weight')->all();
        $parts = [$this->language, $this->mode, $this->solution_code, $this->function_name, $this->case_sensitive, $tests];

        // Aturan kode ikut sidik hanya bila ada, supaya soal lama tidak perlu diuji ulang.
        if ($this->activeRules()) {
            $parts[] = $this->activeRules();
        }

        return sha1(json_encode($parts));
    }

    /** Aturan kode hanya berlaku untuk soal yang kodenya dijalankan. */
    public function activeRules(): array
    {
        return in_array($this->mode, ['io', 'function'], true) ? array_values($this->rules ?? []) : [];
    }

    public function versions(): HasMany
    {
        return $this->hasMany(CodeExerciseVersion::class)->orderByDesc('version');
    }

    /** Isi soal yang menentukan nilai, disimpan per versi. */
    public function snapshot(): array
    {
        return [
            'language' => $this->language,
            'mode' => $this->mode,
            'solution_code' => $this->solution_code,
            'function_name' => $this->function_name,
            'case_sensitive' => $this->case_sensitive,
            'rules' => $this->activeRules(),
            'tests' => $this->tests()->get()->map->only('kind', 'input', 'expected', 'property', 'is_hidden', 'weight')->all(),
        ];
    }

    public function isVerified(): bool
    {
        return $this->mode === 'parsons' || ($this->verified_hash !== null && $this->verified_hash === $this->contentHash());
    }

    /** Baris solusi untuk soal Parsons (baris kosong dibuang, indentasi dipertahankan). */
    public function parsonsLines(): array
    {
        return collect(preg_split('/\R/', $this->solution_code))
            ->map(fn ($line) => rtrim($line))
            ->filter(fn ($line) => trim($line) !== '')
            ->values()
            ->all();
    }
}
