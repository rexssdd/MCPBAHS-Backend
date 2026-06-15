<?php

namespace App\Services\ClassSchedules;

use Illuminate\Support\Facades\DB;
use App\Models\ClassSchedule;
use App\Actions\ClassSchedules\CreateClassScheduleAction;
use App\Actions\ClassSchedules\UpdateClassScheduleAction;

class ClassScheduleService
{
    public function __construct(
        protected CreateClassScheduleAction $createAction,
        protected UpdateClassScheduleAction $updateAction,
        protected ClassScheduleConflictService $conflictService,
    ) {
    }

    public function create(array $data): ClassSchedule
    {
        return DB::transaction(function () use ($data) {

            $this->conflictService->validate($data);

            return $this->createAction->execute($data);
        });
    }

    public function update(ClassSchedule $schedule, array $data): ClassSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {

            $merged = array_merge($schedule->toArray(), $data);

            $this->conflictService->validate($merged, $schedule->id);

            return $this->updateAction->execute($schedule, $data);
        });
    }

    public function delete(ClassSchedule $schedule): void
    {
        $schedule->delete();
    }
}
