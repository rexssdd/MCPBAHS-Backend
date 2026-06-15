<?php

namespace App\Enums\Announcements;

enum TargetAudience:string
{
    case All = 'all';
    case Students = 'students';
    case Teachers = 'teachers';
    case Staff = 'staff';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
