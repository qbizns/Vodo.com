<?php

/**
 * Production Configuration
 *
 * This file contains production-specific configuration settings and recommendations.
 * It should be included or referenced during deployment.
 *
 * CRITICAL: Review all settings before deploying to production!
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    */
    'security' => [
        // Force HTTPS
        'force_https' => env('FORCE_HTTPS', true),

        // HSTS headers
        'hsts' => [
            'enabled' => env('HSTS_ENABLED', true),
            'max_age' => env('HSTS_MAX_AGE', 31536000), // 1 year
            'include_subdomains' => true,
            'preload' => false,
        ],

        // Content Security Policy
        'csp' => [
            'enabled' => env('CSP_ENABLED', true),
            'report_only' => env('CSP_REPORT_ONLY', false),
            'report_uri' => env('CSP_REPORT_URI'),
        ],

        // Session security
        'session' => [
            'lifetime' => env('SESSION_LIFETIME', 120),
            'secure' => true,
            'http_only' => true,
            'same_site' => 'lax',
        ],

        // Password requirements
        'password' => [
            'min_length' => 12,
            'require_uppercase' => true,
            'require_lowercase' => true,
            'require_numbers' => true,
            'require_symbols' => true,
            'max_age_days' => 90,
            'check_pwned' => env('PASSWORD_CHECK_PWNED', true),
        ],

        // API security
        'api' => [
            'rate_limit' => env('API_RATE_LIMIT', 60),
            'rate_limit_window' => env('API_RATE_LIMIT_WINDOW', 1), // minutes
            'max_request_size' => env('API_MAX_REQUEST_SIZE', '10M'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Performance Settings
    |--------------------------------------------------------------------------
    */
    'performance' => [
        // Caching
        'cache' => [
            'driver' => env('CACHE_DRIVER', 'redis'),
            'ttl' => env('CACHE_TTL', 3600),
            'prefix' => env('CACHE_PREFIX', 'vodo_prod_'),
        ],

        // Database
        'database' => [
            'pool_size' => env('DB_POOL_SIZE', 20),
            'query_timeout' => env('DB_QUERY_TIMEOUT', 30),
            'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // ms
        ],

        // Queue
        'queue' => [
            'driver' => env('QUEUE_CONNECTION', 'redis'),
            'retry_after' => env('QUEUE_RETRY_AFTER', 90),
            'max_tries' => env('QUEUE_MAX_TRIES', 3),
            'timeout' => env('QUEUE_TIMEOUT', 60),
        ],

        // OPcache
        'opcache' => [
            'enabled' => true,
            'memory_consumption' => 256,
            'max_accelerated_files' => 20000,
            'validate_timestamps' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Settings
    |--------------------------------------------------------------------------
    */
    'logging' => [
        'channel' => env('LOG_CHANNEL', 'stack'),
        'level' => env('LOG_LEVEL', 'warning'),

        // Log sensitive data masking
        'mask_fields' => [
            'password',
            'password_confirmation',
            'secret',
            'api_key',
            'token',
            'credit_card',
            'ssn',
        ],

        // Structured logging
        'structured' => [
            'enabled' => true,
            'format' => 'json',
        ],

        // External logging services
        'services' => [
            'sentry_dsn' => env('SENTRY_DSN'),
            'papertrail' => [
                'host' => env('PAPERTRAIL_HOST'),
                'port' => env('PAPERTRAIL_PORT'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring Settings
    |--------------------------------------------------------------------------
    */
    'monitoring' => [
        // Health check
        'health' => [
            'enabled' => true,
            'endpoints' => [
                'health' => '/api/health',
                'ready' => '/api/health/ready',
                'live' => '/api/health/live',
            ],
        ],

        // Metrics
        'metrics' => [
            'enabled' => env('METRICS_ENABLED', true),
            'middleware' => ['auth:sanctum', 'can:view-metrics'],
        ],

        // Alerts
        'alerts' => [
            'email' => env('ALERT_EMAIL'),
            'slack_webhook' => env('ALERT_SLACK_WEBHOOK'),
            'pagerduty_key' => env('PAGERDUTY_INTEGRATION_KEY'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    */
    'backup' => [
        'enabled' => env('BACKUP_ENABLED', true),
        'schedule' => env('BACKUP_SCHEDULE', '0 2 * * *'), // 2 AM daily
        'retention_days' => env('BACKUP_RETENTION_DAYS', 30),
        'destination' => env('BACKUP_DESTINATION', 's3'),
        'encrypt' => env('BACKUP_ENCRYPT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Deployment Checklist
    |--------------------------------------------------------------------------
    |
    | BEFORE DEPLOYING TO PRODUCTION:
    |
    | 1. Environment:
    |    - [ ] APP_ENV=production
    |    - [ ] APP_DEBUG=false
    |    - [ ] APP_KEY is set (php artisan key:generate)
    |    - [ ] All required env variables are set
    |
    | 2. Security:
    |    - [ ] FORCE_HTTPS enabled
    |    - [ ] SESSION_SECURE_COOKIE=true
    |    - [ ] Database credentials are secure
    |    - [ ] API rate limiting is configured
    |    - [ ] CORS is properly configured
    |
    | 3. Performance:
    |    - [ ] Run: php artisan config:cache
    |    - [ ] Run: php artisan route:cache
    |    - [ ] Run: php artisan view:cache
    |    - [ ] Run: php artisan optimize
    |    - [ ] Redis/Memcached configured for cache/session
    |    - [ ] Queue workers are running
    |
    | 4. Database:
    |    - [ ] Run: php artisan migrate --force
    |    - [ ] Database backups configured
    |    - [ ] Connection pooling configured
    |
    | 5. Monitoring:
    |    - [ ] Error tracking (Sentry, etc.) configured
    |    - [ ] Health check endpoints accessible
    |    - [ ] Log aggregation configured
    |    - [ ] Alerting configured
    |
    */
];
