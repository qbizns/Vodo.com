<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Hello World Plugin Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the default configuration for the Hello World plugin.
    | These values can be overridden by plugin settings in the database.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | General Settings
    |--------------------------------------------------------------------------
    */

    // Default greeting message
    'greeting' => env('HELLO_WORLD_GREETING', 'Hello, World!'),

    // Show greeting count on dashboard
    'show_count' => env('HELLO_WORLD_SHOW_COUNT', true),

    /*
    |--------------------------------------------------------------------------
    | Display Settings
    |--------------------------------------------------------------------------
    */

    // Display mode: card, list, or grid
    'display_mode' => env('HELLO_WORLD_DISPLAY_MODE', 'card'),

    // Maximum greetings per page
    'max_greetings' => env('HELLO_WORLD_MAX_GREETINGS', 10),

    /*
    |--------------------------------------------------------------------------
    | Advanced Settings
    |--------------------------------------------------------------------------
    */

    // Enable API access
    'enable_api' => env('HELLO_WORLD_ENABLE_API', false),

    // Cache duration in minutes
    'cache_duration' => env('HELLO_WORLD_CACHE_DURATION', 60),

    /*
    |--------------------------------------------------------------------------
    | Route Settings
    |--------------------------------------------------------------------------
    */

    // Route prefix for the plugin
    'route_prefix' => 'plugins/hello-world',

    // API route prefix
    'api_prefix' => 'api/v1/plugins/hello-world',

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */

    'permissions' => [
        'view' => 'greetings.view',
        'create' => 'greetings.create',
        'edit' => 'greetings.edit',
        'delete' => 'greetings.delete',
        'settings' => 'greetings.settings',
    ],

];
