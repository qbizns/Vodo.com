<?php

namespace App\Plugins\ums;

use App\Services\Plugins\BasePlugin;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * User Management System Plugin
 * 
 * Comprehensive plugin for managing users, profiles, roles, 
 * permissions, and user-related operations.
 */
class UmsPlugin extends BasePlugin
{
    /**
     * Plugin identifier
     */
    public const SLUG = 'ums';

    /**
     * Plugin version
     */
    public const VERSION = '1.0.0';

    /**
     * Register plugin services and bindings.
     */
    public function register(): void
    {
        $this->mergeConfig();
        Log::info('UMS Plugin: Registered');
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();
        $this->registerEventListeners();
        $this->registerFiltersAndActions();
        Log::info('UMS Plugin: Booted');
    }

    /**
     * Merge plugin configuration.
     */
    protected function mergeConfig(): void
    {
        $configPath = $this->basePath . '/config/ums.php';
        
        if (file_exists($configPath)) {
            config()->set('ums', require $configPath);
        }
    }

    /**
     * Register event listeners for the plugin.
     */
    protected function registerEventListeners(): void
    {
        // Event listeners are registered through the service provider
    }

    /**
     * Register filters and actions for the plugin.
     */
    protected function registerFiltersAndActions(): void
    {
        // Add a filter for user data
        $this->addFilter('user_data', function (array $data) {
            // Enrich user data if needed
            return $data;
        });

        // Add an action for user events
        $this->addAction('user_created', function ($user) {
            Log::info('UMS: User created', ['user_id' => $user->id]);
        });

        $this->addAction('user_updated', function ($user) {
            Log::info('UMS: User updated', ['user_id' => $user->id]);
        });
    }

    /**
     * Called when plugin is being activated
     */
    public function onActivate(): void
    {
        // Create default settings
        $this->setSetting('users_per_page', 25);
        $this->setSetting('enable_user_registration', true);
        $this->setSetting('require_email_verification', true);
        $this->setSetting('password_min_length', 8);
        $this->setSetting('session_lifetime', 120);
        $this->setSetting('enable_two_factor', false);
        $this->setSetting('allow_avatar_upload', true);
        $this->setSetting('max_avatar_size', 2048); // KB

        // Clear caches
        Cache::forget('ums.users');
        Cache::forget('ums.settings');

        Log::info('UMS Plugin: Activated');
    }

    /**
     * Called when plugin is being deactivated
     */
    public function onDeactivate(): void
    {
        // Clear plugin-specific caches
        Cache::forget('ums.users');
        Cache::forget('ums.settings');

        Log::info('UMS Plugin: Deactivated');
    }

    /**
     * Called before plugin is uninstalled
     */
    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            // Note: We don't drop user tables as they are core system tables
            // Only plugin-specific tables would be dropped here
        }

        // Clear all caches
        Cache::forget('ums.users');
        Cache::forget('ums.settings');

        Log::info('UMS Plugin: Uninstalled');
    }

    /**
     * Called when plugin is being updated
     */
    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        // Run version-specific migrations if needed
        if (version_compare($fromVersion, '1.0.0', '<')) {
            // Migration tasks for version 1.0.0
        }

        // Clear caches
        Cache::forget('ums.users');
        Cache::forget('ums.settings');

        Log::info("UMS Plugin: Updated from {$fromVersion} to {$toVersion}");
    }

    /**
     * Handle plugin activation (legacy method).
     */
    public function activate(): void
    {
        $this->onActivate();
    }

    /**
     * Handle plugin deactivation (legacy method).
     */
    public function deactivate(): void
    {
        $this->onDeactivate();
    }

    /**
     * Handle plugin uninstallation (legacy method).
     */
    public function uninstall(): void
    {
        $this->onUninstall(false);
    }

    /**
     * Check if this plugin has a settings page.
     */
    public function hasSettingsPage(): bool
    {
        return true;
    }

    /**
     * Get the icon for the settings page sidebar.
     */
    public function getSettingsIcon(): string
    {
        return 'users';
    }

    /**
     * Get permissions registered by this plugin.
     */
    public function getPermissions(): array
    {
        return [
            'ums.users.view' => [
                'label' => 'View Users',
                'description' => 'Can view user list and details',
                'group' => 'User Management',
            ],
            'ums.users.create' => [
                'label' => 'Create Users',
                'description' => 'Can create new users',
                'group' => 'User Management',
            ],
            'ums.users.edit' => [
                'label' => 'Edit Users',
                'description' => 'Can modify existing users',
                'group' => 'User Management',
            ],
            'ums.users.delete' => [
                'label' => 'Delete Users',
                'description' => 'Can delete users',
                'group' => 'User Management',
            ],
            'ums.users.impersonate' => [
                'label' => 'Impersonate Users',
                'description' => 'Can log in as another user',
                'group' => 'User Management',
                'is_dangerous' => true,
            ],
            'ums.roles.view' => [
                'label' => 'View Roles',
                'description' => 'Can view role list and details',
                'group' => 'User Management',
            ],
            'ums.roles.manage' => [
                'label' => 'Manage Roles',
                'description' => 'Can create, edit and delete roles',
                'group' => 'User Management',
            ],
            'ums.permissions.view' => [
                'label' => 'View Permissions',
                'description' => 'Can view permission list',
                'group' => 'User Management',
            ],
            'ums.permissions.manage' => [
                'label' => 'Manage Permissions',
                'description' => 'Can assign permissions to roles',
                'group' => 'User Management',
            ],
            'ums.settings' => [
                'label' => 'UMS Settings',
                'description' => 'Can configure user management settings',
                'group' => 'User Management',
            ],
        ];
    }

    /**
     * Get menu items registered by this plugin.
     */
    public function getMenuItems(): array
    {
        return [
            [
                'id' => 'ums',
                'label' => 'User Management',
                'icon' => 'users',
                'permission' => 'ums.users.view',
                'position' => 10,
                'children' => [
                    [
                        'id' => 'ums.users',
                        'label' => 'Users',
                        'icon' => 'user',
                        'url' => '/plugins/ums/users',
                        'permission' => 'ums.users.view',
                    ],
                    [
                        'id' => 'ums.roles',
                        'label' => 'Roles',
                        'icon' => 'shield',
                        'url' => '/plugins/ums/roles',
                        'permission' => 'ums.roles.view',
                    ],
                    [
                        'id' => 'ums.permissions',
                        'label' => 'Permissions',
                        'icon' => 'key',
                        'url' => '/plugins/ums/permissions',
                        'permission' => 'ums.permissions.view',
                    ],
                ],
            ],
        ];
    }

    /**
     * Get dashboard widgets registered by this plugin.
     */
    public function getWidgets(): array
    {
        return [
            [
                'id' => 'ums-user-stats',
                'name' => 'User Statistics',
                'description' => 'Overview of user statistics',
                'component' => 'ums::widgets.stats',
                'permissions' => ['ums.users.view'],
                'default_width' => 4,
                'default_height' => 2,
            ],
            [
                'id' => 'ums-recent-users',
                'name' => 'Recent Users',
                'description' => 'List of recently registered users',
                'component' => 'ums::widgets.recent-users',
                'permissions' => ['ums.users.view'],
                'default_width' => 6,
                'default_height' => 3,
            ],
            [
                'id' => 'ums-active-sessions',
                'name' => 'Active Sessions',
                'description' => 'Currently active user sessions',
                'component' => 'ums::widgets.active-sessions',
                'permissions' => ['ums.users.view'],
                'default_width' => 4,
                'default_height' => 2,
            ],
        ];
    }

    /**
     * Get the settings fields definition for this plugin.
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
                'registration' => ['label' => 'Registration', 'icon' => 'userPlus'],
                'security' => ['label' => 'Security', 'icon' => 'shield'],
                'profile' => ['label' => 'Profile', 'icon' => 'user'],
            ],
            'fields' => [
                [
                    'key' => 'users_per_page',
                    'type' => 'number',
                    'label' => 'Users Per Page',
                    'tab' => 'general',
                    'default' => 25,
                    'min' => 10,
                    'max' => 100,
                    'hint' => 'Number of users to display per page',
                    'rules' => 'required|integer|min:10|max:100',
                ],
                [
                    'key' => 'enable_user_registration',
                    'type' => 'checkbox',
                    'label' => 'Enable User Registration',
                    'tab' => 'registration',
                    'default' => true,
                    'hint' => 'Allow new users to register',
                ],
                [
                    'key' => 'require_email_verification',
                    'type' => 'checkbox',
                    'label' => 'Require Email Verification',
                    'tab' => 'registration',
                    'default' => true,
                    'hint' => 'Require users to verify their email address',
                ],
                [
                    'key' => 'default_role',
                    'type' => 'select',
                    'label' => 'Default Role',
                    'tab' => 'registration',
                    'default' => 'user',
                    'hint' => 'Default role assigned to new users',
                    'options' => [], // Populated dynamically
                ],
                [
                    'key' => 'password_min_length',
                    'type' => 'number',
                    'label' => 'Minimum Password Length',
                    'tab' => 'security',
                    'default' => 8,
                    'min' => 6,
                    'max' => 32,
                    'hint' => 'Minimum number of characters for passwords',
                    'rules' => 'required|integer|min:6|max:32',
                ],
                [
                    'key' => 'session_lifetime',
                    'type' => 'number',
                    'label' => 'Session Lifetime (minutes)',
                    'tab' => 'security',
                    'default' => 120,
                    'min' => 15,
                    'max' => 1440,
                    'hint' => 'How long a user session lasts',
                    'rules' => 'required|integer|min:15|max:1440',
                ],
                [
                    'key' => 'enable_two_factor',
                    'type' => 'checkbox',
                    'label' => 'Enable Two-Factor Authentication',
                    'tab' => 'security',
                    'default' => false,
                    'hint' => 'Allow users to enable 2FA for their accounts',
                ],
                [
                    'key' => 'max_login_attempts',
                    'type' => 'number',
                    'label' => 'Max Login Attempts',
                    'tab' => 'security',
                    'default' => 5,
                    'min' => 3,
                    'max' => 10,
                    'hint' => 'Number of failed login attempts before lockout',
                    'rules' => 'required|integer|min:3|max:10',
                ],
                [
                    'key' => 'lockout_duration',
                    'type' => 'number',
                    'label' => 'Lockout Duration (minutes)',
                    'tab' => 'security',
                    'default' => 15,
                    'min' => 5,
                    'max' => 60,
                    'hint' => 'Duration of account lockout after failed attempts',
                    'rules' => 'required|integer|min:5|max:60',
                ],
                [
                    'key' => 'allow_avatar_upload',
                    'type' => 'checkbox',
                    'label' => 'Allow Avatar Upload',
                    'tab' => 'profile',
                    'default' => true,
                    'hint' => 'Allow users to upload profile pictures',
                ],
                [
                    'key' => 'max_avatar_size',
                    'type' => 'number',
                    'label' => 'Max Avatar Size (KB)',
                    'tab' => 'profile',
                    'default' => 2048,
                    'min' => 256,
                    'max' => 10240,
                    'hint' => 'Maximum file size for avatar uploads',
                    'rules' => 'required|integer|min:256|max:10240',
                ],
                [
                    'key' => 'allowed_avatar_types',
                    'type' => 'text',
                    'label' => 'Allowed Avatar Types',
                    'tab' => 'profile',
                    'default' => 'jpg,jpeg,png,gif,webp',
                    'hint' => 'Comma-separated list of allowed file extensions',
                ],
            ],
        ];
    }

    /**
     * Check if this plugin has a dashboard.
     */
    public function hasDashboard(): bool
    {
        return true;
    }

    /**
     * Get the dashboard icon.
     */
    public function getDashboardIcon(): string
    {
        return 'users';
    }

    /**
     * Get the dashboard title.
     */
    public function getDashboardTitle(): string
    {
        return 'User Management Dashboard';
    }

    /**
     * Get API endpoints registered by this plugin.
     */
    public function getApiEndpoints(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => '/users',
                'name' => 'List Users',
                'permission' => 'ums.users.view',
                'controller' => 'UserApiController@index',
            ],
            [
                'method' => 'POST',
                'path' => '/users',
                'name' => 'Create User',
                'permission' => 'ums.users.create',
                'controller' => 'UserApiController@store',
            ],
            [
                'method' => 'GET',
                'path' => '/users/{id}',
                'name' => 'Get User',
                'permission' => 'ums.users.view',
                'controller' => 'UserApiController@show',
            ],
            [
                'method' => 'PUT',
                'path' => '/users/{id}',
                'name' => 'Update User',
                'permission' => 'ums.users.edit',
                'controller' => 'UserApiController@update',
            ],
            [
                'method' => 'DELETE',
                'path' => '/users/{id}',
                'name' => 'Delete User',
                'permission' => 'ums.users.delete',
                'controller' => 'UserApiController@destroy',
            ],
            [
                'method' => 'GET',
                'path' => '/roles',
                'name' => 'List Roles',
                'permission' => 'ums.roles.view',
                'controller' => 'RoleApiController@index',
            ],
            [
                'method' => 'GET',
                'path' => '/permissions',
                'name' => 'List Permissions',
                'permission' => 'ums.permissions.view',
                'controller' => 'PermissionApiController@index',
            ],
        ];
    }

    /**
     * Get workflow triggers registered by this plugin.
     */
    public function getWorkflowTriggers(): array
    {
        return [
            'ums.user.created' => [
                'label' => 'User Created',
                'description' => 'Triggered when a new user is created',
                'payload' => ['user_id', 'email', 'name'],
            ],
            'ums.user.updated' => [
                'label' => 'User Updated',
                'description' => 'Triggered when a user is updated',
                'payload' => ['user_id', 'changes'],
            ],
            'ums.user.deleted' => [
                'label' => 'User Deleted',
                'description' => 'Triggered when a user is deleted',
                'payload' => ['user_id'],
            ],
            'ums.user.login' => [
                'label' => 'User Logged In',
                'description' => 'Triggered when a user logs in',
                'payload' => ['user_id', 'ip_address'],
            ],
            'ums.role.assigned' => [
                'label' => 'Role Assigned',
                'description' => 'Triggered when a role is assigned to a user',
                'payload' => ['user_id', 'role_id'],
            ],
        ];
    }
}

