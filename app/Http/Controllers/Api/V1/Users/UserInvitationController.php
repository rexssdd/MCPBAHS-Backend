<?php

namespace App\Http\Controllers\Api\V1\Users;

use App\Actions\Users\ActivateUserAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteInvitationRequest;
use App\Http\Resources\UserResource;
use App\Models\User;

class UserInvitationController extends Controller
{
    public function __construct(private readonly ActivateUserAction $activateUserAction)
    {
        //
    }
    public function accept(User $user)
    {
        $user->ensureNotActive();

        return response()->json([
            'message' => 'Invitation is valid.',
            'data' => new UserResource($user),
        ]);
    }

    public function complete(CompleteInvitationRequest $request, User $user)
    {
        $user->ensureNotActive();

        $user = $this->activateUserAction->execute($user, $request->validated('password'));

        return response()->json([
            'message' => 'Account setup complete. You can now log in.',
            'data' => new UserResource($user),
        ]);
    }
}
