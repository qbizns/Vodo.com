<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Create Default Roles & Permissions
    |--------------------------------------------------------------------------
    |
    | When enabled, default roles and permissions will be created on boot.
    |
    */

    'create_defaults' => true,

    /*
    |--------------------------------------------------------------------------
    | Default Roles
    |--------------------------------------------------------------------------
    |
    | Roles created automatically when the system initializes.
    |
    */

    'default_roles' => [
        [
            'slug' => 'super_admin',
            'name' => 'Super Administrator',
            'description' => 'Full system access - bypasses all permission checks',
            'level' => 1000,
        ],
        [
            'slug' => 'admin',
            'name' => 'Administrator',
            'description' => 'Administrative access to most features',
            'level' => 900,
            'permissions' => ['admin.*', 'users.*', 'settings.*'],
        ],
        [
            'slug' => 'moderator',
            'name' => 'Moderator',
            'description' => 'Can moderate content and users',
            'level' => 500,
            'permissions' => ['content.moderate', 'users.view'],
        ],
        [
            'slug' => 'editor',
            'name' => 'Editor',
            'description' => 'Can create and edit content',
            'level' => 300,
            'permissions' => ['content.create', 'content.update', 'content.view'],
        ],
        [
            'slug' => 'author',
            'name' => 'Author',
            'description' => 'Can create and manage own content',
            'level' => 200,
            'permissions' => ['content.create', 'content.update_own', 'content.view'],
        ],
        [
            'slug' => 'subscriber',
            'name' => 'Subscriber',
            'description' => 'Basic access for registered users',
            'level' => 100,
            'default' => true,
            'permissions' => ['content.view'],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Permissions
    |--------------------------------------------------------------------------
    |
    | Permissions created automatically when the system initializes.
    |
    */

    'default_permissions' => [
        // Admin
        ['slug' => 'admin.access', 'name' => 'Access Admin Panel', 'group' => 'admin'],
        ['slug' => 'admin.*', 'name' => 'Full Admin Access', 'group' => 'admin'],

        // Users
        ['slug' => 'users.view', 'name' => 'View Users', 'group' => 'users'],
        ['slug' => 'users.create', 'name' => 'Create Users', 'group' => 'users'],
        ['slug' => 'users.update', 'name' => 'Update Users', 'group' => 'users'],
        ['slug' => 'users.delete', 'name' => 'Delete Users', 'group' => 'users'],
        ['slug' => 'users.*', 'name' => 'Full User Management', 'group' => 'users'],

        // Content
        ['slug' => 'content.view', 'name' => 'View Content', 'group' => 'content'],
        ['slug' => 'content.create', 'name' => 'Create Content', 'group' => 'content'],
        ['slug' => 'content.update', 'name' => 'Update Any Content', 'group' => 'content'],
        ['slug' => 'content.update_own', 'name' => 'Update Own Content', 'group' => 'content'],
        ['slug' => 'content.delete', 'name' => 'Delete Content', 'group' => 'content'],
        ['slug' => 'content.publish', 'name' => 'Publish Content', 'group' => 'content'],
        ['slug' => 'content.moderate', 'name' => 'Moderate Content', 'group' => 'content'],
        ['slug' => 'content.*', 'name' => 'Full Content Management', 'group' => 'content'],

        // Settings
        ['slug' => 'settings.view', 'name' => 'View Settings', 'group' => 'settings'],
        ['slug' => 'settings.update', 'name' => 'Update Settings', 'group' => 'settings'],
        ['slug' => 'settings.*', 'name' => 'Full Settings Access', 'group' => 'settings'],

        // Plugins
        ['slug' => 'plugins.view', 'name' => 'View Plugins', 'group' => 'plugins'],
        ['slug' => 'plugins.install', 'name' => 'Install Plugins', 'group' => 'plugins'],
        ['slug' => 'plugins.activate', 'name' => 'Activate/Deactivate Plugins', 'group' => 'plugins'],
        ['slug' => 'plugins.update', 'name' => 'Update Plugins', 'group' => 'plugins'],
        ['slug' => 'plugins.settings', 'name' => 'Configure Plugin Settings', 'group' => 'plugins'],
        ['slug' => 'plugins.delete', 'name' => 'Uninstall Plugins', 'group' => 'plugins'],
        ['slug' => 'plugins.marketplace', 'name' => 'Access Marketplace', 'group' => 'plugins'],
        ['slug' => 'plugins.licenses', 'name' => 'Manage Licenses', 'group' => 'plugins'],
        ['slug' => 'plugins.*', 'name' => 'Full Plugin Management', 'group' => 'plugins'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Blade Directives
    |--------------------------------------------------------------------------
    |
    | Register Blade directives: @role, @permission, @hasrole, @anypermission
    |
    */

    'blade_directives' => true,

    /*
    |--------------------------------------------------------------------------
    | Register Gate Permissions
    |--------------------------------------------------------------------------
    |
    | Integrate with Laravel's Gate for @can directives and policies.
    |
    */

    'register_gate' => true,

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    */

    'cache' => [
        'enabled' => true,
        'ttl' => 3600,
        'driver' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    */

    'api' => [
        'prefix' => 'api/v1/permissions',
        'middleware' => ['api', 'auth:sanctum'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Super Admin Role
    |--------------------------------------------------------------------------
    |
    | The role slug that bypasses all permission checks.
    |
    */

    'super_admin_role' => 'super_admin',

    /*
    |--------------------------------------------------------------------------
    | Permission Groups
    |--------------------------------------------------------------------------
    |
    | Display names for permission groups in the UI.
    |
    */

    'groups' => [
        'general' => 'General',
        'admin' => 'Administration',
        'users' => 'User Management',
        'content' => 'Content',
        'settings' => 'Settings',
        'plugins' => 'Plugins',
        'system' => 'System',
    ],

];
