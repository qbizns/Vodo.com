<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rate Limiting Configuration
    |--------------------------------------------------------------------------
    |
    | This file defines rate limiting profiles and settings for the application.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Global toggle to enable/disable rate limiting.
    |
    */
    'enabled' => env('RATE_LIMIT_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Cache Store
    |--------------------------------------------------------------------------
    |
    | The cache store to use for rate limiting counters.
    |
    */
    'cache_store' => env('RATE_LIMIT_CACHE_STORE', 'default'),

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Profiles
    |--------------------------------------------------------------------------
    |
    | Define named profiles with specific limits and time windows.
    | Format: 'profile_name' => ['limit' => requests, 'window' => seconds]
    |
    */
    'profiles' => [
        // Default API rate limit
        'api' => [
            'limit' => (int) env('RATE_LIMIT_API', 60),
            'window' => 60,
        ],

        // Plugin installation (very restrictive)
        'plugin_install' => [
            'limit' => 5,
            'window' => 300,
        ],

        // Plugin activation/deactivation
        'plugin_activate' => [
            'limit' => 10,
            'window' => 60,
        ],

        // Entity record creation
        'entity_create' => [
            'limit' => 30,
            'window' => 60,
        ],

        // Entity record updates
        'entity_update' => [
            'limit' => 60,
            'window' => 60,
        ],

        // File uploads
        'upload' => [
            'limit' => 10,
            'window' => 60,
        ],

        // Authentication attempts
        'auth' => [
            'limit' => 5,
            'window' => 60,
        ],

        // Password reset requests
        'password_reset' => [
            'limit' => 3,
            'window' => 300,
        ],

        // Search requests
        'search' => [
            'limit' => 30,
            'window' => 60,
        ],

        // Export operations
        'export' => [
            'limit' => 5,
            'window' => 300,
        ],

        // Report generation
        'reports' => [
            'limit' => 10,
            'window' => 300,
        ],

        // Webhook endpoints
        'webhook' => [
            'limit' => 100,
            'window' => 60,
        ],

        // Admin operations
        'admin' => [
            'limit' => 120,
            'window' => 60,
        ],

        // Public/unauthenticated requests
        'public' => [
            'limit' => 30,
            'window' => 60,
        ],

        // Strict rate limit for sensitive operations
        'strict' => [
            'limit' => 5,
            'window' => 300,
        ],

        // Relaxed rate limit for read-only operations
        'relaxed' => [
            'limit' => 200,
            'window' => 60,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | IP Whitelist
    |--------------------------------------------------------------------------
    |
    | IP addresses that bypass rate limiting.
    |
    */
    'whitelist' => [
        '127.0.0.1',
        '::1',
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limit Headers
    |--------------------------------------------------------------------------
    |
    | Whether to include rate limit headers in responses.
    |
    */
    'headers' => [
        'enabled' => true,
        'limit' => 'X-RateLimit-Limit',
        'remaining' => 'X-RateLimit-Remaining',
        'reset' => 'X-RateLimit-Reset',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Settings for rate limit logging.
    |
    */
    'logging' => [
        // Log when rate limits are exceeded
        'log_exceeded' => true,

        // Log channel to use
        'channel' => env('RATE_LIMIT_LOG_CHANNEL', 'stack'),
    ],
];
