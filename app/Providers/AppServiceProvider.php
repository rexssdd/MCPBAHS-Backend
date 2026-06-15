<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // No bindings needed
    }

    public function boot(): void
    {
        /*
        |------------------------------------------------------------
        | PASSWORD RESET URL (FRONTEND)
        |------------------------------------------------------------
        | Redirects reset flow to frontend application.
        */
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {

            $frontend = rtrim(config('app.frontend_url', ''), '/');

            return $frontend
                . '/password-reset/' . $token
                . '?email=' . urlencode($notifiable->getEmailForPasswordReset());
        });

        /*
        |------------------------------------------------------------
        | API RATE LIMITER (ROLE-BASED)
        |------------------------------------------------------------
        | Teacher   → restricted
        | Admin     → high usage
        | Principal → high usage
        | Guest     → default IP-based limit
        */
        RateLimiter::for('api', function (Request $request) {

            $user = $request->user();
            $key  = $user?->id ?: $request->ip();

            if (!$user) {
                return Limit::perMinute(60)->by($key);
            }

            if ($user->hasRole('teacher')) {
                return Limit::perMinute(40)->by($key);
            }

            if ($user->hasAnyRole(['admin', 'principal'])) {
                return Limit::perMinute(120)->by($key);
            }

            return Limit::perMinute(60)->by($key);
        });
    }
}