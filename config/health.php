<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Health Check Settings
    |--------------------------------------------------------------------------
    |
    | Configure application health monitoring and metrics endpoints.
    |
    */

    'enabled' => env('HEALTH_CHECK_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Health Check Secret
    |--------------------------------------------------------------------------
    |
    | Secret token required to access protected health endpoints (details, metrics).
    | Pass as X-Health-Secret header or ?secret= query parameter.
    | Leave empty to allow unrestricted access (NOT recommended for production).
    |
    */

    'secret' => env('HEALTH_CHECK_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Metrics Middleware
    |--------------------------------------------------------------------------
    |
    | Middleware to apply to the metrics endpoint.
    | In production, you may want to add authentication or IP restriction.
    |
    */

    'metrics_middleware' => array_filter([
        env('HEALTH_METRICS_AUTH_MIDDLEWARE'),
    ]),

    /*
    |--------------------------------------------------------------------------
    | Thresholds
    |--------------------------------------------------------------------------
    |
    | Define thresholds for health check warnings.
    |
    */

    'thresholds' => [
        // Database query latency threshold (ms)
        'database_latency_ms' => env('HEALTH_DB_LATENCY_THRESHOLD', 100),

        // Cache latency threshold (ms)
        'cache_latency_ms' => env('HEALTH_CACHE_LATENCY_THRESHOLD', 50),

        // Queue size warning threshold
        'queue_size_warning' => env('HEALTH_QUEUE_SIZE_WARNING', 1000),

        // Failed jobs warning threshold
        'failed_jobs_warning' => env('HEALTH_FAILED_JOBS_WARNING', 10),

        // Memory usage percentage warning
        'memory_percentage_warning' => env('HEALTH_MEMORY_WARNING', 80),
    ],

    /*
    |--------------------------------------------------------------------------
    | Checks to Run
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific health checks.
    |
    */

    'checks' => [
        'database' => env('HEALTH_CHECK_DATABASE', true),
        'cache' => env('HEALTH_CHECK_CACHE', true),
        'redis' => env('HEALTH_CHECK_REDIS', true),
        'storage' => env('HEALTH_CHECK_STORAGE', true),
        'queue' => env('HEALTH_CHECK_QUEUE', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Log health check results for monitoring.
    |
    */

    'logging' => [
        'enabled' => env('HEALTH_LOGGING_ENABLED', false),
        'channel' => env('HEALTH_LOG_CHANNEL', 'stack'),
        'log_healthy' => env('HEALTH_LOG_HEALTHY', false),
        'log_unhealthy' => env('HEALTH_LOG_UNHEALTHY', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Simple Endpoint
    |--------------------------------------------------------------------------
    |
    | Configure the simple /up endpoint for load balancer checks.
    |
    */

    'simple_endpoint' => [
        // Path for the simple health check (Laravel default: /up)
        'path' => '/up',

        // Include in route cache
        'cacheable' => true,
    ],

];
