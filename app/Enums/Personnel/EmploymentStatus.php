<?php

namespace App\Enums\Personnel;

enum EmploymentStatus: string
{
    case Active = 'Active';
    case OnLeave = 'On Leave';
    case Resigned = 'Resigned';
    case Retired = 'Retired';


    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
