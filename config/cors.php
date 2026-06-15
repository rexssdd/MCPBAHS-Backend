<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Supports multiple origins via comma-separated FRONTEND_URL env var.
    // e.g. FRONTEND_URL=https://your-domain.com,https://www.your-domain.com
    // Localhost ports 3000 and 5173 are always included as safe fallbacks.
    'allowed_origins' => array_filter(array_unique(array_merge(
        ['http://localhost:3000', 'http://localhost:5173'],
        env('FRONTEND_URL')
            ? array_map('trim', explode(',', env('FRONTEND_URL')))
            : []
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    // PROD-FIX-6: max_age was 0 — every CORS pre-flight OPTIONS request was sent
    // to the server with no caching. Setting 7200 (2 hours) tells browsers to
    // cache the pre-flight result, eliminating one round-trip per unique
    // method+header combination. This is the recommended value for production SPAs.
    'max_age' => 7200,

    'supports_credentials' => true,

];
