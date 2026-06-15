<?php

namespace App\Enums\Sections;

enum AcademicStrand: string
{
    case STEM = 'STEM';
    case ABM = 'ABM';
    case HUMSS = 'HUMSS';
    case GAS = 'GAS';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
