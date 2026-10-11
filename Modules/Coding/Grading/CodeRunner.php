<?php

namespace Modules\Coding\Grading;

use Illuminate\Support\Collection;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeTest;

/**
 * Runs code for input→output / function tests and returns the raw output.
 * Drivers: "browser" (results from the student's browser) or "judge0" (separate server).
 */
interface CodeRunner
{
    public function name(): string;

    /**
     * @param  Collection<int, CodeTest>  $tests
     * @param  array<int|string, array{output?: string|null, error?: string|null}>  $reported  results from the browser
     * @return array<int, array{output: string|null, error: string|null}>
     */
    public function run(CodeExercise $exercise, string $code, Collection $tests, array $reported): array;
}
