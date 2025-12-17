<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin System Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all configuration options for the plugin system.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Plugin Directory
    |--------------------------------------------------------------------------
    |
    | The directory where plugins are stored.
    |
    */
    'directory' => app_path('Plugins'),

    /*
    |--------------------------------------------------------------------------
    | Auto-load Plugins
    |--------------------------------------------------------------------------
    |
    | Whether to automatically load active plugins on application boot.
    |
    */
    'auto_load' => true,

    /*
    |--------------------------------------------------------------------------
    | Plugin Cache
    |--------------------------------------------------------------------------
    |
    | Configuration for plugin caching.
    |
    */
    'cache' => [
        'enabled' => env('PLUGIN_CACHE_ENABLED', true),
        'ttl' => env('PLUGIN_CACHE_TTL', 3600),
        'prefix' => 'plugins:',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration for the plugin system.
    |
    */
    'security' => [
        // Enable CSRF protection for plugin operations
        'csrf' => [
            'enabled' => true,
            'protect_all' => false,
            'token_lifetime' => 43200, // 12 hours
        ],

        // Rate limiting for plugin operations
        'rate_limit' => [
            'enabled' => true,
            'install' => ['limit' => 5, 'window' => 300],
            'activate' => ['limit' => 10, 'window' => 60],
            'api' => ['limit' => 60, 'window' => 60],
        ],

        // File upload restrictions
        'uploads' => [
            'max_size' => 50 * 1024 * 1024, // 50MB
            'allowed_extensions' => ['zip'],
            'scan_for_malware' => false,
        ],

        // Sandbox settings (for future implementation)
        'sandbox' => [
            'enabled' => false,
            'memory_limit' => '128M',
            'time_limit' => 30,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Hook System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the WordPress-style hook system.
    |
    */
    'hooks' => [
        // Enable debug mode for hook execution
        'debug' => env('PLUGIN_HOOKS_DEBUG', false),

        // Log slow hooks (execution time in milliseconds)
        'slow_threshold' => 100,

        // Maximum hook depth (to prevent infinite recursion)
        'max_depth' => 10,
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependency Resolution
    |--------------------------------------------------------------------------
    |
    | Settings for plugin dependency handling.
    |
    */
    'dependencies' => [
        // Strict mode fails activation if dependencies not met
        'strict' => true,

        // Allow circular dependencies (not recommended)
        'allow_circular' => false,

        // Automatically activate dependencies
        'auto_activate' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Marketplace Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the plugin marketplace integration.
    |
    */
    'marketplace' => [
        'enabled' => env('PLUGIN_MARKETPLACE_ENABLED', true),
        'url' => env('PLUGIN_MARKETPLACE_URL', 'https://plugins.example.com'),
        'api_key' => env('PLUGIN_MARKETPLACE_API_KEY'),
        'check_updates' => true,
        'update_check_interval' => 86400, // 24 hours
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for plugin-related logging.
    |
    */
    'logging' => [
        'channel' => env('PLUGIN_LOG_CHANNEL', 'stack'),
        'level' => env('PLUGIN_LOG_LEVEL', 'info'),
        'log_activations' => true,
        'log_deactivations' => true,
        'log_hook_errors' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Development Mode
    |--------------------------------------------------------------------------
    |
    | Settings for plugin development.
    |
    */
    'development' => [
        // Show detailed error messages
        'debug' => env('PLUGIN_DEBUG', false),

        // Disable caching for development
        'disable_cache' => env('PLUGIN_DISABLE_CACHE', false),

        // Hot reload support (watches for file changes)
        'hot_reload' => env('PLUGIN_HOT_RELOAD', false),
    ],
];
