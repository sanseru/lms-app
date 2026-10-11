<?php

namespace Modules\Coding\Grading\Drivers;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Modules\Coding\Grading\CodeRunner;
use Modules\Coding\Grading\Harness;
use Modules\Coding\Models\CodeExercise;

/**
 * Runs code on Judge0 CE (self-hosted on a separate VPS, with no access to the internal
 * network). Browser output is ignored, so scores cannot be faked.
 */
class Judge0Runner implements CodeRunner
{
    public function __construct(private array $config) {}

    public function name(): string
    {
        return 'judge0';
    }

    public function run(CodeExercise $exercise, string $code, Collection $tests, array $reported): array
    {
        $languageId = $this->config['languages'][$exercise->language] ?? null;
        abort_unless($languageId, 422, "The {$exercise->language} language is not configured for Judge0 yet.");

        $submissions = $tests->map(fn ($test) => [
            'language_id' => $languageId,
            'source_code' => base64_encode($this->source($exercise, $code, $test->input)),
            'stdin' => base64_encode($exercise->mode === 'io' ? (string) $test->input : ''),
            'cpu_time_limit' => max(1, $exercise->time_limit_ms / 1000),
            'enable_network' => false,
        ])->values()->all();

        $http = Http::baseUrl(rtrim($this->config['url'], '/'))
            ->timeout(30)
            ->when($this->config['token'] ?? null, fn ($h, $token) => $h->withHeaders(['X-Auth-Token' => $token]));

        $tokens = collect($http->post('/submissions/batch?base64_encoded=true', ['submissions' => $submissions])->throw()->json())->pluck('token');

        // Wait until all are done (status id > 2 = not queued/running).
        $results = [];
        for ($i = 0; $i < 20; $i++) {
            $results = $http->get('/submissions/batch', ['tokens' => $tokens->implode(','), 'base64_encoded' => 'true', 'fields' => 'status,stdout,stderr,compile_output'])
                ->throw()->json('submissions');

            if (collect($results)->every(fn ($r) => ($r['status']['id'] ?? 0) > 2)) {
                break;
            }
            usleep(500_000);
        }

        return $tests->values()->mapWithKeys(function ($test, $i) use ($results) {
            $r = $results[$i] ?? [];
            $statusId = $r['status']['id'] ?? 0;
            $decode = fn ($v) => $v === null ? null : base64_decode($v);
            $error = match (true) {
                $statusId === 5 => 'Time limit reached',
                $statusId >= 6 => trim($decode($r['compile_output'] ?? null) ?: $decode($r['stderr'] ?? null) ?: ($r['status']['description'] ?? 'Error')),
                default => null,
            };

            return [$test->id => ['output' => $decode($r['stdout'] ?? null), 'error' => $error]];
        })->all();
    }

    private function source(CodeExercise $exercise, string $code, ?string $input): string
    {
        if ($exercise->mode !== 'function') {
            return $code;
        }

        return $exercise->language === 'python'
            ? Harness::python($code, $exercise->function_name, $input ?? '[]')
            : Harness::javascript($code, $exercise->function_name, $input ?? '[]');
    }
}
