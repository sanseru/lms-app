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
            self::Teacher => 'Teacher',
            self::Student => 'Student',
            self::Guardian => 'Parent',
        };
    }

    /** Priority order that decides the main dashboard for users with several roles. */
    public static function priority(): array
    {
        return [self::Admin, self::Teacher, self::Guardian, self::Student];
    }
}
