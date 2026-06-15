<?php

namespace App\Services\Announcements;

use Illuminate\Support\Facades\Http;

class SmsService
{
    public function send(string $number, string $message): array
    {
        // Using config() instead of env() is required for production correctness.
        // After running `php artisan config:cache` (which is standard on every
        // deployment), Laravel reads all configuration from the compiled cache and
        // env() returns null for every key — silently breaking the API key lookup.
        // config() reads from the cache correctly. The actual env var is read once
        // in config/services.php and never touched at runtime.
        $response = Http::withHeaders([
            'x-api-key' => config('services.sms.key'),
        ])->post(
                'https://smsapiph.onrender.com/api/v1/send/sms',
                [
                    'recipient' => $number,
                    'message' => $message,
                ]
            );

        return $response->json();
    }
}
