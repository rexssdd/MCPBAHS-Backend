<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Railway (and most PaaS) sits behind a load balancer / reverse proxy.
        // Without trusting the proxy, $request->secure() returns false even on
        // HTTPS, which breaks Sanctum's secure cookie flag and HTTPS redirects.
        // '*' trusts all proxies — safe on Railway because only Railway's own
        // infrastructure can reach the container directly.
        $middleware->trustProxies(at: '*');

        // PROD-FIX-5: Apply throttle to all API routes (60 req/min per user/IP).
        // Without this, there was no rate limit on any authenticated API endpoint —
        // any script could hammer /reports, /announcements, etc. without restriction.
        $middleware->api(append: [
            'throttle:api',
        ]);

        // NOTE: EnsureFrontendRequestsAreStateful was previously prepended here,
        // but this app uses Sanctum *token* auth (Authorization: Bearer <token>),
        // not Sanctum's cookie-session SPA mode. With the stateful middleware
        // active, any request from a SANCTUM_STATEFUL_DOMAINS origin sending
        // credentials:"include" (e.g. the Vercel frontend) was being treated as
        // a CSRF-protected session request, causing 419 on /api/login since the
        // SPA never calls /sanctum/csrf-cookie. Removed — not needed for
        // cross-domain Bearer-token auth (Vercel frontend + Railway backend).
            'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
            'role' => \Spatie\Permission\Middleware\RoleMiddleware::class,
            'permission' => \Spatie\Permission\Middleware\PermissionMiddleware::class,
            'role_or_permission' => \Spatie\Permission\Middleware\RoleOrPermissionMiddleware::class,
        ]);

        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Authentication errors - 401 Unauthenticated
        $exceptions->render(function (Illuminate\Auth\AuthenticationException $e, Illuminate\Http\Request $request) {
            return response()->json([
                'message' => 'Unauthenticated.',
                'status' => 'unauthenticated',
            ], 401);
        });

        // Authorization errors - 403 Forbidden
        $exceptions->render(function (Illuminate\Auth\Access\AuthorizationException $e, Illuminate\Http\Request $request) {
            return response()->json([
                'message' => 'Unauthorized. ' . $e->getMessage(),
                'status' => 'forbidden',
            ], 403);
        });

        // Validation errors - 422 Unprocessable Entity
        $exceptions->render(function (Illuminate\Validation\ValidationException $e, Illuminate\Http\Request $request) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'status' => 'validation_error',
            ], 422);
        });

        // Model not found - 404 Not Found
        $exceptions->render(function (Illuminate\Database\Eloquent\ModelNotFoundException $e, Illuminate\Http\Request $request) {
            return response()->json([
                'message' => 'Resource not found.',
                'status' => 'not_found',
            ], 404);
        });

        // Domain logic errors - 422 Unprocessable Entity
        $exceptions->render(function (\DomainException $e, Illuminate\Http\Request $request) {
            return response()->json([
                'message' => $e->getMessage(),
                'status' => 'domain_error',
            ], 422);
        });

        // Log all errors in production
        $exceptions->report(function (Throwable $e) {
            if (app()->bound('sentry')) {
                app('sentry')->captureException($e);
            }
        });

        // Generic error fallback - 500 Internal Server Error
        $exceptions->render(function (Throwable $e, Illuminate\Http\Request $request) {
            $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
            
            $response = [
                'message' => 'An error occurred. Please try again later.',
                'status' => 'server_error',
            ];

            // Only show details in development
            if (config('app.debug')) {
                $response['exception'] = get_class($e);
                $response['message'] = $e->getMessage();
            }

            return response()->json($response, $statusCode);
        });
    })->create();
