<?php

namespace App\Actions\Personnel;

use App\Models\Personnel;
use App\Models\User;

class AssignPersonnelUserAction
{
    public function execute(Personnel $personnel, string $userUuid): Personnel
    {
        $user = User::where('uuid', $userUuid)->firstOrFail();

        $personnel->update([
            'user_id' => $user->id,
        ]);

        return $personnel->refresh();
    }
}
