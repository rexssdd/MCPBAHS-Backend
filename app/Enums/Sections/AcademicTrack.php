<?php

namespace App\Enums\Sections;

enum AcademicTrack: string
{
    // temporary whatnot values, can be changed later on when we have the actual tracks and strands
    case Academic = 'Academic';
    case TVL = 'TVL';
    case Sports = 'Sports';
    case ArtsAndDesign = 'Arts and Design';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
