<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendInAppJob implements ShouldQueue
{
    use Queueable;

    // FIX: was missing — failed jobs retried infinitely and silently.
    public int $tries = 3;
    public array $backoff = [60, 300];
    public int $timeout = 120;

    public function __construct(public Announcement $announcement)
    {
        //
    }

    public function handle(): void
    {
        // FIX (Bug 5): The original code called $user->notify(new AnnouncementNotification())
        // which uses Laravel's built-in 'database' channel. That channel writes to a
        // notifications table with columns: notifiable_type, notifiable_id, data, read_at —
        // none of which exist in this project's custom notifications table
        // (user_id, type, title, message, is_read). Using the wrong channel caused a SQL
        // error on every in-app dispatch. Replaced with a direct insert into the custom
        // Notification model so the schema matches.
        //
        // FIX: was User::all() — loaded every user into memory at once.
        // chunkById keeps memory usage constant.
        $announcementId = $this->announcement->uuid;
        $title          = $this->announcement->title ?? 'New Announcement';
        $message        = $this->announcement->message ?? '';

        User::query()->chunkById(100, function ($users) use ($announcementId, $title, $message) {
            foreach ($users as $user) {
                Notification::create([
                    'user_id' => $user->id,
                    'type'    => 'announcement',
                    'title'   => $title,
                    'message' => $message,
                ]);
            }
        });
    }

    // FIX: was missing — failures disappeared with no trace.
    public function failed(Throwable $exception): void
    {
        Log::error('SendInAppJob failed after all retries', [
            'announcement_id' => $this->announcement->id,
            'error'           => $exception->getMessage(),
        ]);
    }
}