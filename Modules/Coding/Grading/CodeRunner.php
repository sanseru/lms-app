<?php

namespace Modules\Coding\Grading;

use Illuminate\Support\Collection;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeTest;

/**
 * Menjalankan kode untuk tes input→output / fungsi dan mengembalikan output mentah.
 * Driver: "browser" (hasil dari browser siswa) atau "judge0" (server terpisah).
 */
interface CodeRunner
{
    public function name(): string;

    /**
     * @param  Collection<int, CodeTest>  $tests
     * @param  array<int|string, array{output?: string|null, error?: string|null}>  $reported  hasil dari browser
     * @return array<int, array{output: string|null, error: string|null}>
     */
    public function run(CodeExercise $exercise, string $code, Collection $tests, array $reported): array;
}
