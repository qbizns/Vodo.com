<?php

namespace App\Services;

use App\Models\DashboardWidget;
use App\Models\Plugin;
use App\Services\Plugins\PluginManager;
use Illuminate\Support\Collection;

class DashboardService
{
    /**
     * The plugin manager instance.
     */
    protected PluginManager $pluginManager;

    /**
     * Built-in system widgets.
     */
    protected array $systemWidgets = [];

    /**
     * Create a new DashboardService instance.
     */
    public function __construct(PluginManager $pluginManager)
    {
        $this->pluginManager = $pluginManager;
        $this->registerSystemWidgets();
    }

    /**
     * Register built-in system widgets.
     */
    protected function registerSystemWidgets(): void
    {
        $this->systemWidgets = [
            'welcome' => [
                'title' => 'Welcome',
                'description' => 'Welcome message and quick stats',
                'icon' => 'home',
                'component' => 'welcome',
                'default_width' => 4,
                'default_height' => 1,
                'refreshable' => false,
                'configurable' => false,
                'system' => true,
            ],
            'quick-actions' => [
                'title' => 'Quick Actions',
                'description' => 'Shortcuts to common tasks',
                'icon' => 'zap',
                'component' => 'quick-actions',
                'default_width' => 2,
                'default_height' => 1,
                'refreshable' => false,
                'configurable' => true,
                'system' => true,
            ],
            'system-health' => [
                'title' => 'System Health',
                'description' => 'Server and application status',
                'icon' => 'activity',
                'component' => 'stats',
                'default_width' => 2,
                'default_height' => 1,
                'refreshable' => true,
                'configurable' => false,
                'system' => true,
            ],
            'recent-activity' => [
                'title' => 'Recent Activity',
                'description' => 'Latest system activities',
                'icon' => 'clock',
                'component' => 'list',
                'default_width' => 2,
                'default_height' => 2,
                'refreshable' => true,
                'configurable' => true,
                'system' => true,
            ],
        ];
    }

    /**
     * Get all plugins that have dashboards.
     */
    public function getPluginsWithDashboards(): array
    {
        $plugins = Plugin::active()->get();
        $pluginsWithDashboards = [];

        foreach ($plugins as $plugin) {
            try {
                $instance = $this->pluginManager->getLoadedPlugin($plugin->slug);
                
                if (!$instance) {
                    $instance = $this->pluginManager->loadPluginInstance($plugin);
                }
                
                if ($instance && method_exists($instance, 'hasDashboard') && $instance->hasDashboard()) {
                    $pluginsWithDashboards[] = [
                        'slug' => $plugin->slug,
                        'name' => $plugin->name,
                        'icon' => $instance->getDashboardIcon(),
                        'title' => $instance->getDashboardTitle(),
                        'instance' => $instance,
                    ];
                }
            } catch (\Throwable $e) {
                // Skip plugins that can't be loaded
                continue;
            }
        }

        return $pluginsWithDashboards;
    }

    /**
     * Get user's main dashboard widgets.
     */
    public function getMainDashboardWidgets(string $userType, int $userId): Collection
    {
        $widgets = DashboardWidget::mainDashboard($userType, $userId)->get();

        // If no widgets configured, return defaults
        if ($widgets->isEmpty()) {
            $this->initializeDefaultWidgets($userType, $userId, 'main');
            $widgets = DashboardWidget::mainDashboard($userType, $userId)->get();
        }

        return $this->hydrateWidgets($widgets, 'main');
    }

    /**
     * Get user's plugin dashboard widgets.
     */
    public function getPluginDashboardWidgets(string $slug, string $userType, int $userId): Collection
    {
        $widgets = DashboardWidget::pluginDashboard($userType, $userId, $slug)->get();

        // If no widgets configured, return defaults from plugin
        if ($widgets->isEmpty()) {
            $this->initializePluginDefaultWidgets($slug, $userType, $userId);
            $widgets = DashboardWidget::pluginDashboard($userType, $userId, $slug)->get();
        }

        return $this->hydrateWidgets($widgets, $slug);
    }

    /**
     * Hydrate widgets with their definitions.
     */
    protected function hydrateWidgets(Collection $widgets, string $dashboard): Collection
    {
        $availableWidgets = $this->getAvailableWidgets($dashboard);

        return $widgets->map(function ($widget) use ($availableWidgets) {
            $definition = $availableWidgets[$widget->widget_id] ?? null;
            
            if ($definition) {
                $widget->definition = $definition;
            } else {
                $widget->definition = [
                    'title' => 'Unknown Widget',
                    'icon' => 'alertCircle',
                    'component' => 'custom',
                ];
            }

            return $widget;
        });
    }

    /**
     * Get all available widgets for a dashboard.
     */
    public function getAvailableWidgets(string $dashboard = 'main'): array
    {
        $widgets = [];

        // Add system widgets for main dashboard
        if ($dashboard === 'main') {
            $widgets = $this->systemWidgets;

            // Add widgets from all plugins with dashboards
            $pluginsWithDashboards = $this->getPluginsWithDashboards();
            foreach ($pluginsWithDashboards as $plugin) {
                $instance = $plugin['instance'];
                $pluginWidgets = $instance->getDashboardWidgets();
                
                foreach ($pluginWidgets as $widgetId => $widgetDef) {
                    $fullWidgetId = $plugin['slug'] . ':' . $widgetId;
                    $widgetDef['plugin_slug'] = $plugin['slug'];
                    $widgetDef['plugin_name'] = $plugin['name'];
                    $widgets[$fullWidgetId] = $widgetDef;
                }
            }
        } else {
            // Plugin-specific dashboard - only get widgets from that plugin
            $plugin = Plugin::where('slug', $dashboard)->first();
            if ($plugin) {
                try {
                    $instance = $this->pluginManager->getLoadedPlugin($dashboard);
                    if (!$instance) {
                        $instance = $this->pluginManager->loadPluginInstance($plugin);
                    }
                    
                    if ($instance && method_exists($instance, 'getDashboardWidgets')) {
                        $pluginWidgets = $instance->getDashboardWidgets();
                        foreach ($pluginWidgets as $widgetId => $widgetDef) {
                            $widgetDef['plugin_slug'] = $dashboard;
                            $widgetDef['plugin_name'] = $plugin->name;
                            $widgets[$widgetId] = $widgetDef;
                        }
                    }
                } catch (\Throwable $e) {
                    // Skip if plugin can't be loaded
                }
            }
        }

        return $widgets;
    }

    /**
     * Initialize default widgets for a user's main dashboard.
     */
    protected function initializeDefaultWidgets(string $userType, int $userId, string $dashboard): void
    {
        $defaults = [
            [
                'widget_id' => 'welcome',
                'plugin_slug' => null,
                'position' => 0,
                'col' => 0,
                'row' => 0,
                'width' => 4,
                'height' => 1,
            ],
            [
                'widget_id' => 'quick-actions',
                'plugin_slug' => null,
                'position' => 1,
                'col' => 0,
                'row' => 1,
                'width' => 2,
                'height' => 1,
            ],
            [
                'widget_id' => 'system-health',
                'plugin_slug' => null,
                'position' => 2,
                'col' => 2,
                'row' => 1,
                'width' => 2,
                'height' => 1,
            ],
        ];

        foreach ($defaults as $widget) {
            DashboardWidget::create([
                'user_type' => $userType,
                'user_id' => $userId,
                'dashboard' => $dashboard,
                'widget_id' => $widget['widget_id'],
                'plugin_slug' => $widget['plugin_slug'],
                'position' => $widget['position'],
                'col' => $widget['col'],
                'row' => $widget['row'],
                'width' => $widget['width'],
                'height' => $widget['height'],
                'visible' => true,
            ]);
        }
    }

    /**
     * Initialize default widgets for a plugin dashboard.
     */
    protected function initializePluginDefaultWidgets(string $slug, string $userType, int $userId): void
    {
        $availableWidgets = $this->getAvailableWidgets($slug);
        
        $position = 0;
        $col = 0;
        $row = 0;

        foreach ($availableWidgets as $widgetId => $definition) {
            DashboardWidget::create([
                'user_type' => $userType,
                'user_id' => $userId,
                'dashboard' => $slug,
                'widget_id' => $widgetId,
                'plugin_slug' => $slug,
                'position' => $position,
                'col' => $col,
                'row' => $row,
                'width' => $definition['default_width'] ?? 1,
                'height' => $definition['default_height'] ?? 1,
                'visible' => true,
            ]);

            $position++;
            $col += $definition['default_width'] ?? 1;
            if ($col >= 4) {
                $col = 0;
                $row++;
            }
        }
    }

    /**
     * Save widget layout for a dashboard.
     */
    public function saveWidgetLayout(string $dashboard, array $widgets, string $userType, int $userId): bool
    {
        try {
            foreach ($widgets as $widgetData) {
                DashboardWidget::updateOrCreate(
                    [
                        'user_type' => $userType,
                        'user_id' => $userId,
                        'dashboard' => $dashboard,
                        'widget_id' => $widgetData['widget_id'],
                    ],
                    [
                        'position' => $widgetData['position'] ?? 0,
                        'col' => $widgetData['col'] ?? 0,
                        'row' => $widgetData['row'] ?? 0,
                        'width' => $widgetData['width'] ?? 1,
                        'height' => $widgetData['height'] ?? 1,
                        'visible' => $widgetData['visible'] ?? true,
                    ]
                );
            }
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Add a widget to a dashboard.
     */
    public function addWidget(string $dashboard, string $widgetId, string $userType, int $userId, ?string $pluginSlug = null): ?DashboardWidget
    {
        // Get available widgets to find default dimensions
        $availableWidgets = $this->getAvailableWidgets($dashboard);
        $definition = $availableWidgets[$widgetId] ?? null;

        if (!$definition) {
            return null;
        }

        // Find the next position
        $maxPosition = DashboardWidget::forUser($userType, $userId, $dashboard)
            ->max('position') ?? -1;

        return DashboardWidget::create([
            'user_type' => $userType,
            'user_id' => $userId,
            'dashboard' => $dashboard,
            'widget_id' => $widgetId,
            'plugin_slug' => $pluginSlug ?? $definition['plugin_slug'] ?? null,
            'position' => $maxPosition + 1,
            'col' => 0,
            'row' => 99, // Will be repositioned by frontend
            'width' => $definition['default_width'] ?? 1,
            'height' => $definition['default_height'] ?? 1,
            'visible' => true,
        ]);
    }

    /**
     * Remove a widget from a dashboard.
     */
    public function removeWidget(string $dashboard, string $widgetId, string $userType, int $userId): bool
    {
        return DashboardWidget::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('dashboard', $dashboard)
            ->where('widget_id', $widgetId)
            ->delete() > 0;
    }

    /**
     * Update widget settings.
     */
    public function updateWidgetSettings(string $dashboard, string $widgetId, array $settings, string $userType, int $userId): bool
    {
        $widget = DashboardWidget::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('dashboard', $dashboard)
            ->where('widget_id', $widgetId)
            ->first();

        if ($widget) {
            $widget->settings = array_merge($widget->settings ?? [], $settings);
            return $widget->save();
        }

        return false;
    }

    /**
     * Get widget data from plugin.
     */
    public function getWidgetData(string $widgetId, ?string $pluginSlug = null): array
    {
        // System widget data
        if (!$pluginSlug && isset($this->systemWidgets[$widgetId])) {
            return $this->getSystemWidgetData($widgetId);
        }

        // Plugin widget data
        if ($pluginSlug) {
            $plugin = Plugin::where('slug', $pluginSlug)->first();
            if ($plugin) {
                try {
                    $instance = $this->pluginManager->getLoadedPlugin($pluginSlug);
                    if (!$instance) {
                        $instance = $this->pluginManager->loadPluginInstance($plugin);
                    }
                    
                    if ($instance && method_exists($instance, 'getWidgetData')) {
                        return $instance->getWidgetData($widgetId);
                    }
                } catch (\Throwable $e) {
                    // Return empty data on error
                }
            }
        }

        return ['widget_id' => $widgetId, 'data' => []];
    }

    /**
     * Get system widget data.
     */
    protected function getSystemWidgetData(string $widgetId): array
    {
        switch ($widgetId) {
            case 'welcome':
                return [
                    'widget_id' => 'welcome',
                    'data' => [
                        'greeting' => $this->getGreeting(),
                        'date' => now()->format('l, F j, Y'),
                    ],
                ];

            case 'system-health':
                return [
                    'widget_id' => 'system-health',
                    'data' => [
                        'items' => [
                            ['label' => 'Server Status', 'value' => 'Online', 'status' => 'success'],
                            ['label' => 'Database', 'value' => 'Connected', 'status' => 'success'],
                            ['label' => 'Cache', 'value' => 'Active', 'status' => 'success'],
                        ],
                    ],
                ];

            case 'quick-actions':
                return [
                    'widget_id' => 'quick-actions',
                    'data' => [
                        'actions' => [
                            ['label' => 'Settings', 'icon' => 'settings', 'url' => '/system/settings'],
                            ['label' => 'Plugins', 'icon' => 'plug', 'url' => '/system/plugins'],
                        ],
                    ],
                ];

            case 'recent-activity':
                return [
                    'widget_id' => 'recent-activity',
                    'data' => [
                        'items' => [
                            ['title' => 'System started', 'time' => now()->subHours(2)->diffForHumans()],
                            ['title' => 'Cache cleared', 'time' => now()->subHours(4)->diffForHumans()],
                        ],
                    ],
                ];

            default:
                return ['widget_id' => $widgetId, 'data' => []];
        }
    }

    /**
     * Get time-based greeting.
     */
    protected function getGreeting(): string
    {
        $hour = now()->hour;
        
        if ($hour < 12) {
            return 'Good morning';
        } elseif ($hour < 17) {
            return 'Good afternoon';
        } else {
            return 'Good evening';
        }
    }

    /**
     * Get user's widgets not yet added to a dashboard.
     */
    public function getUnusedWidgets(string $dashboard, string $userType, int $userId): array
    {
        $available = $this->getAvailableWidgets($dashboard);
        $used = DashboardWidget::forUser($userType, $userId, $dashboard)
            ->pluck('widget_id')
            ->toArray();

        $unused = [];
        foreach ($available as $widgetId => $definition) {
            if (!in_array($widgetId, $used)) {
                $unused[$widgetId] = $definition;
            }
        }

        return $unused;
    }

    /**
     * Reset dashboard to defaults.
     */
    public function resetDashboard(string $dashboard, string $userType, int $userId): bool
    {
        // Delete all widgets for this dashboard
        DashboardWidget::where('user_type', $userType)
            ->where('user_id', $userId)
            ->where('dashboard', $dashboard)
            ->delete();

        // Re-initialize defaults
        if ($dashboard === 'main') {
            $this->initializeDefaultWidgets($userType, $userId, $dashboard);
        } else {
            $this->initializePluginDefaultWidgets($dashboard, $userType, $userId);
        }

        return true;
    }
}
