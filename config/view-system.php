<?php

return [
    /*
    |--------------------------------------------------------------------------
    | View System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the dynamic view system with XPath-based extensions.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Control how compiled views are cached. Disabling cache is useful for
    | development but should be enabled in production for performance.
    |
    */
    'cache' => [
        'enabled' => env('VIEW_SYSTEM_CACHE_ENABLED', true),
        'ttl' => env('VIEW_SYSTEM_CACHE_TTL', 3600), // seconds
        'prefix' => 'view_system_',
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the view system REST API.
    |
    */
    'api' => [
        'prefix' => 'api/v1',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for the admin interface (if implemented).
    |
    */
    'admin' => [
        'prefix' => 'admin',
        'middleware' => ['web', 'auth', 'admin'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Behavior
    |--------------------------------------------------------------------------
    |
    | Control what happens when views are unregistered.
    |
    */
    'delete_extensions_on_unregister' => true,
    'delete_cache_on_unregister' => true,

    /*
    |--------------------------------------------------------------------------
    | Compiler Settings
    |--------------------------------------------------------------------------
    |
    | Settings for the XPath compiler.
    |
    */
    'compiler' => [
        // Log level: 'debug', 'info', 'warning', 'error', 'none'
        'log_level' => env('VIEW_COMPILER_LOG_LEVEL', 'warning'),
        
        // Maximum extensions per view (for safety)
        'max_extensions_per_view' => 100,
        
        // Enable detailed compilation logging
        'detailed_logging' => env('VIEW_COMPILER_DETAILED_LOG', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Blade Integration
    |--------------------------------------------------------------------------
    |
    | Settings for Laravel Blade integration.
    |
    */
    'blade' => [
        // Directive name for rendering dynamic views
        // Usage: @dynamicView('view_name', ['data' => 'value'])
        'directive' => 'dynamicView',
        
        // Temp directory for compiled Blade views
        'temp_path' => storage_path('framework/views/dynamic'),
        
        // Clean up temp files after rendering
        'cleanup_temp_files' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Security-related configuration.
    |
    */
    'security' => [
        // Allowed HTML tags in view content (null = all allowed)
        'allowed_tags' => null,
        
        // Allowed attributes (null = all allowed)
        'allowed_attributes' => null,
        
        // Sanitize view content on save
        'sanitize_content' => false,
        
        // Require plugin_slug for all operations
        'require_plugin_ownership' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Values
    |--------------------------------------------------------------------------
    |
    | Default values for new views and extensions.
    |
    */
    'defaults' => [
        'view' => [
            'type' => 'blade',
            'priority' => 100,
            'is_cacheable' => true,
        ],
        'extension' => [
            'priority' => 100,
            'sequence' => 0,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | XPath Shortcuts
    |--------------------------------------------------------------------------
    |
    | Common XPath patterns that can be referenced by name.
    | Usage: '@by_id:my-element' instead of '//*[@id="my-element"]'
    |
    */
    'xpath_shortcuts' => [
        'by_id' => '//*[@id="{0}"]',
        'by_class' => '//*[contains(@class, "{0}")]',
        'by_data' => '//*[@data-{0}="{1}"]',
        'by_name' => '//*[@name="{0}"]',
        'by_tag' => '//{0}',
        'first_child' => '//*[@id="{0}"]/*[1]',
        'last_child' => '//*[@id="{0}"]/*[last()]',
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    |
    | Enable/disable specific event hooks for the view system.
    |
    */
    'hooks' => [
        'view_registered' => true,
        'view_updated' => true,
        'view_unregistered' => true,
        'view_extended' => true,
        'view_compiled' => true,
        'extension_created' => true,
        'extension_updated' => true,
        'extension_deleted' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | Enable debug mode for additional logging and error details.
    |
    */
    'debug' => env('VIEW_SYSTEM_DEBUG', false),
];
