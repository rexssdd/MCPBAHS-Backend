<?php

return [

    'paths' => ['*'],

    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

    // Exact-match origins — your stable domains (production + localhost dev).
    // Supports multiple origins via comma-separated FRONTEND_URL env var.
    // e.g. FRONTEND_URL=https://your-domain.com,https://www.your-domain.com
    'allowed_origins' => array_filter(array_unique(array_merge(
        ['http://localhost:3000', 'http://localhost:5173'],
        env('FRONTEND_URL')
            ? array_map('trim', explode(',', env('FRONTEND_URL')))
            : []
    ))),

    // Vercel preview deployments get a random subdomain on every deploy
    // (e.g. mcpbahs-gprppbjen-rlusica545469-4827s-projects.vercel.app), so an
    // exact-match allowed_origins list can never keep up with them. Match any
    // Vercel preview/prod URL under this project with a regex pattern instead.
    'allowed_origins_patterns' => [
        '#^https://mcpbahs(-[a-z0-9]+)*-rlusica545469-4827s-projects\.vercel\.app$#',
    ],

    'allowed_headers' => ['Content-Type', 'Authorization', 'Accept', 'X-Requested-With', 'X-XSRF-TOKEN'],

    'exposed_headers' => [],

    'max_age' => 7200,

    'supports_credentials' => true,

];