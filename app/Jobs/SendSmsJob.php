<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\User;
use App\Services\Announcements\SmsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendSmsJob implements ShouldQueue
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

    public function handle(SmsService $sms): void
    {
        // FIX: chunk to avoid loading the entire users table into memory.
        User::query()->whereNotNull('phone')->chunkById(100, function ($users) use ($sms) {
            foreach ($users as $user) {
                $sms->send($user->phone, $this->announcement->message);
            }
        });
    }

    // FIX: was missing — failures disappeared with no trace.
    public function failed(Throwable $exception): void
    {
        Log::error('SendSmsJob failed after all retries', [
            'announcement_id' => $this->announcement->id,
            'error'           => $exception->getMessage(),
        ]);
    }
}
