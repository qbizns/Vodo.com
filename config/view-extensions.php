<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Auto Process Views
    |--------------------------------------------------------------------------
    |
    | When enabled, all HTML responses will be processed for view extensions.
    | When disabled, only views listed in 'process_views' will be processed.
    |
    | Enabling this may have a performance impact on large applications.
    |
    */
    'auto_process' => env('VIEW_EXTENSIONS_AUTO_PROCESS', false),

    /*
    |--------------------------------------------------------------------------
    | Views to Process
    |--------------------------------------------------------------------------
    |
    | List of view names that should be processed for extensions.
    | Supports wildcards: 'admin.*' will match all admin views.
    |
    | Only used when 'auto_process' is false.
    |
    */
    'process_views' => [
        'admin.*',
        'owner.*',
        'client.*',
        'frontend.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Excluded Views
    |--------------------------------------------------------------------------
    |
    | Views that should never be processed for extensions.
    | Takes precedence over 'process_views' and 'auto_process'.
    |
    */
    'excluded_views' => [
        'emails.*',
        'errors.*',
        'vendor.*',
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Extensions
    |--------------------------------------------------------------------------
    |
    | Whether to cache processed view extensions.
    | Recommended for production environments.
    |
    */
    'cache' => [
        'enabled' => env('VIEW_EXTENSIONS_CACHE', false),
        'ttl' => 3600, // seconds
        'prefix' => 'view_ext_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Debug Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, adds HTML comments showing where extensions were applied.
    | Useful for debugging but should be disabled in production.
    |
    */
    'debug' => env('VIEW_EXTENSIONS_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Default Priority
    |--------------------------------------------------------------------------
    |
    | Default priority for extensions when not specified.
    | Lower numbers = higher priority (executed first).
    |
    */
    'default_priority' => 10,

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | Configuration for the view extension middleware.
    |
    */
    'middleware' => [
        // Whether to automatically register the middleware
        'auto_register' => true,

        // Middleware groups to add the processor to
        'groups' => ['web'],

        // Priority in the middleware stack (lower = earlier)
        'priority' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Slot Configuration
    |--------------------------------------------------------------------------
    |
    | Default settings for view slots.
    |
    */
    'slots' => [
        // Maximum items per slot (0 = unlimited)
        'max_items' => 0,

        // Default slot names to register in all extendable views
        'defaults' => [
            'head_start',
            'head_end',
            'body_start',
            'body_end',
            'before_content',
            'after_content',
            'sidebar_start',
            'sidebar_end',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | XPath Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for XPath-based modifications.
    |
    */
    'xpath' => [
        // Whether to preserve whitespace in DOM operations
        'preserve_whitespace' => true,

        // Whether to format output HTML
        'format_output' => false,

        // Encoding for DOM operations
        'encoding' => 'UTF-8',
    ],

    /*
    |--------------------------------------------------------------------------
    | Safe Mode
    |--------------------------------------------------------------------------
    |
    | When enabled, catches and logs extension errors without breaking the page.
    | Recommended for production.
    |
    */
    'safe_mode' => env('VIEW_EXTENSIONS_SAFE_MODE', true),
];
