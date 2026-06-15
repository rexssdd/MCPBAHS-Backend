<?php

namespace App\Actions\Personnel;

use App\Models\Personnel;
use App\Models\User;

class SyncPersonnelUserRelationAction
{
    public function execute(string $email): void
    {
        $user = User::query()
            ->where('email', $email)
            ->first();

        $personnel = Personnel::query()
            ->where('email', $email)
            ->whereNull('user_id')
            ->first();

        if (! $user || ! $personnel) {
            return;
        }

        $personnel->update([
            'user_id' => $user->id,
        ]);
    }
}
