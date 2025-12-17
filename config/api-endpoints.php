<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Auto-Register Routes
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically register all active endpoints
    | as routes when the application boots.
    |
    */

    'auto_register_routes' => true,

    /*
    |--------------------------------------------------------------------------
    | Global Logging
    |--------------------------------------------------------------------------
    |
    | When enabled, all API requests will be logged automatically.
    |
    */

    'global_logging' => true,

    /*
    |--------------------------------------------------------------------------
    | API Documentation
    |--------------------------------------------------------------------------
    |
    | Configuration for auto-generated API documentation.
    |
    */

    'documentation' => [
        'title' => env('APP_NAME', 'Laravel') . ' API',
        'description' => 'API Documentation',
        'version' => '1.0.0',
        'contact' => [
            'name' => 'API Support',
            'email' => env('MAIL_FROM_ADDRESS', 'support@example.com'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default API Version
    |--------------------------------------------------------------------------
    |
    | The default API version used when none is specified.
    |
    */

    'default_version' => 'v1',

    /*
    |--------------------------------------------------------------------------
    | API Key Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for API key authentication.
    |
    */

    'api_key_header' => ['X-API-Key', 'Authorization'],
    'api_key_param' => 'api_key',
    'require_signed_requests' => false,

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limiting configuration for API endpoints.
    |
    */

    'rate_limiting' => [
        'enabled' => true,
        'default_limit' => 60, // requests per minute
        'by' => 'ip', // ip, user, api_key
    ],

    /*
    |--------------------------------------------------------------------------
    | Request Logging
    |--------------------------------------------------------------------------
    |
    | Configuration for request logging.
    |
    */

    'logging' => [
        'enabled' => true,
        'async' => false, // Use queue for logging
        'sample_rate' => 100, // Percentage of requests to log (1-100)
        'retention_days' => 30, // Days to keep logs
        'log_headers' => true,
        'log_params' => true,
        'sensitive_headers' => [
            'authorization',
            'cookie',
            'x-api-key',
            'x-api-secret',
        ],
        'sensitive_params' => [
            'password',
            'password_confirmation',
            'secret',
            'token',
            'api_key',
            'credit_card',
            'cvv',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Cross-Origin Resource Sharing configuration for API endpoints.
    |
    */

    'cors' => [
        'enabled' => true,
        'allowed_origins' => ['*'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        'allowed_headers' => ['Content-Type', 'Authorization', 'X-API-Key', 'X-Requested-With'],
        'exposed_headers' => ['X-RateLimit-Limit', 'X-RateLimit-Remaining'],
        'max_age' => 86400,
        'supports_credentials' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Response Configuration
    |--------------------------------------------------------------------------
    |
    | Default response configuration.
    |
    */

    'response' => [
        'wrap_responses' => true, // Wrap all responses in {success: ..., data: ...}
        'include_debug' => env('APP_DEBUG', false), // Include debug info in errors
        'pretty_print' => env('APP_DEBUG', false), // Pretty print JSON
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Routes
    |--------------------------------------------------------------------------
    |
    | Configuration for admin management routes.
    |
    */

    'admin' => [
        'prefix' => 'api/v1/admin',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific event hooks.
    |
    */

    'hooks' => [
        'api_endpoint_registered' => true,
        'api_endpoint_updated' => true,
        'api_endpoint_unregistered' => true,
        'api_endpoints_ready' => true,
        'api_key_created' => true,
        'api_key_revoked' => true,
        'api_request_logged' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security-related configuration.
    |
    */

    'security' => [
        'require_https' => env('APP_ENV') === 'production',
        'validate_content_type' => true,
        'max_request_size' => 10 * 1024 * 1024, // 10MB
    ],

];
