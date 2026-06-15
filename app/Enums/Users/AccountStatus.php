<?php

namespace App\Enums\Users;

enum AccountStatus: string
{
    case PendingInvitation = 'pending_invitation';
    case Active = 'active';
    case Deactivated = 'deactivated';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
