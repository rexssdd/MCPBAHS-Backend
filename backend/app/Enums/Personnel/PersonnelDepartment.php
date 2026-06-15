<?php

namespace App\Enums\Personnel;

enum PersonnelDepartment: string
{
    case ENGLISH = 'English';
    case FILIPINO = 'Filipino';
    case MATHEMATICS = 'Mathematics';
    case SCIENCE = 'Science';
    case ARALING_PANLIPUNAN = 'Araling Panlipunan';
    case MAPEH = 'MAPEH';
    case TLE = 'TLE';
    case VALUES_EDUCATION = 'Values Education';
    case ICT = 'ICT';
    case TVL = 'TVL';
    case HUMSS = 'HUMMS';
    case STEM = 'STEM';
    case ABM = 'ABM';
    case GAS = 'GAS';
    case ADMINISTRATION = 'Administration';
    case GUIDANCE = 'Guidance';
    case REGISTRAR = 'Registrar';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
