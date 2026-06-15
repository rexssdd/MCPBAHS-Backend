<?php

namespace App\Actions\ClassSchedules;

use App\Models\ClassSchedule;

class UpdateClassScheduleAction
{
    public function execute(ClassSchedule $schedule, array $data): ClassSchedule
    {
        if (isset($data['days'])) {
            $data['days'] = collect($data['days'])
                ->unique()
                ->values()
                ->all();
        }

        $schedule->update($data);

        return $schedule->refresh();
    }
}
