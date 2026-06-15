<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Notifications\AnnouncementNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendEmailJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public array $backoff = [60, 300];
    public int $timeout = 120;

    public function __construct(public Announcement $announcement)
    {
        //
    }

    public function handle(): void
    {
        // Mail::raw() was replaced with a proper Notification so that emails
        // use the application's configured mail template (branding, footer,
        // unsubscribe link) instead of bare plain-text. AnnouncementNotification
        // already implements toMail() for exactly this purpose.
        //
        // We add the 'mail' channel dynamically via onDemand so that sending
        // an email does not require the user to have a notification preference
        // record — every user with an email address receives the announcement.
        User::query()->whereNotNull('email')->chunkById(100, function ($users) {
            foreach ($users as $user) {
                $user->notify(new AnnouncementNotification($this->announcement, ['mail']));
            }
        });
    }

    public function failed(Throwable $exception): void
    {
        Log::error('SendEmailJob failed after all retries', [
            'announcement_id' => $this->announcement->id,
            'error'           => $exception->getMessage(),
        ]);
    }
}
