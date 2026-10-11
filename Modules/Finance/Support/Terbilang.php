<?php

namespace Modules\Finance\Support;

/** Mengubah angka menjadi kata dalam bahasa Indonesia, untuk kuitansi. */
class Terbilang
{
    private const WORDS = ['', 'satu', 'dua', 'tiga', 'empat', 'lima', 'enam', 'tujuh', 'delapan', 'sembilan', 'sepuluh', 'sebelas'];

    public static function rupiah(int $amount): string
    {
        return ucfirst(trim(self::words($amount)) ?: 'nol').' rupiah';
    }

    public static function words(int $n): string
    {
        return match (true) {
            $n === 0 => '',
            $n < 12 => ' '.self::WORDS[$n],
            $n < 20 => self::words($n - 10).' belas',
            $n < 100 => self::words(intdiv($n, 10)).' puluh'.self::words($n % 10),
            $n < 200 => ' seratus'.self::words($n - 100),
            $n < 1000 => self::words(intdiv($n, 100)).' ratus'.self::words($n % 100),
            $n < 2000 => ' seribu'.self::words($n - 1000),
            $n < 1_000_000 => self::words(intdiv($n, 1000)).' ribu'.self::words($n % 1000),
            $n < 1_000_000_000 => self::words(intdiv($n, 1_000_000)).' juta'.self::words($n % 1_000_000),
            default => self::words(intdiv($n, 1_000_000_000)).' miliar'.self::words($n % 1_000_000_000),
        };
    }
}
