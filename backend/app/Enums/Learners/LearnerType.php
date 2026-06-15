<?php

namespace App\Enums\Learners;

enum LearnerType: string
{
    case UpcomingGrade7 = 'upcoming grade 7';
    case OldStudent = 'old student';
    case Transferee = 'transferee';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
