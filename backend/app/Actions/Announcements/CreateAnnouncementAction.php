<?php

namespace App\Actions\Announcements;

use App\Models\Announcement;
use App\Enums\Announcements\AnnouncementStatus;

class CreateAnnouncementAction
{
    public function execute(array $data): Announcement
    {
        // A default arm prevents UnhandledMatchError if a future publish_mode
        // value is introduced or the method is called without going through
        // StoreAnnouncementRequest validation. Draft is the safest fallback —
        // it never auto-dispatches and can be reviewed before publishing.
        $data['status'] = match ($data['publish_mode'] ?? 'draft') {
            'schedule' => AnnouncementStatus::Scheduled->value,
            'now'      => AnnouncementStatus::Processing->value,
            default    => AnnouncementStatus::Draft->value,
        };

        if (($data['publish_mode'] ?? '') === 'now') {
            $data['scheduled_at'] = now();
        }

        return Announcement::create($data);
    }
}
