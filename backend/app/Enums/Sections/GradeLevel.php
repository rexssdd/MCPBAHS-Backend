<?php

namespace App\Enums\Sections;

enum GradeLevel: string
{
    case Grade7 = 'Grade 7';
    case Grade8 = 'Grade 8';
    case Grade9 = 'Grade 9';
    case Grade10 = 'Grade 10';

    case Grade11 = 'Grade 11';
    case Grade12 = 'Grade 12';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    // doubting my existence as a programmer if this is the
    // only way to check if the grade level is senior high or not
    public function isSeniorHigh(): bool
    {
        return in_array($this, [
            self::Grade11,
            self::Grade12,
        ]);
    }
}
