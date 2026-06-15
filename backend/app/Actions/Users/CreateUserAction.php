<?php

namespace App\Actions\Users;

use App\Enums\Users\AccountStatus;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class CreateUserAction
{
    public function execute(array $data): User
    {
        // Normalize role to lowercase so it matches Spatie role names ('admin',
        // 'teacher', etc.) regardless of how the frontend sends it ('Admin', 'TEACHER').
        $role = strtolower($data['role'] ?? 'teacher');

        $user = User::create([
            'name'               => $data['name'],
            'email'              => $data['email'],
            'password'           => Hash::make(str()->random(16)),
            'role'               => $role,
            'account_status'     => AccountStatus::PendingInvitation,
            'invitation_sent_at' => now(),
        ]);

        // BUG FIX: Auto-verify email so invited users can log in after accepting
        // the invitation. The 'verified' middleware on all API routes would otherwise
        // permanently block them with 403.
        $user->markEmailAsVerified();

        // Sync Spatie role so route middleware ('role:admin', 'role:teacher', etc.)
        // resolves correctly. Without this, every permission check via HasRoles
        // returns false for the newly-created user.
        $user->syncRoles([$role]);

        return $user;
    }
}