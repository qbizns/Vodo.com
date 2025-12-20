<?php

namespace App\Plugins\hello_world;

use App\Services\Plugins\BasePlugin;
use HelloWorld\Models\Greeting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class HelloWorldPlugin extends BasePlugin
{
    /**
     * Plugin identifier
     */
    public const SLUG = 'hello-world';

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
        Log::info('Hello World Plugin: Registered');
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();
        $this->registerEventListeners();
        $this->registerFiltersAndActions();
        Log::info('Hello World Plugin: Booted');
    }

    /**
     * Merge plugin configuration.
     */
    protected function mergeConfig(): void
    {
        $configPath = $this->basePath . '/config/hello-world.php';
        
        if (file_exists($configPath)) {
            config()->set('hello-world', require $configPath);
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
        // Add a filter to modify dashboard widgets
        $this->addFilter('dashboard_widgets', function (array $widgets) {
            $widgets[] = [
                'id' => 'hello-world-widget',
                'title' => 'Hello World',
                'content' => 'This widget is added by the Hello World plugin!',
            ];
            return $widgets;
        });

        // Add an action for admin footer
        $this->addAction('admin_footer', function () {
            echo '<!-- Hello World Plugin Footer -->';
        });
    }

    /**
     * Called when plugin is being activated
     */
    public function onActivate(): void
    {
        // Create default settings
        $this->setSetting('greeting', 'Hello, World!');
        $this->setSetting('show_count', true);
        $this->setSetting('display_mode', 'card');
        $this->setSetting('max_greetings', 10);
        $this->setSetting('enable_api', false);
        $this->setSetting('cache_duration', 60);

        // Publish assets
        $this->publishAssets();

        // Clear caches
        Cache::forget('hello-world.greetings');

        Log::info('Hello World Plugin: Activated');
    }

    /**
     * Called when plugin is being deactivated
     */
    public function onDeactivate(): void
    {
        // Clear plugin-specific caches
        Cache::forget('hello-world.greetings');
        Cache::forget('hello-world.settings');

        Log::info('Hello World Plugin: Deactivated');
    }

    /**
     * Called before plugin is uninstalled
     */
    public function onUninstall(bool $keepData = false): void
    {
        if (!$keepData) {
            // Drop plugin tables
            $this->dropTables();
        }

        // Remove published assets
        $this->unpublishAssets();

        // Clear all caches
        Cache::forget('hello-world.greetings');
        Cache::forget('hello-world.settings');

        Log::info('Hello World Plugin: Uninstalled');
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

        // Update assets
        $this->publishAssets();

        // Clear caches
        Cache::forget('hello-world.greetings');

        Log::info("Hello World Plugin: Updated from {$fromVersion} to {$toVersion}");
    }

    /**
     * Publish plugin assets.
     */
    protected function publishAssets(): void
    {
        // Asset publishing logic
    }

    /**
     * Unpublish plugin assets.
     */
    protected function unpublishAssets(): void
    {
        // Asset removal logic
    }

    /**
     * Drop plugin database tables.
     */
    protected function dropTables(): void
    {
        \Illuminate\Support\Facades\Schema::dropIfExists('hello_world_greetings');
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
     * Get the greeting message.
     */
    public function getGreeting(): string
    {
        return $this->getSetting('greeting', 'Hello, World!');
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
        return 'smile';
    }

    /**
     * Get permissions registered by this plugin.
     */
    public function getPermissions(): array
    {
        return [
            'greetings.view' => [
                'label' => 'View Greetings',
                'description' => 'Can view greeting list and details',
                'group' => 'Greetings',
            ],
            'greetings.create' => [
                'label' => 'Create Greetings',
                'description' => 'Can create new greetings',
                'group' => 'Greetings',
            ],
            'greetings.edit' => [
                'label' => 'Edit Greetings',
                'description' => 'Can modify existing greetings',
                'group' => 'Greetings',
            ],
            'greetings.delete' => [
                'label' => 'Delete Greetings',
                'description' => 'Can delete greetings',
                'group' => 'Greetings',
            ],
            'greetings.settings' => [
                'label' => 'Greeting Settings',
                'description' => 'Can configure plugin settings',
                'group' => 'Greetings',
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
                'id' => 'hello-world',
                'label' => 'Hello World',
                'icon' => 'smile',
                'route' => 'plugins.hello-world.index',
                'permission' => 'greetings.view',
                'position' => 30,
                'children' => [
                    [
                        'id' => 'hello-world.index',
                        'label' => 'Dashboard',
                        'route' => 'plugins.hello-world.index',
                        'permission' => 'greetings.view',
                    ],
                    [
                        'id' => 'hello-world.greetings',
                        'label' => 'Greetings',
                        'route' => 'plugins.hello-world.greetings',
                        'permission' => 'greetings.view',
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
                'id' => 'greeting-stats',
                'name' => 'Greeting Statistics',
                'description' => 'Overview of greeting statistics',
                'component' => 'hello-world::widgets.stats',
                'permissions' => ['greetings.view'],
                'default_width' => 4,
                'default_height' => 2,
            ],
            [
                'id' => 'recent-greetings',
                'name' => 'Recent Greetings',
                'description' => 'List of recent greeting messages',
                'component' => 'hello-world::widgets.recent',
                'permissions' => ['greetings.view'],
                'default_width' => 6,
                'default_height' => 3,
            ],
            [
                'id' => 'greeting-message',
                'name' => 'Current Greeting',
                'description' => 'Shows the current configured greeting',
                'component' => 'hello-world::widgets.message',
                'permissions' => ['greetings.view'],
                'default_width' => 4,
                'default_height' => 2,
            ],
        ];
    }

    /**
     * Get entity definitions registered by this plugin.
     */
    public function getEntities(): array
    {
        return [
            'greeting' => [
                'label' => 'Greeting',
                'label_plural' => 'Greetings',
                'model' => Greeting::class,
                'table' => 'hello_world_greetings',
                'icon' => 'message-square',
                'searchable' => true,
                'fields' => [
                    'id' => ['type' => 'integer', 'primary' => true],
                    'message' => ['type' => 'string', 'label' => 'Message', 'searchable' => true],
                    'author' => ['type' => 'string', 'label' => 'Author', 'searchable' => true],
                    'created_at' => ['type' => 'datetime', 'label' => 'Created At'],
                    'updated_at' => ['type' => 'datetime', 'label' => 'Updated At'],
                ],
            ],
        ];
    }

    /**
     * Get API endpoints registered by this plugin.
     */
    public function getApiEndpoints(): array
    {
        return [
            [
                'method' => 'GET',
                'path' => '/greetings',
                'name' => 'List Greetings',
                'permission' => 'greetings.view',
                'controller' => 'GreetingApiController@index',
            ],
            [
                'method' => 'POST',
                'path' => '/greetings',
                'name' => 'Create Greeting',
                'permission' => 'greetings.create',
                'controller' => 'GreetingApiController@store',
            ],
            [
                'method' => 'GET',
                'path' => '/greetings/{id}',
                'name' => 'Get Greeting',
                'permission' => 'greetings.view',
                'controller' => 'GreetingApiController@show',
            ],
            [
                'method' => 'PUT',
                'path' => '/greetings/{id}',
                'name' => 'Update Greeting',
                'permission' => 'greetings.edit',
                'controller' => 'GreetingApiController@update',
            ],
            [
                'method' => 'DELETE',
                'path' => '/greetings/{id}',
                'name' => 'Delete Greeting',
                'permission' => 'greetings.delete',
                'controller' => 'GreetingApiController@destroy',
            ],
        ];
    }

    /**
     * Get scheduled tasks registered by this plugin.
     */
    public function getScheduledTasks(): array
    {
        return [
            [
                'name' => 'Clear Old Greetings',
                'description' => 'Remove greetings older than 30 days',
                'command' => 'hello-world:clear-old-greetings',
                'schedule' => 'daily',
                'enabled' => false,
            ],
        ];
    }

    /**
     * Get workflow triggers registered by this plugin.
     */
    public function getWorkflowTriggers(): array
    {
        return [
            'greeting.created' => [
                'label' => 'Greeting Created',
                'description' => 'Triggered when a new greeting is created',
                'payload' => ['greeting_id', 'message', 'author'],
            ],
            'greeting.deleted' => [
                'label' => 'Greeting Deleted',
                'description' => 'Triggered when a greeting is deleted',
                'payload' => ['greeting_id'],
            ],
        ];
    }

    /**
     * Get shortcodes registered by this plugin.
     */
    public function getShortcodes(): array
    {
        return [
            'greeting' => [
                'label' => 'Display Greeting',
                'description' => 'Display the current greeting message',
                'handler' => function ($attributes) {
                    return $this->getGreeting();
                },
                'attributes' => [],
            ],
            'greeting_count' => [
                'label' => 'Greeting Count',
                'description' => 'Display the total number of greetings',
                'handler' => function ($attributes) {
                    return Greeting::count();
                },
                'attributes' => [],
            ],
        ];
    }

    /**
     * Get the settings fields definition for this plugin.
     * Uses tab-based structure per documentation.
     */
    public function getSettingsFields(): array
    {
        return [
            'tabs' => [
                'general' => ['label' => 'General', 'icon' => 'settings'],
                'display' => ['label' => 'Display', 'icon' => 'layout'],
                'advanced' => ['label' => 'Advanced', 'icon' => 'sliders'],
            ],
            'fields' => [
                [
                    'key' => 'greeting',
                    'type' => 'text',
                    'label' => 'Greeting Message',
                    'tab' => 'general',
                    'default' => 'Hello, World!',
                    'hint' => 'The greeting message to display',
                    'rules' => 'required|string|max:255',
                ],
                [
                    'key' => 'show_count',
                    'type' => 'checkbox',
                    'label' => 'Show Greeting Count',
                    'tab' => 'general',
                    'default' => true,
                    'hint' => 'Display the total number of greetings',
                ],
                [
                    'key' => 'display_mode',
                    'type' => 'select',
                    'label' => 'Display Mode',
                    'tab' => 'display',
                    'default' => 'card',
                    'hint' => 'Choose how greetings appear on the page',
                    'options' => [
                        'card' => 'Card View',
                        'list' => 'List View',
                        'grid' => 'Grid View',
                    ],
                    'rules' => 'required|in:card,list,grid',
                ],
                [
                    'key' => 'max_greetings',
                    'type' => 'number',
                    'label' => 'Maximum Greetings',
                    'tab' => 'display',
                    'default' => 10,
                    'min' => 1,
                    'max' => 100,
                    'hint' => 'Maximum number of greetings to display per page',
                    'rules' => 'required|integer|min:1|max:100',
                ],
                [
                    'key' => 'enable_api',
                    'type' => 'checkbox',
                    'label' => 'Enable API Access',
                    'tab' => 'advanced',
                    'default' => false,
                    'hint' => 'Allow external access to greetings via API',
                ],
                [
                    'key' => 'cache_duration',
                    'type' => 'number',
                    'label' => 'Cache Duration (minutes)',
                    'tab' => 'advanced',
                    'default' => 60,
                    'min' => 0,
                    'max' => 1440,
                    'hint' => 'How long to cache greetings data',
                    'rules' => 'required|integer|min:0|max:1440',
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
        return 'smile';
    }

    /**
     * Get the dashboard title.
     */
    public function getDashboardTitle(): string
    {
        return 'Hello World Dashboard';
    }

    /**
     * Get the dashboard widgets for this plugin (legacy method).
     */
    public function getDashboardWidgets(): array
    {
        return [
            'greeting-stats' => [
                'title' => 'Greetings Today',
                'description' => 'Number of greetings delivered today',
                'icon' => 'smile',
                'component' => 'stats',
                'default_width' => 1,
                'default_height' => 1,
                'refreshable' => true,
                'configurable' => false,
            ],
            'recent-greetings' => [
                'title' => 'Recent Greetings',
                'description' => 'List of recent greeting messages',
                'icon' => 'messageSquare',
                'component' => 'list',
                'default_width' => 2,
                'default_height' => 2,
                'refreshable' => true,
                'configurable' => true,
            ],
            'greeting-message' => [
                'title' => 'Current Greeting',
                'description' => 'Shows the current configured greeting',
                'icon' => 'smile',
                'component' => 'stats',
                'default_width' => 2,
                'default_height' => 1,
                'refreshable' => true,
                'configurable' => false,
            ],
        ];
    }

    /**
     * Get widget data for a specific widget.
     */
    public function getWidgetData(string $widgetId): array
    {
        switch ($widgetId) {
            case 'greeting-stats':
                return [
                    'widget_id' => $widgetId,
                    'data' => [
                        'items' => [
                            [
                                'label' => 'Total Greetings',
                                'value' => Greeting::count(),
                                'status' => 'success',
                            ],
                            [
                                'label' => 'Today',
                                'value' => Greeting::whereDate('created_at', today())->count(),
                                'status' => 'success',
                            ],
                        ],
                    ],
                ];

            case 'recent-greetings':
                $greetings = Greeting::latest()->take(5)->get()->map(function ($greeting) {
                    return [
                        'title' => $greeting->message,
                        'time' => $greeting->created_at->diffForHumans(),
                    ];
                })->toArray();
                
                return [
                    'widget_id' => $widgetId,
                    'data' => [
                        'items' => $greetings,
                    ],
                ];

            case 'greeting-message':
                return [
                    'widget_id' => $widgetId,
                    'data' => [
                        'items' => [
                            [
                                'label' => 'Current Greeting',
                                'value' => $this->getGreeting(),
                                'status' => 'success',
                            ],
                        ],
                    ],
                ];

            default:
                return [
                    'widget_id' => $widgetId,
                    'data' => [],
                ];
        }
    }
}
