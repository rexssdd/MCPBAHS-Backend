<?php

namespace App\Actions\Users;

use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Support\Facades\URL;

class SendUserInvitationAction
{
    public function execute(User $user): void
    {
        $signedUrl = URL::temporarySignedRoute(
            'user.invitation.accept',
            now()->addHours(24),
            [
                'user' => $user->uuid,
            ]
        );

        $frontendUrl = config('app.frontend_url')
            . '/accept-invitation?url='
            . urlencode($signedUrl);

        $user->notify(
            new UserInvitationNotification($frontendUrl)
        );
    }
}
