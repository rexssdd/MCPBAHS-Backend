<?php

namespace App\Actions\ClassSchedules;

use App\Models\ClassSchedule;

class CreateClassScheduleAction
{
    public function execute(array $data): ClassSchedule
    {
        $data['days'] = collect($data['days'])
            ->unique()
            ->values()
            ->all();

        return ClassSchedule::create($data);
    }
}
