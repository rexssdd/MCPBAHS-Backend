<?php

namespace App\Actions\Announcements;

use App\Enums\Announcements\AnnouncementStatus;
use App\Jobs\SendEmailJob;
use App\Jobs\SendInAppJob;
use App\Jobs\SendSmsJob;
use App\Models\Announcement;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchAnnouncementAction
{
    public function execute(Announcement $announcement): void
    {
        foreach ($announcement->dissemination_modes as $mode) {
            // BUG FIX: dispatch() for a single channel could throw (e.g. queue
            // connection misconfigured, or QUEUE_CONNECTION=sync executing the
            // job inline and it fails immediately). That exception used to
            // bubble up out of the whole loop, so the status update below never
            // ran and the announcement was stuck on "Processing" forever even
            // though "Publish Now" was selected. Catching per-channel means one
            // bad channel can't block the others or block the Posted status.
            try {
                match ($mode) {
                    'sms'    => SendSmsJob::dispatch($announcement),
                    'email'  => SendEmailJob::dispatch($announcement),
                    'in-app' => SendInAppJob::dispatch($announcement),
                    default  => null,
                };
            } catch (Throwable $e) {
                Log::error('Failed to dispatch announcement channel', [
                    'announcement_id' => $announcement->id,
                    'mode'            => $mode,
                    'error'           => $e->getMessage(),
                ]);
            }
        }

        // Always reaches Posted once "Publish Now" has been triggered —
        // channel delivery failures are logged above but never block this.
        $announcement->update([
            'status'    => AnnouncementStatus::Posted->value,
            'posted_at' => now(),
        ]);
    }
}