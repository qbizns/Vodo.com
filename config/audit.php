<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Audit Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the audit logging system.
    |
    */

    // Enable/disable auditing globally
    'enabled' => env('AUDIT_ENABLED', true),

    // Days to keep audit logs before cleanup
    'retention_days' => env('AUDIT_RETENTION_DAYS', 90),

    // Maximum entries per page in queries
    'per_page' => 50,

    // Global excluded fields (never logged)
    'excluded_fields' => [
        'password',
        'remember_token',
        'api_token',
        'secret',
        'credit_card',
        'cvv',
        'ssn',
    ],

    // Entity-specific excluded fields
    'entity_excluded_fields' => [
        'App\\Models\\User' => ['password', 'remember_token'],
        // Add more as needed
    ],

    // Events to log
    'events' => [
        'create' => true,
        'update' => true,
        'delete' => true,
        'restore' => true,
        'login' => true,
        'logout' => true,
    ],

    // Models to automatically audit (if empty, use HasAudit trait)
    'auto_audit_models' => [
        // 'App\\Models\\Invoice',
        // 'App\\Models\\Order',
    ],

    // Queue audit logs for performance
    'queue' => [
        'enabled' => env('AUDIT_QUEUE_ENABLED', false),
        'connection' => env('AUDIT_QUEUE_CONNECTION', 'default'),
        'queue' => env('AUDIT_QUEUE_NAME', 'audits'),
    ],

    // Console commands
    'commands' => [
        'cleanup' => [
            'schedule' => 'daily', // daily, weekly, monthly
            'time' => '03:00',
        ],
    ],
];
