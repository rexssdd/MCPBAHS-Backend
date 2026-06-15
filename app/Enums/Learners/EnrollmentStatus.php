<?php

namespace App\Enums\Learners;

enum EnrollmentStatus: string
{
    case Pending = 'pending';
    case PartiallyEnrolled = 'partially enrolled';
    case Enrolled = 'enrolled';
    case Rejected = 'rejected';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
