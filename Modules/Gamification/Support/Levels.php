<?php

namespace Modules\Gamification\Support;

/** Level dari total XP: level n butuh 50·n·(n−1) XP (0, 100, 300, 600, 1000, …). */
class Levels
{
    public const NAMES = [
        1 => 'Pemula Koding', 2 => 'Penjelajah', 3 => 'Perakit', 4 => 'Penemu',
        5 => 'Insinyur Cilik', 6 => 'Pencipta', 7 => 'Master Koding', 8 => 'Legenda',
    ];

    public static function threshold(int $level): int
    {
        return 50 * $level * ($level - 1);
    }

    public static function for(int $xp): array
    {
        $level = 1;
        while ($xp >= self::threshold($level + 1)) {
            $level++;
        }

        $from = self::threshold($level);
        $to = self::threshold($level + 1);

        return [
            'level' => $level,
            'name' => self::NAMES[min($level, 8)],
            'xp' => $xp,
            'from' => $from,
            'to' => $to,
            'to_next' => $to - $xp,
            'percent' => (int) floor(($xp - $from) / ($to - $from) * 100),
        ];
    }
}
