<?php

namespace Modules\Coding\Grading;

use DOMDocument;
use DOMXPath;
use Modules\Coding\Models\CodeExercise;
use Modules\Coding\Models\CodeTest;
use Symfony\Component\CssSelector\CssSelectorConverter;
use Throwable;

/**
 * Menilai satu jawaban. Semua mode menghasilkan format yang sama:
 * status, skor 0–100, jumlah tes lulus, dan daftar hasil per tes.
 */
class Grader
{
    public function __construct(private CodeRunner $runner, private OutputComparator $compare, private CodeRules $rules) {}

    /**
     * @param  array<int|string, array{output?: string|null, error?: string|null}>  $reported  output dari browser
     * @param  array<int|string, bool>  $styles  hasil cek gaya CSS dari browser (hanya bisa diukur di browser)
     */
    public function grade(CodeExercise $exercise, string $code, array $reported = [], array $styles = []): array
    {
        $exercise->loadMissing('tests');

        $items = match ($exercise->mode) {
            'parsons' => $this->parsons($exercise, $code),
            'html' => $this->html($exercise, $code, $styles),
            default => $this->executed($exercise, $code, $reported),
        };

        if ($rules = $exercise->activeRules()) {
            $items = [...$items, ...$this->rules->check($rules, $exercise->language, $code)];
        }

        $total = array_sum(array_column($items, 'weight')) ?: 1;
        $earned = array_sum(array_map(fn ($i) => $i['passed'] ? $i['weight'] : 0, $items));
        $passed = count(array_filter($items, fn ($i) => $i['passed']));
        $allPassed = $items !== [] && $passed === count($items);
        $hasError = collect($items)->contains(fn ($i) => ! empty($i['error']));

        return [
            'status' => $allPassed ? 'passed' : ($hasError ? 'error' : 'failed'),
            'score' => (int) round($earned / $total * 100),
            'passed_tests' => $passed,
            'total_tests' => count($items),
            'results' => array_values($items),
            'graded_by' => in_array($exercise->mode, ['parsons', 'html'], true) ? 'server' : $this->runner->name(),
        ];
    }

    private function executed(CodeExercise $exercise, string $code, array $reported): array
    {
        $outputs = $this->runner->run($exercise, $code, $exercise->tests, $reported);

        return $exercise->tests->map(function (CodeTest $test) use ($exercise, $outputs) {
            $out = $outputs[$test->id] ?? ['output' => null, 'error' => 'Tes tidak dijalankan'];
            $actual = $exercise->mode === 'function' ? Harness::extract($out['output']) : $out['output'];
            $passed = empty($out['error']) && ($exercise->mode === 'function'
                ? $this->compare->json($test->expected, $actual)
                : $this->compare->text($test->expected, $actual, $exercise->case_sensitive));

            return $this->item($test, $passed, $out['error'] ?? null, $actual, $test->input);
        })->all();
    }

    /** Aturan struktur dicek ulang di server; gaya CSS hanya bisa diukur di browser. */
    private function html(CodeExercise $exercise, string $code, array $styles): array
    {
        $xpath = $this->dom($code);

        return $exercise->tests->map(function (CodeTest $test) use ($xpath, $styles) {
            if ($test->kind === 'style') {
                return $this->item($test, (bool) ($styles[$test->id] ?? false));
            }

            $nodes = $this->query($xpath, (string) $test->input);
            $first = $nodes[0] ?? null;
            $expected = mb_strtolower(trim((string) $test->expected));

            $passed = match ($test->kind) {
                'exists' => $first !== null,
                'count' => count($nodes) >= max(1, (int) $test->expected),
                'text' => $first !== null && str_contains(mb_strtolower(preg_replace('/\s+/', ' ', $first->textContent)), $expected),
                'attr' => $first !== null && $first->hasAttribute((string) $test->property)
                    && ($expected === '' || str_contains(mb_strtolower($first->getAttribute((string) $test->property)), $expected)),
                default => false,
            };

            return $this->item($test, $passed);
        })->all();
    }

    /** Parsons: setiap baris di posisi yang benar mendapat poin (indentasi ikut dinilai). */
    private function parsons(CodeExercise $exercise, string $code): array
    {
        $solution = $exercise->parsonsLines();
        $given = collect(preg_split('/\R/', $code))->map(fn ($l) => rtrim($l))->filter(fn ($l) => trim($l) !== '')->values()->all();

        return collect($solution)->map(fn ($line, $i) => [
            'id' => 'line-'.$i,
            'passed' => ($given[$i] ?? null) === $line,
            'hidden' => false,
            'weight' => 1,
            'label' => 'Baris '.($i + 1),
            'message' => ($given[$i] ?? null) === $line ? null : 'Belum di posisi yang benar',
            'error' => null,
            'expected' => null,
            'actual' => null,
            'input' => null,
        ])->all();
    }

    private function item(CodeTest $test, bool $passed, ?string $error = null, ?string $actual = null, ?string $input = null): array
    {
        return [
            'id' => $test->id,
            'passed' => $passed,
            'hidden' => $test->is_hidden,
            'weight' => $test->weight,
            'message' => $passed ? null : ($test->fail_message ?: null),
            'error' => $error ? $this->friendlyError($error) : null,
            // Untuk tes tersembunyi, jawaban dan output tidak ditampilkan.
            'expected' => $test->is_hidden ? null : $test->expected,
            'actual' => $test->is_hidden ? null : ($actual === null ? null : mb_substr($actual, 0, 2000)),
            'input' => $test->is_hidden ? null : $input,
        ];
    }

    private function friendlyError(string $error): string
    {
        if (str_contains(strtolower($error), 'waktu habis') || str_contains(strtolower($error), 'timeout')) {
            return 'Waktu habis. Mungkin ada perulangan yang tidak pernah berhenti.';
        }

        return mb_substr($error, 0, 300);
    }

    private function dom(string $html): DOMXPath
    {
        $doc = new DOMDocument;
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="utf-8"?>'.($html !== '' ? $html : '<html></html>'), LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        return new DOMXPath($doc);
    }

    /** @return array<int, \DOMElement> */
    private function query(DOMXPath $xpath, string $selector): array
    {
        try {
            $list = $xpath->query((new CssSelectorConverter)->toXPath($selector));
        } catch (Throwable) {
            return [];
        }

        return $list ? iterator_to_array($list) : [];
    }
}
