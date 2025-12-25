<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Integration Platform Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the integration platform including connectors,
    | credentials, flows, and execution settings.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | The encryption key used for storing credentials securely.
    | Defaults to the application key if not set.
    |
    */
    'encryption_key' => env('INTEGRATION_ENCRYPTION_KEY', env('APP_KEY')),

    /*
    |--------------------------------------------------------------------------
    | Credential Storage
    |--------------------------------------------------------------------------
    |
    | Configuration for credential storage.
    |
    */
    'credentials' => [
        'table' => 'integration_credentials',
        'ttl' => 3600, // Cache TTL in seconds
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Configuration
    |--------------------------------------------------------------------------
    |
    | Default OAuth settings.
    |
    */
    'oauth' => [
        'state_ttl' => 900, // 15 minutes
        'redirect_uri' => env('INTEGRATION_OAUTH_REDIRECT_URI'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for webhook handling.
    |
    */
    'webhooks' => [
        'verify_signatures' => true,
        'timeout' => 30,
        'retry_attempts' => 3,
    ],

    /*
    |--------------------------------------------------------------------------
    | Polling Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for polling triggers.
    |
    */
    'polling' => [
        'default_interval' => 300, // 5 minutes
        'min_interval' => 60, // 1 minute minimum
        'max_interval' => 86400, // 24 hours maximum
    ],

    /*
    |--------------------------------------------------------------------------
    | Execution Configuration
    |--------------------------------------------------------------------------
    |
    | Flow execution settings.
    |
    */
    'execution' => [
        'max_time' => 300, // 5 minutes max per flow
        'max_nodes' => 1000, // Max nodes per execution
        'max_loop_iterations' => 10000,
        'default_timeout' => 30, // Default action timeout
        'queue' => 'integrations', // Queue name
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Default rate limiting settings.
    |
    */
    'rate_limits' => [
        'default_requests' => 100,
        'default_per_seconds' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    |
    | Default retry settings for failed actions.
    |
    */
    'retry' => [
        'max_attempts' => 3,
        'backoff' => 'exponential', // fixed, linear, exponential
        'base_delay' => 1000, // milliseconds
        'max_delay' => 30000, // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Execution logging settings.
    |
    */
    'logging' => [
        'enabled' => true,
        'channel' => 'integration',
        'retention_days' => 30,
        'log_input' => true,
        'log_output' => true,
        'redact_secrets' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Categories
    |--------------------------------------------------------------------------
    |
    | Connector categories for organization.
    |
    */
    'categories' => [
        'communication' => 'Communication',
        'crm' => 'CRM',
        'marketing' => 'Marketing',
        'productivity' => 'Productivity',
        'development' => 'Development',
        'finance' => 'Finance',
        'ecommerce' => 'E-commerce',
        'analytics' => 'Analytics',
        'storage' => 'Storage',
        'social' => 'Social Media',
        'other' => 'Other',
    ],

    /*
    |--------------------------------------------------------------------------
    | Built-in Connectors
    |--------------------------------------------------------------------------
    |
    | Enable/disable built-in connectors.
    |
    */
    'builtin_connectors' => [
        'webhook' => true,
        'http' => true,
        'schedule' => true,
        'email' => true,
    ],

];
