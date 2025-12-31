<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Theme System Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the theme registry and resolution system.
    |
    */

    'enabled' => env('THEMES_ENABLED', true),

    /*
    |--------------------------------------------------------------------------
    | Default Theme
    |--------------------------------------------------------------------------
    |
    | The default theme to use when no theme is specified.
    |
    */
    'default' => env('DEFAULT_THEME', null),

    /*
    |--------------------------------------------------------------------------
    | Theme Storage
    |--------------------------------------------------------------------------
    |
    | Where theme assets are stored and served from.
    |
    */
    'storage' => [
        'disk' => env('THEME_STORAGE_DISK', 'public'),
        'path' => 'themes',
    ],

    /*
    |--------------------------------------------------------------------------
    | Asset Versioning
    |--------------------------------------------------------------------------
    |
    | Enable cache-busting for theme assets.
    |
    */
    'assets' => [
        'versioning' => env('THEME_ASSET_VERSIONING', true),
        'compile' => env('THEME_ASSET_COMPILE', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Caching
    |--------------------------------------------------------------------------
    |
    | Cache theme configuration and resolution.
    |
    */
    'cache' => [
        'enabled' => env('THEME_CACHE_ENABLED', true),
        'ttl' => env('THEME_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme Slots
    |--------------------------------------------------------------------------
    |
    | Default slots that themes can implement.
    |
    */
    'slots' => [
        'storefront.head' => [
            'description' => 'Content in <head> tag',
            'accepts' => ['blade'],
        ],
        'storefront.header' => [
            'description' => 'Page header/navigation',
            'accepts' => ['blade', 'component'],
        ],
        'storefront.footer' => [
            'description' => 'Page footer',
            'accepts' => ['blade', 'component'],
        ],
        'storefront.sidebar' => [
            'description' => 'Optional sidebar',
            'accepts' => ['blade', 'component'],
        ],
        'storefront.scripts' => [
            'description' => 'Scripts before </body>',
            'accepts' => ['blade'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Allowed Features
    |--------------------------------------------------------------------------
    |
    | Features that themes can declare support for.
    |
    */
    'features' => [
        'storefront',
        'checkout',
        'account',
        'blog',
        'rtl',
        'dark-mode',
        'mega-menu',
        'wishlist',
        'compare',
        'quick-view',
    ],
];
