<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;

class UpdateAnnouncementAction
{
    public function execute(Announcement $announcement, array $data): Announcement
    {
        $announcement->update($data);

        return $announcement;
    }
}
