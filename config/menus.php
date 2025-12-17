<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Register Default Menus
    |--------------------------------------------------------------------------
    |
    | When enabled, default menus will be created automatically on boot.
    |
    */

    'register_defaults' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Menus
    |--------------------------------------------------------------------------
    |
    | Menus that should be created automatically. You can add menu items
    | to these in your plugins or application service providers.
    |
    */

    'defaults' => [
        'admin_sidebar' => [
            'name' => 'Admin Sidebar',
            'location' => 'sidebar',
            'show_icons' => true,
            'show_badges' => true,
            'collapsible' => true,
        ],
        'admin_topbar' => [
            'name' => 'Admin Top Bar',
            'location' => 'topbar',
            'show_icons' => true,
            'show_badges' => true,
        ],
        'user_menu' => [
            'name' => 'User Menu',
            'location' => 'dropdown',
            'show_icons' => true,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Blade Directives
    |--------------------------------------------------------------------------
    |
    | Register Blade directives for rendering menus.
    | @menu, @menuNavbar, @menuSidebar, @breadcrumb
    |
    */

    'blade_directives' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => true,
        'definition_ttl' => 3600, // 1 hour
        'tree_ttl' => 300, // 5 minutes
        'driver' => null, // null = default cache driver
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'prefix' => 'api/v1/menus',
        'middleware' => ['api', 'auth:sanctum'],
        'public_middleware' => ['api'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default CSS Classes
    |--------------------------------------------------------------------------
    |
    | Default CSS classes for menu rendering.
    |
    */

    'classes' => [
        'menu' => 'nav flex-column',
        'item' => 'nav-item',
        'link' => 'nav-link',
        'dropdown' => 'nav-item dropdown',
        'dropdown_toggle' => 'nav-link dropdown-toggle',
        'dropdown_menu' => 'dropdown-menu',
        'header' => 'nav-header',
        'divider' => 'dropdown-divider',
        'active' => 'active',
        'disabled' => 'disabled',
        'icon' => 'menu-icon me-2',
        'badge' => 'badge ms-auto',
        'children' => 'nav flex-column ms-3',
    ],

    /*
    |--------------------------------------------------------------------------
    | Security
    |--------------------------------------------------------------------------
    */

    'security' => [
        // Maximum nesting depth for menu items
        'max_depth' => 5,
        
        // Allow JavaScript actions in menu items
        'allow_js_actions' => true,
        
        // Validate route names exist
        'validate_routes' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Event Hooks
    |--------------------------------------------------------------------------
    */

    'hooks' => [
        'menu_item_added' => true,
        'menu_item_updated' => true,
        'menu_item_removed' => true,
        'menus_ready' => true,
    ],

];
