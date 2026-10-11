<?php

namespace Modules\Coding\Grading\Drivers;

use Illuminate\Support\Collection;
use Modules\Coding\Grading\CodeRunner;
use Modules\Coding\Models\CodeExercise;

/**
 * The code already ran in the student's browser (Pyodide / Web Worker); the server only
 * receives the output and compares it with the answers stored on the server.
 * Hidden test answers are never sent to the browser.
 */
class BrowserRunner implements CodeRunner
{
    public function name(): string
    {
        return 'browser';
    }

    public function run(CodeExercise $exercise, string $code, Collection $tests, array $reported): array
    {
        return $tests->mapWithKeys(fn ($test) => [$test->id => [
            'output' => isset($reported[$test->id]['output']) ? mb_substr((string) $reported[$test->id]['output'], 0, 20000) : null,
            'error' => isset($reported[$test->id]['error']) ? mb_substr((string) $reported[$test->id]['error'], 0, 500) : null,
        ]])->all();
    }
}
