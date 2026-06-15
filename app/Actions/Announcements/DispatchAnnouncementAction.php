<?php

namespace App\Actions\Announcements;

use App\Enums\Announcements\AnnouncementStatus;
use App\Jobs\SendEmailJob;
use App\Jobs\SendInAppJob;
use App\Jobs\SendSmsJob;
use App\Models\Announcement;

class DispatchAnnouncementAction
{
    public function execute(Announcement $announcement): void
    {
        foreach ($announcement->dissemination_modes as $mode) {
            // A default arm prevents UnhandledMatchError if the DB contains a
            // mode that was added later (e.g. 'push') without a corresponding
            // job yet. Without the default, a single unknown mode crashes the
            // entire dispatch loop — meaning no channel gets notified at all,
            // and the status is never updated to Posted.
            match ($mode) {
                'sms'    => SendSmsJob::dispatch($announcement),
                'email'  => SendEmailJob::dispatch($announcement),
                'in-app' => SendInAppJob::dispatch($announcement),
                default  => null,
            };
        }

        $announcement->update([
            'status'    => AnnouncementStatus::Posted->value,
            'posted_at' => now(),
        ]);
    }
}
