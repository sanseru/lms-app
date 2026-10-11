<?php

namespace Modules\Coding\Grading;

/**
 * Simple code rules checked on the server, e.g. "must use for",
 * "must not use sum", "at most 6 lines". Comments and string contents
 * are ignored so a rule can't be gamed by writing the keyword in a
 * comment.
 */
class CodeRules
{
    public const TYPES = ['require' => 'Must use', 'forbid' => 'Must not use', 'max_lines' => 'At most N lines'];

    /**
     * @param  array<int, array{type: string, value: string, message?: string|null}>  $rules
     * @return array<int, array<string, mixed>> per-rule results in the same format as test results
     */
    public function check(array $rules, string $language, string $code): array
    {
        $clean = $this->strip($language, $code);
        $lines = count(array_filter(preg_split('/\R/', $clean), fn ($l) => trim($l) !== ''));

        return collect($rules)->values()->map(function (array $rule, int $i) use ($clean, $lines) {
            $value = trim((string) $rule['value']);
            $found = $this->contains($clean, $value);

            $passed = match ($rule['type']) {
                'require' => $found,
                'forbid' => ! $found,
                'max_lines' => $lines <= (int) $value,
                default => true,
            };

            return [
                'id' => 'rule-'.$i,
                'passed' => $passed,
                'hidden' => false,
                'weight' => 1,
                'label' => self::label($rule),
                'message' => $passed ? null : ($rule['message'] ?? null ?: ($rule['type'] === 'max_lines' ? "Your code has {$lines} lines." : null)),
                'error' => null,
                'expected' => null,
                'actual' => null,
                'input' => null,
                'rule' => true,
            ];
        })->all();
    }

    public static function label(array $rule): string
    {
        return match ($rule['type']) {
            'require' => "Uses `{$rule['value']}`",
            'forbid' => "Does not use `{$rule['value']}`",
            'max_lines' => "At most {$rule['value']} lines of code",
            default => $rule['type'],
        };
    }

    /** Words like `for` match as whole words (not part of `format`); anything else is matched literally. */
    private function contains(string $code, string $needle): bool
    {
        if ($needle === '') {
            return false;
        }

        return preg_match('/^\w+$/u', $needle)
            ? (bool) preg_match('/(?<!\w)'.preg_quote($needle, '/').'(?!\w)/u', $code)
            : str_contains($code, $needle);
    }

    /** Strip comments and string literal contents (replaced with empty strings). */
    public function strip(string $language, string $code): string
    {
        $patterns = $language === 'python'
            ? ['/("""|\'\'\')[\s\S]*?\1/', '/"(?:\\\\.|[^"\\\\\n])*"|\'(?:\\\\.|[^\'\\\\\n])*\'/', '/#[^\n]*/']
            : ['/`(?:\\\\.|[^`\\\\])*`/', '/"(?:\\\\.|[^"\\\\\n])*"|\'(?:\\\\.|[^\'\\\\\n])*\'/', '/\/\*[\s\S]*?\*\//', '/\/\/[^\n]*/'];

        foreach ($patterns as $pattern) {
            $code = preg_replace_callback($pattern, fn ($m) => str_starts_with($m[0], '#') || str_starts_with($m[0], '/') ? '' : '""', $code) ?? $code;
        }

        return $code;
    }
}
