<?php

namespace App\Services;

use App\Actions\Personnel\SyncPersonnelUserRelationAction;
use App\Actions\Users\CreateUserAction;
use App\Actions\Users\SendUserInvitationAction;
use App\Actions\Users\UpdateUserAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UserService
{
    public function __construct(
        protected CreateUserAction $createUserAction,
        protected UpdateUserAction $updateUserAction,
        protected SendUserInvitationAction $sendUserInvitationAction,
        protected SyncPersonnelUserRelationAction $syncPersonnelUserRelationAction,
    )
    {
        //
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {

            $user = $this->createUserAction->execute($data);

            $this->sendUserInvitationAction->execute($user);

            $this->syncPersonnelUserRelationAction->execute($user->email);

            return $user->refresh();
        });
    }

    public function update(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data) {

            $user = $this->updateUserAction->execute($user, $data);

            $this->syncPersonnelUserRelationAction->execute($user->email);

            return $user->refresh();
        });
    }

    public function delete(User $user): void
    {
        $user->delete();
    }
}
