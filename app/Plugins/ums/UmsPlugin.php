<?php

declare(strict_types=1);

namespace App\Plugins\ums;

use App\Models\UIViewDefinition;
use App\Services\Entity\EntityRegistry;
use App\Services\Plugins\BasePlugin;
use App\Services\Plugins\CircuitBreaker;
use App\Services\View\ViewRegistry;
use App\Traits\HasTenantCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * User Management System Plugin - Enterprise user and access management.
 *
 * Demonstrates platform features for system/admin plugins:
 * - Entity Registry: user_profile, team, activity_log entities
 * - View Registry: List, Form, Kanban, Activity, Tree views
 * - Permission Registry: Fine-grained RBAC
 * - Workflow Triggers: User lifecycle automation
 * - Circuit Breaker: Protected hook execution
 * - Tenant Cache: Multi-tenant data isolation
 *
 * @see docs/plugins/DEVELOPMENT_GUIDE.md
 */
class UmsPlugin extends BasePlugin
{
    use HasTenantCache;

    /**
     * Plugin identifier.
     */
    public const SLUG = 'ums';

    /**
     * Plugin version.
     */
    public const VERSION = '2.0.0';

    /**
     * Registries.
     */
    protected ?EntityRegistry $entityRegistry = null;
    protected ?ViewRegistry $viewRegistry = null;
    protected ?CircuitBreaker $circuitBreaker = null;

    /**
     * Register plugin services and bindings.
     */
    public function register(): void
    {
        $this->mergeConfig();
        Log::debug('UMS Plugin: Registered', ['version' => self::VERSION]);
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();

        $this->initializeRegistries();
        $this->registerEntities();
        $this->registerViews();
        $this->registerProtectedHooks();

        Log::info('UMS Plugin: Booted', [
            'version' => self::VERSION,
            'entities' => ['user_profile', 'team', 'activity_log'],
        ]);
    }

    /**
     * Initialize registry instances.
     */
    protected function initializeRegistries(): void
    {
        $this->entityRegistry = EntityRegistry::getInstance();

        if (app()->bound(ViewRegistry::class)) {
            $this->viewRegistry = app(ViewRegistry::class);
        }

        if (app()->bound(CircuitBreaker::class)) {
            $this->circuitBreaker = app(CircuitBreaker::class);
        }
    }

    /**
     * Register entities with full field definitions.
     */
    protected function registerEntities(): void
    {
        // =====================================================================
        // USER PROFILE ENTITY - Extended user information
        // =====================================================================
        $this->entityRegistry->register('user_profile', [
            'labels' => [
                'singular' => 'User Profile',
                'plural' => 'User Profiles',
            ],
            'icon' => 'userCircle',
            'supports' => ['author'],
            'is_public' => false,
            'show_in_menu' => false,
            'fields' => [
                'user_id' => [
                    'type' => 'relation',
                    'label' => 'User',
                    'required' => true,
                    'unique' => true,
                    'config' => [
                        'entity' => 'user',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                ],
                'avatar' => [
                    'type' => 'image',
                    'label' => 'Avatar',
                    'config' => [
                        'max_size' => 2048,
                        'allowed_types' => ['jpg', 'jpeg', 'png', 'webp'],
                        'dimensions' => ['width' => 500, 'height' => 500],
                    ],
                ],
                'bio' => [
                    'type' => 'text',
                    'label' => 'Bio',
                    'config' => ['max_length' => 500],
                ],
                'phone' => [
                    'type' => 'phone',
                    'label' => 'Phone Number',
                ],
                'timezone' => [
                    'type' => 'select',
                    'label' => 'Timezone',
                    'default' => 'UTC',
                    'config' => [
                        'options' => 'timezones', // Dynamic options
                    ],
                ],
                'locale' => [
                    'type' => 'select',
                    'label' => 'Language',
                    'default' => 'en',
                    'config' => [
                        'options' => [
                            'en' => 'English',
                            'ar' => 'Arabic',
                            'es' => 'Spanish',
                            'fr' => 'French',
                        ],
                    ],
                ],
                'date_format' => [
                    'type' => 'select',
                    'label' => 'Date Format',
                    'default' => 'Y-m-d',
                    'config' => [
                        'options' => [
                            'Y-m-d' => '2024-01-15',
                            'd/m/Y' => '15/01/2024',
                            'm/d/Y' => '01/15/2024',
                            'd-m-Y' => '15-01-2024',
                        ],
                    ],
                ],
                'social_links' => [
                    'type' => 'json',
                    'label' => 'Social Links',
                    'config' => [
                        'schema' => [
                            'linkedin' => 'url|null',
                            'twitter' => 'url|null',
                            'github' => 'url|null',
                        ],
                    ],
                ],
                'preferences' => [
                    'type' => 'json',
                    'label' => 'Preferences',
                    'config' => [
                        'schema' => [
                            'email_notifications' => 'boolean',
                            'push_notifications' => 'boolean',
                            'newsletter' => 'boolean',
                            'dark_mode' => 'boolean',
                        ],
                    ],
                ],
                'two_factor_enabled' => [
                    'type' => 'boolean',
                    'label' => '2FA Enabled',
                    'default' => false,
                ],
                'last_login_at' => [
                    'type' => 'datetime',
                    'label' => 'Last Login',
                    'sortable' => true,
                ],
                'last_login_ip' => [
                    'type' => 'string',
                    'label' => 'Last Login IP',
                ],
            ],
        ], self::SLUG);

        // =====================================================================
        // TEAM ENTITY - User groups/teams
        // =====================================================================
        $this->entityRegistry->register('team', [
            'labels' => [
                'singular' => 'Team',
                'plural' => 'Teams',
            ],
            'icon' => 'users',
            'supports' => ['title', 'content', 'author'],
            'is_public' => false,
            'show_in_menu' => true,
            'menu_position' => 15,
            'is_hierarchical' => true,
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'label' => 'Team Name',
                    'required' => true,
                    'searchable' => true,
                    'show_in_list' => true,
                ],
                'slug' => [
                    'type' => 'slug',
                    'label' => 'Slug',
                    'required' => true,
                    'unique' => true,
                    'config' => ['source' => 'name'],
                ],
                'description' => [
                    'type' => 'text',
                    'label' => 'Description',
                ],
                'leader_id' => [
                    'type' => 'relation',
                    'label' => 'Team Leader',
                    'config' => [
                        'entity' => 'user',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                    'show_in_list' => true,
                ],
                'parent_id' => [
                    'type' => 'relation',
                    'label' => 'Parent Team',
                    'config' => [
                        'entity' => 'team',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                ],
                'members' => [
                    'type' => 'relation',
                    'label' => 'Members',
                    'config' => [
                        'entity' => 'user',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to_many',
                    ],
                ],
                'color' => [
                    'type' => 'color',
                    'label' => 'Team Color',
                    'default' => '#3b82f6',
                ],
                'icon' => [
                    'type' => 'select',
                    'label' => 'Icon',
                    'default' => 'users',
                    'config' => [
                        'options' => [
                            'users' => 'Users',
                            'building' => 'Building',
                            'briefcase' => 'Briefcase',
                            'code' => 'Code',
                            'palette' => 'Design',
                            'megaphone' => 'Marketing',
                            'headphones' => 'Support',
                        ],
                    ],
                ],
                'is_active' => [
                    'type' => 'boolean',
                    'label' => 'Active',
                    'default' => true,
                    'show_in_list' => true,
                    'filterable' => true,
                ],
                'member_count' => [
                    'type' => 'integer',
                    'label' => 'Members',
                    'show_in_list' => true,
                    'system' => true,
                ],
            ],
        ], self::SLUG);

        // =====================================================================
        // ACTIVITY LOG ENTITY - User activity tracking
        // =====================================================================
        $this->entityRegistry->register('activity_log', [
            'labels' => [
                'singular' => 'Activity',
                'plural' => 'Activities',
            ],
            'icon' => 'activity',
            'supports' => [],
            'is_public' => false,
            'show_in_menu' => true,
            'menu_position' => 50,
            'fields' => [
                'user_id' => [
                    'type' => 'relation',
                    'label' => 'User',
                    'config' => [
                        'entity' => 'user',
                        'display_field' => 'name',
                        'relationship' => 'belongs_to',
                    ],
                    'searchable' => true,
                    'filterable' => true,
                    'show_in_list' => true,
                ],
                'action' => [
                    'type' => 'select',
                    'label' => 'Action',
                    'required' => true,
                    'config' => [
                        'options' => [
                            'login' => 'Login',
                            'logout' => 'Logout',
                            'create' => 'Create',
                            'update' => 'Update',
                            'delete' => 'Delete',
                            'view' => 'View',
                            'export' => 'Export',
                            'import' => 'Import',
                        ],
                    ],
                    'show_in_list' => true,
                    'filterable' => true,
                ],
                'entity_type' => [
                    'type' => 'string',
                    'label' => 'Entity Type',
                    'show_in_list' => true,
                    'filterable' => true,
                ],
                'entity_id' => [
                    'type' => 'integer',
                    'label' => 'Entity ID',
                ],
                'description' => [
                    'type' => 'string',
                    'label' => 'Description',
                    'searchable' => true,
                    'show_in_list' => true,
                ],
                'changes' => [
                    'type' => 'json',
                    'label' => 'Changes',
                    'description' => 'Before/after values for updates',
                ],
                'ip_address' => [
                    'type' => 'string',
                    'label' => 'IP Address',
                    'filterable' => true,
                ],
                'user_agent' => [
                    'type' => 'string',
                    'label' => 'User Agent',
                ],
                'metadata' => [
                    'type' => 'json',
                    'label' => 'Metadata',
                ],
            ],
        ], self::SLUG);
    }

    /**
     * Register views for entities.
     */
    protected function registerViews(): void
    {
        if (!$this->viewRegistry) {
            return;
        }

        // =====================================================================
        // TEAM VIEWS
        // =====================================================================

        // List View
        $this->viewRegistry->registerListView('team', [
            'name' => 'Teams List',
            'columns' => [
                'name' => ['label' => 'Team Name', 'sortable' => true, 'link' => true],
                'leader_id' => ['label' => 'Leader', 'widget' => 'many2one'],
                'member_count' => ['label' => 'Members', 'widget' => 'badge'],
                'color' => ['label' => 'Color', 'widget' => 'color'],
                'is_active' => ['label' => 'Active', 'widget' => 'boolean'],
            ],
            'default_order' => 'name asc',
            'selectable' => true,
        ], self::SLUG);

        // Form View
        $this->viewRegistry->registerFormView('team', [
            'name' => 'Team Form',
            'groups' => [
                'basic' => [
                    'label' => 'Basic Information',
                    'columns' => 2,
                    'fields' => [
                        'name' => ['widget' => 'char', 'required' => true],
                        'slug' => ['widget' => 'slug'],
                        'description' => ['widget' => 'text', 'colspan' => 2],
                    ],
                ],
                'organization' => [
                    'label' => 'Organization',
                    'columns' => 2,
                    'fields' => [
                        'leader_id' => ['widget' => 'many2one'],
                        'parent_id' => ['widget' => 'many2one'],
                        'members' => ['widget' => 'many2many', 'colspan' => 2],
                    ],
                ],
                'appearance' => [
                    'label' => 'Appearance',
                    'columns' => 3,
                    'fields' => [
                        'color' => ['widget' => 'color'],
                        'icon' => ['widget' => 'selection'],
                        'is_active' => ['widget' => 'checkbox'],
                    ],
                ],
            ],
        ], self::SLUG);

        // Tree View - Hierarchical team structure
        $this->viewRegistry->registerView('team', UIViewDefinition::TYPE_TREE, [
            'name' => 'Team Hierarchy',
            'parent_field' => 'parent_id',
            'child_field' => 'children',
            'display_field' => 'name',
            'icon_field' => 'icon',
            'color_field' => 'color',
            'expandable' => true,
            'drag_drop' => true,
        ], self::SLUG);

        // Kanban View - Teams by status
        $this->viewRegistry->registerKanbanView('team', [
            'name' => 'Teams Kanban',
            'group_by' => 'is_active',
            'card' => [
                'title' => 'name',
                'subtitle' => 'leader_id',
                'fields' => ['member_count', 'description'],
                'color_field' => 'color',
            ],
        ], self::SLUG);

        // =====================================================================
        // ACTIVITY LOG VIEWS
        // =====================================================================

        // List View
        $this->viewRegistry->registerListView('activity_log', [
            'name' => 'Activity Log',
            'columns' => [
                'created_at' => ['label' => 'Time', 'widget' => 'datetime', 'sortable' => true],
                'user_id' => ['label' => 'User', 'widget' => 'many2one'],
                'action' => ['label' => 'Action', 'widget' => 'badge'],
                'entity_type' => ['label' => 'Entity'],
                'description' => ['label' => 'Description'],
                'ip_address' => ['label' => 'IP'],
            ],
            'default_order' => 'created_at desc',
            'editable' => false,
            'row_colors' => [
                'action' => [
                    'delete' => 'danger',
                    'create' => 'success',
                    'login' => 'info',
                ],
            ],
        ], self::SLUG);

        // Activity View (Timeline)
        $this->viewRegistry->registerView('activity_log', UIViewDefinition::TYPE_ACTIVITY, [
            'name' => 'Activity Timeline',
            'user_field' => 'user_id',
            'date_field' => 'created_at',
            'action_field' => 'action',
            'description_field' => 'description',
            'icon_mapping' => [
                'login' => 'logIn',
                'logout' => 'logOut',
                'create' => 'plus',
                'update' => 'edit',
                'delete' => 'trash',
            ],
        ], self::SLUG);

        // =====================================================================
        // DASHBOARD VIEW
        // =====================================================================
        $this->viewRegistry->registerView('user_profile', UIViewDefinition::TYPE_DASHBOARD, [
            'name' => 'User Management Dashboard',
            'slug' => 'ums_dashboard',
            'widgets' => [
                'total_users' => [
                    'type' => 'kpi',
                    'title' => 'Total Users',
                    'metric' => 'count:user',
                ],
                'active_users' => [
                    'type' => 'kpi',
                    'title' => 'Active Users (30d)',
                    'metric' => 'count:activity_log',
                    'filter' => ['action' => 'login', 'created_at' => '>=30_days_ago'],
                ],
                'teams' => [
                    'type' => 'kpi',
                    'title' => 'Active Teams',
                    'metric' => 'count:team',
                    'filter' => ['is_active' => true],
                ],
                'activity_chart' => [
                    'type' => 'chart',
                    'title' => 'User Activity',
                    'chart_type' => 'line',
                    'data_source' => 'activity_log',
                ],
            ],
        ], self::SLUG);
    }

    /**
     * Register hooks with circuit breaker protection.
     */
    protected function registerProtectedHooks(): void
    {
        // User lifecycle hooks
        $this->addProtectedAction('user_created', function ($user) {
            // Create user profile
            $this->entityRegistry->createRecord('user_profile', [
                'user_id' => $user->id,
                'locale' => config('app.locale', 'en'),
                'timezone' => config('app.timezone', 'UTC'),
            ]);

            // Log activity
            $this->logActivity($user->id, 'create', 'user', $user->id, 'User account created');

            Log::info('UMS: User created', ['user_id' => $user->id]);
        });

        $this->addProtectedAction('user_updated', function ($user) {
            $this->forgetTenantCache("user:{$user->id}");
            $this->logActivity($user->id, 'update', 'user', $user->id, 'User account updated');
            Log::info('UMS: User updated', ['user_id' => $user->id]);
        });

        $this->addProtectedAction('user_deleted', function ($user) {
            $this->forgetTenantCache("user:{$user->id}");
            $this->logActivity(null, 'delete', 'user', $user->id, "User {$user->email} deleted");
            Log::info('UMS: User deleted', ['user_id' => $user->id]);
        });

        $this->addProtectedAction('user_login', function ($user, $request = null) {
            $ip = $request?->ip() ?? request()->ip();

            // Update last login
            $this->entityRegistry->query('user_profile')
                ->where('user_id', $user->id)
                ->update([
                    'last_login_at' => now(),
                    'last_login_ip' => $ip,
                ]);

            $this->logActivity($user->id, 'login', 'user', $user->id, 'User logged in', ['ip' => $ip]);
        });

        $this->addProtectedAction('user_logout', function ($user) {
            $this->logActivity($user->id, 'logout', 'user', $user->id, 'User logged out');
        });

        // Filter to enrich user data
        $this->addFilter('user_data', function (array $data) {
            if (isset($data['id'])) {
                $profile = $this->tenantCache("user_profile:{$data['id']}", function () use ($data) {
                    return $this->entityRegistry->query('user_profile')
                        ->where('user_id', $data['id'])
                        ->first();
                }, 300);

                if ($profile) {
                    $data['profile'] = $profile->toArray();
                }
            }
            return $data;
        });
    }

    /**
     * Add an action with circuit breaker protection.
     */
    protected function addProtectedAction(string $hook, callable $callback, int $priority = 10): void
    {
        if (!$this->circuitBreaker) {
            $this->addAction($hook, $callback, $priority);
            return;
        }

        $hookKey = CircuitBreaker::hookKey($hook, self::SLUG);

        $this->addAction($hook, function (...$args) use ($callback, $hookKey) {
            if ($this->circuitBreaker->isOpen($hookKey)) {
                Log::warning("Hook skipped due to circuit breaker: {$hookKey}");
                return;
            }

            try {
                $callback(...$args);
                $this->circuitBreaker->recordSuccess($hookKey);
            } catch (\Throwable $e) {
                $this->circuitBreaker->recordFailure($hookKey, $e);
                Log::error("Hook failed: {$hookKey}", ['error' => $e->getMessage()]);
                throw $e;
            }
        }, $priority);
    }

    /**
     * Log user activity.
     */
    protected function logActivity(
        ?int $userId,
        string $action,
        string $entityType,
        ?int $entityId,
        string $description,
        array $metadata = []
    ): void {
        try {
            $this->entityRegistry->createRecord('activity_log', [
                'user_id' => $userId,
                'action' => $action,
                'entity_type' => $entityType,
                'entity_id' => $entityId,
                'description' => $description,
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
                'metadata' => $metadata,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to log activity', ['error' => $e->getMessage()]);
        }
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

    // =========================================================================
    // LIFECYCLE METHODS
    // =========================================================================

    public function activate(): void
    {
        $this->onActivate();
    }

    public function onActivate(): void
    {
        $this->setSetting('users_per_page', 25);
        $this->setSetting('enable_user_registration', true);
        $this->setSetting('require_email_verification', true);
        $this->setSetting('password_min_length', 8);
        $this->setSetting('session_lifetime', 120);
        $this->setSetting('enable_two_factor', false);
        $this->setSetting('allow_avatar_upload', true);
        $this->setSetting('max_avatar_size', 2048);

        Cache::forget('ums.users');
        Cache::forget('ums.settings');

        Log::info('UMS Plugin: Activated', ['version' => self::VERSION]);
    }

    public function deactivate(): void
    {
        $this->onDeactivate();
    }

    public function onDeactivate(): void
    {
        Cache::forget('ums.users');
        Cache::forget('ums.settings');
        Log::info('UMS Plugin: Deactivated');
    }

    public function uninstall(): void
    {
        $this->onUninstall(false);
    }

    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            $this->entityRegistry?->unregister('user_profile', self::SLUG);
            $this->entityRegistry?->unregister('team', self::SLUG);
            $this->entityRegistry?->unregister('activity_log', self::SLUG);
        }

        Cache::forget('ums.users');
        Cache::forget('ums.settings');
        Log::info('UMS Plugin: Uninstalled', ['keep_data' => $keepData]);
    }

    public function onUpdate(string $fromVersion, string $toVersion): void
    {
        Cache::forget('ums.users');
        Cache::forget('ums.settings');
        Log::info("UMS Plugin: Updated from {$fromVersion} to {$toVersion}");
    }

    // =========================================================================
    // SETTINGS & DASHBOARD
    // =========================================================================

    public function hasSettingsPage(): bool
    {
        return true;
    }

    public function getSettingsIcon(): string
    {
        return 'users';
    }

    public function hasDashboard(): bool
    {
        return true;
    }

    public function getDashboardIcon(): string
    {
        return 'users';
    }

    public function getDashboardTitle(): string
    {
        return 'User Management Dashboard';
    }

    public function getPermissions(): array
    {
        return [
            'ums.users.view' => ['label' => 'View Users', 'description' => 'Can view user list and details', 'group' => 'User Management'],
            'ums.users.create' => ['label' => 'Create Users', 'description' => 'Can create new users', 'group' => 'User Management'],
            'ums.users.edit' => ['label' => 'Edit Users', 'description' => 'Can modify existing users', 'group' => 'User Management'],
            'ums.users.delete' => ['label' => 'Delete Users', 'description' => 'Can delete users', 'group' => 'User Management'],
            'ums.users.impersonate' => ['label' => 'Impersonate Users', 'description' => 'Can log in as another user', 'group' => 'User Management', 'is_dangerous' => true],
            'ums.teams.view' => ['label' => 'View Teams', 'description' => 'Can view team list', 'group' => 'User Management'],
            'ums.teams.manage' => ['label' => 'Manage Teams', 'description' => 'Can create, edit and delete teams', 'group' => 'User Management'],
            'ums.roles.view' => ['label' => 'View Roles', 'description' => 'Can view role list', 'group' => 'User Management'],
            'ums.roles.manage' => ['label' => 'Manage Roles', 'description' => 'Can create, edit and delete roles', 'group' => 'User Management'],
            'ums.activity.view' => ['label' => 'View Activity Log', 'description' => 'Can view user activity', 'group' => 'User Management'],
            'ums.settings' => ['label' => 'UMS Settings', 'description' => 'Can configure user management settings', 'group' => 'User Management'],
        ];
    }

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
                    ['id' => 'ums.dashboard', 'label' => 'Dashboard', 'icon' => 'layoutDashboard', 'url' => '/plugins/ums', 'permission' => 'ums.users.view'],
                    ['id' => 'ums.users', 'label' => 'Users', 'icon' => 'user', 'url' => '/plugins/ums/users', 'permission' => 'ums.users.view'],
                    ['id' => 'ums.teams', 'label' => 'Teams', 'icon' => 'users', 'url' => '/plugins/ums/teams', 'permission' => 'ums.teams.view'],
                    ['id' => 'ums.roles', 'label' => 'Roles', 'icon' => 'shield', 'url' => '/plugins/ums/roles', 'permission' => 'ums.roles.view'],
                    ['id' => 'ums.activity', 'label' => 'Activity Log', 'icon' => 'activity', 'url' => '/plugins/ums/activity', 'permission' => 'ums.activity.view'],
                ],
            ],
        ];
    }

    public function getWidgets(): array
    {
        return [
            ['id' => 'ums-user-stats', 'name' => 'User Statistics', 'description' => 'Overview of user statistics', 'component' => 'ums::widgets.stats', 'permissions' => ['ums.users.view'], 'default_width' => 4, 'default_height' => 2],
            ['id' => 'ums-recent-users', 'name' => 'Recent Users', 'description' => 'Recently registered users', 'component' => 'ums::widgets.recent-users', 'permissions' => ['ums.users.view'], 'default_width' => 6, 'default_height' => 3],
            ['id' => 'ums-activity', 'name' => 'Recent Activity', 'description' => 'Recent user activity', 'component' => 'ums::widgets.activity', 'permissions' => ['ums.activity.view'], 'default_width' => 6, 'default_height' => 3],
        ];
    }

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
                ['key' => 'users_per_page', 'type' => 'number', 'label' => 'Users Per Page', 'tab' => 'general', 'default' => 25, 'min' => 10, 'max' => 100],
                ['key' => 'enable_user_registration', 'type' => 'checkbox', 'label' => 'Enable User Registration', 'tab' => 'registration', 'default' => true],
                ['key' => 'require_email_verification', 'type' => 'checkbox', 'label' => 'Require Email Verification', 'tab' => 'registration', 'default' => true],
                ['key' => 'password_min_length', 'type' => 'number', 'label' => 'Min Password Length', 'tab' => 'security', 'default' => 8, 'min' => 6, 'max' => 32],
                ['key' => 'enable_two_factor', 'type' => 'checkbox', 'label' => 'Enable 2FA', 'tab' => 'security', 'default' => false],
                ['key' => 'allow_avatar_upload', 'type' => 'checkbox', 'label' => 'Allow Avatar Upload', 'tab' => 'profile', 'default' => true],
                ['key' => 'max_avatar_size', 'type' => 'number', 'label' => 'Max Avatar Size (KB)', 'tab' => 'profile', 'default' => 2048],
            ],
        ];
    }

    public function getWorkflowTriggers(): array
    {
        return [
            'ums.user.created' => ['label' => 'User Created', 'description' => 'When a new user is created', 'payload' => ['user_id', 'email', 'name']],
            'ums.user.updated' => ['label' => 'User Updated', 'description' => 'When a user is updated', 'payload' => ['user_id', 'changes']],
            'ums.user.deleted' => ['label' => 'User Deleted', 'description' => 'When a user is deleted', 'payload' => ['user_id']],
            'ums.user.login' => ['label' => 'User Logged In', 'description' => 'When a user logs in', 'payload' => ['user_id', 'ip_address']],
            'ums.user.logout' => ['label' => 'User Logged Out', 'description' => 'When a user logs out', 'payload' => ['user_id']],
            'ums.team.created' => ['label' => 'Team Created', 'description' => 'When a team is created', 'payload' => ['team_id', 'name']],
            'ums.team.member_added' => ['label' => 'Team Member Added', 'description' => 'When a user joins a team', 'payload' => ['team_id', 'user_id']],
        ];
    }

    public function getApiEndpoints(): array
    {
        return [
            ['method' => 'GET', 'path' => '/users', 'name' => 'List Users', 'permission' => 'ums.users.view'],
            ['method' => 'POST', 'path' => '/users', 'name' => 'Create User', 'permission' => 'ums.users.create'],
            ['method' => 'GET', 'path' => '/users/{id}', 'name' => 'Get User', 'permission' => 'ums.users.view'],
            ['method' => 'PUT', 'path' => '/users/{id}', 'name' => 'Update User', 'permission' => 'ums.users.edit'],
            ['method' => 'DELETE', 'path' => '/users/{id}', 'name' => 'Delete User', 'permission' => 'ums.users.delete'],
            ['method' => 'GET', 'path' => '/teams', 'name' => 'List Teams', 'permission' => 'ums.teams.view'],
            ['method' => 'GET', 'path' => '/activity', 'name' => 'Activity Log', 'permission' => 'ums.activity.view'],
        ];
    }

    public function getEntities(): array
    {
        return [
            'user_profile' => ['label' => 'User Profile', 'label_plural' => 'User Profiles', 'icon' => 'userCircle'],
            'team' => ['label' => 'Team', 'label_plural' => 'Teams', 'icon' => 'users'],
            'activity_log' => ['label' => 'Activity', 'label_plural' => 'Activities', 'icon' => 'activity'],
        ];
    }
}
