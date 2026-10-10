<?php

namespace Modules\Identity\Enums;

enum Role: string
{
    case Admin = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Guardian = 'guardian';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Teacher => 'Guru',
            self::Student => 'Siswa',
            self::Guardian => 'Orang Tua',
        };
    }

    /** Urutan prioritas untuk menentukan dashboard utama user dengan banyak role. */
    public static function priority(): array
    {
        return [self::Admin, self::Teacher, self::Guardian, self::Student];
    }
}
