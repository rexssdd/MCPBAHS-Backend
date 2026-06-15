<?php

namespace App\Services;

use App\Actions\Personnel\AssignPersonnelUserAction;
use App\Actions\Personnel\CreatePersonnelAction;
use App\Actions\Personnel\SyncPersonnelUserRelationAction;
use App\Actions\Personnel\UpdatePersonnelAction;
use App\Enums\Personnel\EmploymentStatus;
use App\Models\Personnel;
use Illuminate\Support\Facades\DB;

class PersonnelService
{
    public function __construct(
        private CreatePersonnelAction $createPersonnelAction,
        private UpdatePersonnelAction $updatePersonnelAction,
        private AssignPersonnelUserAction $assignPersonnelUserAction,
        private SyncPersonnelUserRelationAction $syncPersonnelUserRelationAction
    )
    {
        //
    }

    public function create(array $data): Personnel
    {
        return DB::transaction(function () use ($data) {
            $personnel = $this->createPersonnelAction->execute($data);

            $this->syncPersonnelUserRelationAction->execute($personnel->email);

            return $personnel->refresh();
        });
    }

    public function update(Personnel $personnel, array $data): Personnel
    {
        return DB::transaction(
            fn() => $this->updatePersonnelAction->execute($personnel, $data)
        );
    }

    public function delete(Personnel $personnel): void
    {
        // $personnel->update([
        //     'employment_status' => EmploymentStatus::Retired
        // ]);
        $personnel->delete();
    }

    public function restore(Personnel $personnel): void
    {
        // $personnel->update([
        //     'employment_status' => EmploymentStatus::Active
        // ]);
        $personnel->restore();
    }

    public function forceDelete(Personnel $personnel): void
    {
        $personnel->forceDelete();
    }

    // public function assignUser(Personnel $personnel, int $userId): Personnel
    // {
    //     return DB::transaction(
    //         fn() => $this->assignPersonnelUserAction->execute($personnel, $userId)
    //     );
    // }
}
