<?php

/**
 * API Configuration
 *
 * Configuration for API versioning, rate limiting, and general API behavior.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | API Versioning
    |--------------------------------------------------------------------------
    */
    'versioning' => [
        // Supported API versions
        'supported_versions' => ['1', '2'],

        // Default version when not specified
        'default_version' => '1',

        // Latest stable version
        'latest_version' => '1',

        // Deprecated versions (will show warning in response)
        'deprecated_versions' => [],

        // Version negotiation methods (in order of priority)
        'negotiation' => [
            'url',      // /api/v1/...
            'header',   // Accept: application/vnd.vodo.v1+json
            'custom',   // X-API-Version: 1
            'query',    // ?api-version=1
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    */
    'rate_limiting' => [
        // Enable rate limiting
        'enabled' => env('API_RATE_LIMIT_ENABLED', true),

        // Default limits per minute
        'limits' => [
            'default' => env('API_RATE_LIMIT', 60),
            'authenticated' => env('API_RATE_LIMIT_AUTH', 120),
            'admin' => env('API_RATE_LIMIT_ADMIN', 300),
        ],

        // Endpoint-specific limits
        'endpoints' => [
            'auth.login' => 5,       // 5 per minute
            'auth.register' => 3,    // 3 per minute
            'password.reset' => 3,   // 3 per minute
            'export.*' => 10,        // 10 per minute
            'bulk.*' => 10,          // 10 per minute
        ],

        // Rate limit headers
        'headers' => [
            'limit' => 'X-RateLimit-Limit',
            'remaining' => 'X-RateLimit-Remaining',
            'reset' => 'X-RateLimit-Reset',
        ],

        // Bypass rate limiting for these IPs
        'bypass_ips' => array_filter(explode(',', env('API_RATE_LIMIT_BYPASS_IPS', ''))),
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Limits
    |--------------------------------------------------------------------------
    */
    'request' => [
        // Maximum request body size
        'max_size' => env('API_MAX_REQUEST_SIZE', '10M'),

        // Maximum JSON depth
        'max_json_depth' => 20,

        // Request timeout (seconds)
        'timeout' => env('API_TIMEOUT', 30),

        // Maximum file upload size
        'max_upload_size' => env('API_MAX_UPLOAD_SIZE', '50M'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    */
    'response' => [
        // Default pagination
        'pagination' => [
            'default_per_page' => 15,
            'max_per_page' => 100,
        ],

        // Default response format
        'format' => 'json',

        // Include debug info in non-production
        'debug' => env('API_DEBUG', false),

        // Compression
        'compression' => [
            'enabled' => true,
            'min_size' => 1024, // Compress responses > 1KB
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    */
    'auth' => [
        // Token expiration (minutes)
        'token_expiration' => env('API_TOKEN_EXPIRATION', 60),

        // Refresh token expiration (days)
        'refresh_token_expiration' => env('API_REFRESH_TOKEN_EXPIRATION', 30),

        // API key prefix
        'key_prefix' => 'vodo_',

        // Allowed authentication methods
        'methods' => ['sanctum', 'api_key'],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    */
    'cors' => [
        // Allowed origins (use * for all, or array of domains)
        'allowed_origins' => array_filter(explode(',', env('CORS_ALLOWED_ORIGINS', '*'))),

        // Allowed methods
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],

        // Allowed headers
        'allowed_headers' => [
            'Content-Type',
            'Authorization',
            'X-Requested-With',
            'X-API-Version',
            'X-Request-ID',
        ],

        // Exposed headers
        'exposed_headers' => [
            'X-API-Version',
            'X-RateLimit-Limit',
            'X-RateLimit-Remaining',
            'X-RateLimit-Reset',
            'X-Request-ID',
        ],

        // Max age for preflight cache
        'max_age' => 86400, // 24 hours

        // Allow credentials
        'supports_credentials' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Documentation
    |--------------------------------------------------------------------------
    */
    'docs' => [
        // Enable API documentation
        'enabled' => env('API_DOCS_ENABLED', true),

        // Documentation URL
        'url' => '/api/docs',

        // OpenAPI version
        'openapi_version' => '3.0.3',

        // API info
        'info' => [
            'title' => env('APP_NAME', 'Vodo') . ' API',
            'version' => '1.0.0',
            'description' => 'Enterprise Business Platform API',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    */
    'webhooks' => [
        // Enable webhooks
        'enabled' => env('WEBHOOKS_ENABLED', true),

        // Webhook timeout (seconds)
        'timeout' => 30,

        // Retry configuration
        'retries' => [
            'max_attempts' => 3,
            'backoff' => [30, 300, 3600], // seconds
        ],

        // Signature algorithm
        'signature_algorithm' => 'sha256',
    ],
];
