<?php

namespace App\Enums;

enum WeekDay: string
{
    case Monday = 'Monday';
    case Tuesday = 'Tuesday';
    case Wednesday = 'Wednesday';
    case Thursday = 'Thursday';
    case Friday = 'Friday';
    case Saturday = 'Saturday';
    case Sunday = 'Sunday';

    public static function values(): array
    {
        return array_column(
            self::cases(),
            'value'
        );
    }

}
