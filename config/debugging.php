<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Debugging Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the debugging and tracing system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Enable Debugging
    |--------------------------------------------------------------------------
    |
    | Global toggle for the debugging system. When disabled, all tracing
    | operations become no-ops.
    |
    */
    'enabled' => env('DEBUG_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Persist Traces
    |--------------------------------------------------------------------------
    |
    | When enabled, traces are persisted to the database for later analysis.
    | Disable for performance in production.
    |
    */
    'persist' => env('DEBUG_PERSIST', true),

    /*
    |--------------------------------------------------------------------------
    | Allowed IPs
    |--------------------------------------------------------------------------
    |
    | IP addresses that are allowed to enable debug mode in production.
    | Leave empty to disable IP-based restrictions.
    |
    */
    'allowed_ips' => array_filter(explode(',', env('DEBUG_ALLOWED_IPS', ''))),

    /*
    |--------------------------------------------------------------------------
    | Trace Retention Days
    |--------------------------------------------------------------------------
    |
    | Number of days to keep traces in the database. Older traces are
    | automatically pruned.
    |
    */
    'retention_days' => env('DEBUG_RETENTION_DAYS', 7),

    /*
    |--------------------------------------------------------------------------
    | Slow Query Threshold
    |--------------------------------------------------------------------------
    |
    | Queries taking longer than this (in milliseconds) are flagged as slow.
    |
    */
    'slow_query_threshold' => env('DEBUG_SLOW_QUERY_MS', 100),

    /*
    |--------------------------------------------------------------------------
    | Slow Trace Threshold
    |--------------------------------------------------------------------------
    |
    | Traces taking longer than this (in milliseconds) are flagged as slow.
    |
    */
    'slow_trace_threshold' => env('DEBUG_SLOW_TRACE_MS', 1000),

    /*
    |--------------------------------------------------------------------------
    | Max Trace Depth
    |--------------------------------------------------------------------------
    |
    | Maximum depth of nested traces to record. Deeper traces are ignored.
    |
    */
    'max_depth' => env('DEBUG_MAX_DEPTH', 50),

    /*
    |--------------------------------------------------------------------------
    | Exclude Paths
    |--------------------------------------------------------------------------
    |
    | Request paths that should not be traced.
    |
    */
    'exclude_paths' => [
        '_debugbar/*',
        'telescope/*',
        'horizon/*',
        'health',
        'livewire/*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Sanitize Fields
    |--------------------------------------------------------------------------
    |
    | Field names that should be redacted in traces for security.
    |
    */
    'sanitize_fields' => [
        'password',
        'password_confirmation',
        'secret',
        'token',
        'api_key',
        'credit_card',
        'card_number',
        'cvv',
        'ssn',
    ],
];
