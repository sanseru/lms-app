<?php

namespace Modules\Finance\Support;

/** Spells out a number in English words, for receipts. */
class AmountInWords
{
    private const ONES = ['', 'one', 'two', 'three', 'four', 'five', 'six', 'seven', 'eight', 'nine', 'ten',
        'eleven', 'twelve', 'thirteen', 'fourteen', 'fifteen', 'sixteen', 'seventeen', 'eighteen', 'nineteen'];

    private const TENS = ['', '', 'twenty', 'thirty', 'forty', 'fifty', 'sixty', 'seventy', 'eighty', 'ninety'];

    private const SCALES = [1_000_000_000_000 => 'trillion', 1_000_000_000 => 'billion', 1_000_000 => 'million', 1000 => 'thousand'];

    public static function rupiah(int $amount): string
    {
        return ucfirst(self::words($amount) ?: 'zero').' rupiah';
    }

    public static function words(int $n): string
    {
        if ($n <= 0) {
            return '';
        }

        foreach (self::SCALES as $size => $name) {
            if ($n >= $size) {
                return trim(self::words(intdiv($n, $size)).' '.$name.' '.self::words($n % $size));
            }
        }

        return self::belowThousand($n);
    }

    private static function belowThousand(int $n): string
    {
        $parts = [];

        if ($n >= 100) {
            $parts[] = self::ONES[intdiv($n, 100)].' hundred';
            $n %= 100;
        }

        if ($n >= 20) {
            $parts[] = self::TENS[intdiv($n, 10)].($n % 10 ? '-'.self::ONES[$n % 10] : '');
        } elseif ($n > 0) {
            $parts[] = self::ONES[$n];
        }

        return implode(' ', $parts);
    }
}
