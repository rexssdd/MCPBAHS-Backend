<?php

namespace App\Actions\Announcements;

use App\Enums\Announcements\AnnouncementStatus;
use App\Models\Announcement;

class UpdateAnnouncementAction
{
    public function execute(Announcement $announcement, array $data): Announcement
    {
        // BUG FIX: switching publish_mode to "now" (or "schedule") on an
        // existing draft/scheduled announcement previously left `status`
        // untouched, so it never moved past Draft/Scheduled — and never
        // got picked up by DispatchAnnouncementAction, which is what
        // actually flips it to Posted. Recompute status here exactly like
        // CreateAnnouncementAction does, whenever publish_mode is present
        // in the update payload.
        if (array_key_exists('publish_mode', $data)) {
            $data['status'] = match ($data['publish_mode']) {
                'schedule' => AnnouncementStatus::Scheduled->value,
                'now'      => AnnouncementStatus::Processing->value,
                default    => AnnouncementStatus::Draft->value,
            };

            if ($data['publish_mode'] === 'now') {
                $data['scheduled_at'] = now();
            }
        }

        $announcement->update($data);

        return $announcement;
    }
}