<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Plugin Directory
    |--------------------------------------------------------------------------
    |
    | The directory where plugins are installed. This should be relative to
    | the application root.
    |
    */

    'directory' => app_path('Plugins'),

    /*
    |--------------------------------------------------------------------------
    | Temporary Directory
    |--------------------------------------------------------------------------
    |
    | The directory used for extracting plugin ZIP files during installation.
    |
    */

    'temp_directory' => storage_path('app/plugins-temp'),

    /*
    |--------------------------------------------------------------------------
    | Backup Directory
    |--------------------------------------------------------------------------
    |
    | The directory used for backing up plugins during updates.
    |
    */

    'backup_directory' => storage_path('app/plugins-backup'),

    /*
    |--------------------------------------------------------------------------
    | Auto-load Plugins
    |--------------------------------------------------------------------------
    |
    | Whether to automatically load active plugins on application boot.
    |
    */

    'auto_load' => env('PLUGINS_AUTO_LOAD', true),

    /*
    |--------------------------------------------------------------------------
    | Maximum Upload Size
    |--------------------------------------------------------------------------
    |
    | Maximum size for plugin ZIP uploads in kilobytes.
    |
    */

    'max_upload_size' => env('PLUGINS_MAX_UPLOAD_SIZE', 10240), // 10MB

    /*
    |--------------------------------------------------------------------------
    | Allowed File Extensions
    |--------------------------------------------------------------------------
    |
    | File extensions allowed within plugin ZIP files.
    |
    */

    'allowed_extensions' => [
        'php',
        'json',
        'js',
        'css',
        'html',
        'blade.php',
        'md',
        'txt',
        'png',
        'jpg',
        'jpeg',
        'gif',
        'svg',
        'ico',
        'woff',
        'woff2',
        'ttf',
        'eot',
    ],

    /*
    |--------------------------------------------------------------------------
    | Core Hooks
    |--------------------------------------------------------------------------
    |
    | List of core hooks available in the system.
    |
    */

    'core_hooks' => [
        'actions' => [
            'plugin_activated' => 'Fired after a plugin is activated',
            'plugin_deactivated' => 'Fired after a plugin is deactivated',
            'plugin_loaded' => 'Fired after a plugin is loaded',
            'plugins_loaded' => 'Fired after all plugins are loaded',
            'plugin_uninstalled' => 'Fired after a plugin is uninstalled',
            'routes_loaded' => 'Fired after routes are registered',
            'admin_head' => 'Add content to admin head section',
            'admin_footer' => 'Add content to admin footer section',
        ],
        'filters' => [
            'navigation_items' => 'Modify sidebar navigation items',
            'dashboard_widgets' => 'Add widgets to dashboard',
            'plugin_settings' => 'Modify plugin settings before save',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Modules With Plugin Management
    |--------------------------------------------------------------------------
    |
    | List of modules that have access to plugin management.
    |
    */

    'management_modules' => [
        'Console',
        'Owner',
        'Admin',
    ],
];
