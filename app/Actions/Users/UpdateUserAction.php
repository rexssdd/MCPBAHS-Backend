<?php

namespace App\Actions\Users;

use App\Models\User;

class UpdateUserAction
{
    public function execute(User $user, array $data): User
    {
        // Normalize role to lowercase before persisting so it stays consistent
        // with how Spatie stores role names ('admin', 'teacher', etc.).
        if (isset($data['role'])) {
            $data['role'] = strtolower($data['role']);
        }

        $user->update($data);

        // Keep Spatie role table in sync with the role column whenever the role
        // is being changed. Without this call, route middleware ('role:admin' etc.)
        // would still resolve the old role after an admin changes a user's role.
        if (isset($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        return $user->refresh();
    }
}