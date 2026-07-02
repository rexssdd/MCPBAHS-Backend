<?php

namespace App\Enums\Announcements;

enum AnnouncementCategory: string
{
    case Event   = 'event';
    case Notice  = 'notice';
    case Holiday = 'holiday';
    case Exam    = 'exam';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function label(): string
    {
        return match ($this) {
            self::Event   => 'Event',
            self::Notice  => 'Notice',
            self::Holiday => 'Holiday',
            self::Exam    => 'Exam',
        };
    }
}