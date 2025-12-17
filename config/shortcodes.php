<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Register Built-in Shortcodes
    |--------------------------------------------------------------------------
    |
    | When enabled, the system will automatically register all built-in
    | shortcodes (button, alert, youtube, etc.) on boot.
    |
    */

    'register_builtin' => true,

    /*
    |--------------------------------------------------------------------------
    | Maximum Parsing Depth
    |--------------------------------------------------------------------------
    |
    | Maximum depth for nested shortcode parsing. This prevents infinite
    | loops when shortcodes reference each other.
    |
    */

    'max_depth' => 10,

    /*
    |--------------------------------------------------------------------------
    | Show Errors
    |--------------------------------------------------------------------------
    |
    | When enabled, validation errors will be shown as HTML comments in
    | the output. Useful for debugging but should be disabled in production.
    |
    */

    'show_errors' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Blade Directive
    |--------------------------------------------------------------------------
    |
    | When enabled, registers Blade directives for shortcode processing:
    | @shortcode('tag', ['attr' => 'value'])
    | @shortcodes($content)
    |
    */

    'blade_directive' => true,

    /*
    |--------------------------------------------------------------------------
    | String Macro
    |--------------------------------------------------------------------------
    |
    | When enabled, adds shortcode methods to Illuminate\Support\Str:
    | Str::parseShortcodes($content)
    | Str::stripShortcodes($content)
    |
    */

    'string_macro' => true,

    /*
    |--------------------------------------------------------------------------
    | Caching Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for shortcode output caching.
    |
    */

    'cache' => [
        // Default cache TTL for shortcode output (in seconds)
        'default_ttl' => 3600, // 1 hour

        // TTL for shortcode definition cache
        'definition_ttl' => 3600,

        // Cache driver to use (null = default)
        'driver' => null,

        // Cache key prefix
        'prefix' => 'shortcode',
    ],

    /*
    |--------------------------------------------------------------------------
    | Usage Tracking
    |--------------------------------------------------------------------------
    |
    | Configuration for tracking shortcode usage in content.
    |
    */

    'tracking' => [
        // Enable/disable usage tracking
        'enabled' => true,

        // Async tracking via queue
        'async' => false,

        // Clean up old tracking data after X days
        'retention_days' => 90,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the shortcode API routes.
    |
    */

    'api' => [
        // Route prefix
        'prefix' => 'api/v1/shortcodes',

        // Middleware for authenticated routes
        'middleware' => ['api', 'auth:sanctum'],

        // Middleware for public routes (parsing)
        'public_middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    |
    | Security-related configuration.
    |
    */

    'security' => [
        // List of tags that cannot be overridden by plugins
        'protected_tags' => [],

        // Maximum content size for parsing (in bytes)
        'max_content_size' => 1024 * 1024, // 1MB

        // Sanitize HTML output
        'sanitize_output' => false,

        // Allow script/iframe tags in output
        'allow_unsafe_tags' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Attributes
    |--------------------------------------------------------------------------
    |
    | Default attribute values applied to all shortcodes unless overridden.
    |
    */

    'defaults' => [
        'category' => 'general',
        'is_cacheable' => true,
        'parse_nested' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Content Processors
    |--------------------------------------------------------------------------
    |
    | Pre and post processors for shortcode content.
    |
    */

    'processors' => [
        // Process markdown in content
        'markdown' => false,

        // Auto-paragraph content (wpautop style)
        'autop' => false,

        // Strip dangerous HTML
        'strip_tags' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    |
    | Enable or disable specific event hooks.
    |
    */

    'hooks' => [
        'shortcode_registered' => true,
        'shortcode_updated' => true,
        'shortcode_unregistered' => true,
        'shortcodes_ready' => true,
        'shortcode_rendered' => true,
    ],

];
