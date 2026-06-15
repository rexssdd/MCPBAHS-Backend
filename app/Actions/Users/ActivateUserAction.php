<?php

namespace App\Actions\Users;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

class ActivateUserAction
{
    public function execute(User $user, string $password): User
    {
        $user->update([
            'password' => Hash::make($password),
            'account_status' => 'active',
            'email_verified_at' => now(),
            'invitation_accepted_at' => now(),
        ]);

        return $user->fresh();
    }
}
