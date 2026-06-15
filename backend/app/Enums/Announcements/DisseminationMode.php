<?php

namespace App\Enums\Announcements;

enum DisseminationMode:string
{
    case Sms = 'sms';
    case InApp = 'in-app';
    case Email = 'email';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
