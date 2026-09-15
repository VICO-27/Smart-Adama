<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    | Restricted to FRONTEND_URL only — never wildcard in production.
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_filter([
        env('FRONTEND_URL', 'http://localhost:5173'),
        env('FRONTEND_URL_ALT', ''),          // optional second origin
        'http://localhost:5173',
        'http://localhost:5174',
    ]),

    'allowed_origins_patterns' => array_filter([
        // Allow private network IPs (192.168.*, 10.*, 172.16-31.*) in non-production for mobile devices on LAN
        env('APP_ENV') !== 'production'
            ? '#^https?://(localhost|127\.0\.0\.1|192\.168\.\d+\.\d+|10\.\d+\.\d+\.\d+|172\.(1[6-9]|2\d|3[0-1])\.\d+\.\d+)(:\d+)?$#'
            : null,
    ]),

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining', 'Retry-After'],

    'max_age' => 0,

    'supports_credentials' => false,

];
