<?php

namespace App\Plugins\hello_world;

use App\Services\Plugins\BasePlugin;
use Illuminate\Support\Facades\Log;

class HelloWorldPlugin extends BasePlugin
{
    /**
     * Register plugin services and bindings.
     */
    public function register(): void
    {
        // Register any bindings or services here
        Log::info('Hello World Plugin: Registered');
    }

    /**
     * Bootstrap the plugin.
     */
    public function boot(): void
    {
        parent::boot();

        // Navigation is now loaded automatically from plugin.json
        // You can still add programmatic navigation if needed:
        // $this->addNavigationItem([...], 'Category Name');

        // Add a filter to modify dashboard widgets (example)
        $this->addFilter('dashboard_widgets', function (array $widgets) {
            $widgets[] = [
                'id' => 'hello-world-widget',
                'title' => 'Hello World',
                'content' => 'This widget is added by the Hello World plugin!',
            ];
            return $widgets;
        });

        // Add an action for admin footer (example)
        $this->addAction('admin_footer', function () {
            echo '<!-- Hello World Plugin Footer -->';
        });

        Log::info('Hello World Plugin: Booted');
    }

    /**
     * Handle plugin activation.
     */
    public function activate(): void
    {
        // Create default settings
        $this->setSetting('greeting', 'Hello, World!');
        $this->setSetting('show_count', true);
        $this->setSetting('display_mode', 'card');
        $this->setSetting('max_greetings', 10);
        
        Log::info('Hello World Plugin: Activated');
    }

    /**
     * Handle plugin deactivation.
     */
    public function deactivate(): void
    {
        Log::info('Hello World Plugin: Deactivated');
    }

    /**
     * Handle plugin uninstallation.
     */
    public function uninstall(): void
    {
        // Clean up any plugin-specific data
        Log::info('Hello World Plugin: Uninstalled');
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
     * Get the settings fields definition for this plugin.
     */
    public function getSettingsFields(): array
    {
        return [
            'general' => [
                'title' => 'General Settings',
                'description' => 'Configure basic Hello World plugin options',
                'fields' => [
                    'greeting' => [
                        'type' => 'text',
                        'label' => 'Greeting Message',
                        'description' => 'The greeting message to display',
                        'default' => 'Hello, World!',
                    ],
                    'show_count' => [
                        'type' => 'toggle',
                        'label' => 'Show Greeting Count',
                        'description' => 'Display the total number of greetings',
                        'default' => true,
                    ],
                ],
            ],
            'display' => [
                'title' => 'Display Options',
                'description' => 'Customize how greetings are displayed',
                'fields' => [
                    'display_mode' => [
                        'type' => 'select',
                        'label' => 'Display Mode',
                        'description' => 'Choose how greetings appear on the page',
                        'default' => 'card',
                        'options' => [
                            'card' => 'Card View',
                            'list' => 'List View',
                            'grid' => 'Grid View',
                        ],
                    ],
                    'max_greetings' => [
                        'type' => 'number',
                        'label' => 'Maximum Greetings',
                        'description' => 'Maximum number of greetings to display per page',
                        'default' => 10,
                        'min' => 1,
                        'max' => 100,
                    ],
                ],
            ],
            'advanced' => [
                'title' => 'Advanced Settings',
                'description' => 'Advanced configuration options',
                'fields' => [
                    'enable_api' => [
                        'type' => 'toggle',
                        'label' => 'Enable API Access',
                        'description' => 'Allow external access to greetings via API',
                        'default' => false,
                    ],
                    'cache_duration' => [
                        'type' => 'number',
                        'label' => 'Cache Duration (minutes)',
                        'description' => 'How long to cache greetings data',
                        'default' => 60,
                        'min' => 0,
                        'max' => 1440,
                    ],
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
     * Get the dashboard widgets for this plugin.
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
                                'value' => rand(50, 200), // Sample data
                                'status' => 'success',
                            ],
                            [
                                'label' => 'Today',
                                'value' => rand(5, 30),
                                'status' => 'success',
                            ],
                        ],
                    ],
                ];

            case 'recent-greetings':
                $greetings = [
                    ['title' => 'Hello from New York', 'time' => '2 minutes ago'],
                    ['title' => 'Bonjour from Paris', 'time' => '5 minutes ago'],
                    ['title' => 'Hola from Madrid', 'time' => '10 minutes ago'],
                    ['title' => 'Ciao from Rome', 'time' => '15 minutes ago'],
                    ['title' => 'Hallo from Berlin', 'time' => '20 minutes ago'],
                ];
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
