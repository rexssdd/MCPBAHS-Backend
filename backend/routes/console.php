<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// PROD-FIX-4: DispatchScheduledAnnouncements command existed but was never
// registered with the scheduler — scheduled announcements were never dispatched
// automatically. The command must run every minute so scheduled_at times are
// honoured within a one-minute window.
//
// Deployment checklist:
//   • Add to crontab: * * * * * php /var/www/html/artisan schedule:run >> /dev/null 2>&1
//   • Or use Laravel Octane / Forge / Vapor scheduler integration.
Schedule::command('announcements:dispatch-scheduled')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
