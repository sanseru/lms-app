<?php

namespace Modules\Coding\Grading;

/**
 * Compares student output with the expected output in a kid-friendly way:
 * trailing spaces and trailing blank lines are ignored, letter case is ignored
 * (unless required), and decimal numbers get a tolerance.
 */
class OutputComparator
{
    public const TOLERANCE = 1e-6;

    public function text(?string $expected, ?string $actual, bool $caseSensitive = false): bool
    {
        $a = $this->lines($expected ?? '', $caseSensitive);
        $b = $this->lines($actual ?? '', $caseSensitive);

        if (count($a) !== count($b)) {
            return false;
        }

        foreach ($a as $i => $line) {
            $x = preg_split('/\s+/', $line, -1, PREG_SPLIT_NO_EMPTY);
            $y = preg_split('/\s+/', $b[$i], -1, PREG_SPLIT_NO_EMPTY);

            if (count($x) !== count($y)) {
                return false;
            }

            foreach ($x as $j => $token) {
                if (! $this->token($token, $y[$j])) {
                    return false;
                }
            }
        }

        return true;
    }

    /** Function return values (JSON) are compared structurally. */
    public function json(?string $expected, ?string $actual): bool
    {
        try {
            $a = json_decode((string) $expected, true, 64, JSON_THROW_ON_ERROR);
            $b = json_decode((string) $actual, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return false;
        }

        return $this->value($a, $b);
    }

    private function value(mixed $a, mixed $b): bool
    {
        if ((is_int($a) || is_float($a)) && (is_int($b) || is_float($b))) {
            return $this->numbersEqual((float) $a, (float) $b);
        }

        if (is_array($a) && is_array($b)) {
            if (count($a) !== count($b) || array_is_list($a) !== array_is_list($b)) {
                return false;
            }
            foreach ($a as $key => $item) {
                if (! array_key_exists($key, $b) || ! $this->value($item, $b[$key])) {
                    return false;
                }
            }

            return true;
        }

        return $a === $b;
    }

    private function lines(string $text, bool $caseSensitive): array
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $lines = array_map('rtrim', explode("\n", $caseSensitive ? $text : mb_strtolower($text)));

        while ($lines && end($lines) === '') {
            array_pop($lines);
        }

        return $lines;
    }

    private function token(string $a, string $b): bool
    {
        if ($a === $b) {
            return true;
        }

        return is_numeric($a) && is_numeric($b) && $this->numbersEqual((float) $a, (float) $b);
    }

    private function numbersEqual(float $a, float $b): bool
    {
        return abs($a - $b) <= self::TOLERANCE * max(1.0, abs($a));
    }
}
