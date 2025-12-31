<?php

/**
 * Plugin Security Configuration
 *
 * Configuration for plugin permissions, sandboxing, and API keys.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Sandbox Configuration
    |--------------------------------------------------------------------------
    |
    | Control plugin resource usage and isolation.
    |
    */
    'sandbox' => [
        // Enable sandbox enforcement (recommended for production)
        'enabled' => env('PLUGIN_SANDBOX_ENABLED', false),

        // Auto-deactivate plugins that exceed limits
        'auto_deactivate' => env('PLUGIN_SANDBOX_AUTO_DEACTIVATE', true),

        // Default resource limits
        'limits' => [
            // Memory limit per plugin execution (MB)
            'memory_mb' => env('PLUGIN_SANDBOX_MEMORY_MB', 256),

            // Maximum execution time per request (seconds)
            'execution_time_seconds' => env('PLUGIN_SANDBOX_EXEC_TIME', 30),

            // API request rate limits
            'api_requests_per_minute' => env('PLUGIN_SANDBOX_API_RPM', 60),
            'api_requests_per_hour' => env('PLUGIN_SANDBOX_API_RPH', 1000),
            'api_requests_per_day' => env('PLUGIN_SANDBOX_API_RPD', 10000),

            // Hook execution rate limits
            'hook_executions_per_minute' => env('PLUGIN_SANDBOX_HOOKS_RPM', 100),

            // Entity operation rate limits
            'entity_reads_per_minute' => env('PLUGIN_SANDBOX_READS_RPM', 500),
            'entity_writes_per_minute' => env('PLUGIN_SANDBOX_WRITES_RPM', 100),

            // Storage limit (MB)
            'storage_mb' => env('PLUGIN_SANDBOX_STORAGE_MB', 50),

            // Network limits
            'network_requests_per_minute' => env('PLUGIN_SANDBOX_NET_RPM', 30),
            'network_bytes_per_day' => env('PLUGIN_SANDBOX_NET_BYTES', 104857600), // 100MB

            // Error thresholds
            'max_consecutive_errors' => env('PLUGIN_SANDBOX_MAX_ERRORS', 10),
        ],

        // Violation handling
        'violations' => [
            // Number of violations before auto-block
            'threshold' => env('PLUGIN_SANDBOX_VIOLATION_THRESHOLD', 5),

            // Time window for counting violations (minutes)
            'window_minutes' => env('PLUGIN_SANDBOX_VIOLATION_WINDOW', 60),

            // Block duration when threshold exceeded (minutes)
            'block_duration_minutes' => env('PLUGIN_SANDBOX_BLOCK_DURATION', 1440), // 24 hours
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | API Key Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for plugin API key authentication.
    |
    */
    'api_keys' => [
        // Default rate limits for new API keys
        'default_rate_limit_per_minute' => env('PLUGIN_API_KEY_RPM', 60),
        'default_rate_limit_per_hour' => env('PLUGIN_API_KEY_RPH', 1000),
        'default_rate_limit_per_day' => env('PLUGIN_API_KEY_RPD', 10000),

        // Default expiration (days, null for no expiration)
        'default_expiration_days' => env('PLUGIN_API_KEY_EXPIRY', null),

        // Allow API keys in query parameters (not recommended)
        'allow_query_param' => env('PLUGIN_API_KEY_QUERY_PARAM', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Permission Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for plugin permission management.
    |
    */
    'permissions' => [
        // Cache TTL for permission lookups (seconds)
        'cache_ttl' => env('PLUGIN_PERMISSION_CACHE_TTL', 300),

        // Require approval for dangerous scopes
        'require_approval_for_dangerous' => env('PLUGIN_PERMISSION_REQUIRE_APPROVAL', true),

        // Auto-grant safe scopes on activation
        'auto_grant_safe_scopes' => env('PLUGIN_PERMISSION_AUTO_GRANT', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for security audit logging.
    |
    */
    'audit' => [
        // Enable audit logging
        'enabled' => env('PLUGIN_AUDIT_ENABLED', true),

        // Log level for audit events
        'min_severity' => env('PLUGIN_AUDIT_MIN_SEVERITY', 'info'),

        // Retention period for audit logs (days)
        'retention_days' => env('PLUGIN_AUDIT_RETENTION', 90),

        // Events to log (null for all)
        'events' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Scope Definitions
    |--------------------------------------------------------------------------
    |
    | Override or extend scope definitions.
    |
    */
    'scopes' => [
        // Maximum risk level to auto-grant (1-5)
        'max_auto_grant_risk' => env('PLUGIN_SCOPE_MAX_AUTO_RISK', 2),
    ],
];
