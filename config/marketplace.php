<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Marketplace API
    |--------------------------------------------------------------------------
    |
    | Configuration for connecting to the plugin marketplace.
    |
    */

    'api_url' => env('MARKETPLACE_API_URL', 'https://marketplace.example.com/api/v1'),
    'api_key' => env('MARKETPLACE_API_KEY'),
    'timeout' => env('MARKETPLACE_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Site Identification
    |--------------------------------------------------------------------------
    |
    | Used for license validation and update requests.
    |
    */

    'site_url' => env('APP_URL'),
    'email' => env('MARKETPLACE_EMAIL', env('MAIL_FROM_ADDRESS')),

    /*
    |--------------------------------------------------------------------------
    | Plugin Installation
    |--------------------------------------------------------------------------
    */

    'plugins_path' => env('PLUGINS_PATH', base_path('plugins')),
    'auto_activate' => env('PLUGINS_AUTO_ACTIVATE', false),

    /*
    |--------------------------------------------------------------------------
    | Backups
    |--------------------------------------------------------------------------
    */

    'backup_path' => storage_path('plugin-backups'),
    'backup_retention_days' => 30,

    /*
    |--------------------------------------------------------------------------
    | Updates
    |--------------------------------------------------------------------------
    */

    'auto_update' => env('PLUGINS_AUTO_UPDATE', false),
    'auto_update_security' => env('PLUGINS_AUTO_UPDATE_SECURITY', true),
    'check_interval' => 86400, // 24 hours

    /*
    |--------------------------------------------------------------------------
    | License Verification
    |--------------------------------------------------------------------------
    */

    'verify_interval' => 86400, // 24 hours
    'offline_grace_days' => 7,

    /*
    |--------------------------------------------------------------------------
    | Cache
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'marketplace_ttl' => 3600, // 1 hour
        'plugin_ttl' => 86400, // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'prefix' => 'api/v1/marketplace',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'update_available' => true,
        'license_expiring' => true,
        'license_expiring_days' => 30,
    ],

];
