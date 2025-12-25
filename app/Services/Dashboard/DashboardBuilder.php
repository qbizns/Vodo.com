<?php

declare(strict_types=1);

namespace App\Services\Dashboard;

use App\Contracts\DashboardBuilderContract;
use App\Contracts\WidgetRegistryContract;
use App\Models\Dashboard;
use App\Models\DashboardUserLayout;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Dashboard Builder
 *
 * Manages dashboard layouts, widgets, and user customization.
 * Supports drag-and-drop layouts with persistent user preferences.
 *
 * @example Register a dashboard
 * ```php
 * $builder->register('sales_dashboard', [
 *     'title' => 'Sales Dashboard',
 *     'layout' => 'grid',
 *     'columns' => 3,
 *     'widgets' => [
 *         ['name' => 'revenue_chart', 'position' => ['x' => 0, 'y' => 0, 'w' => 2, 'h' => 2]],
 *         ['name' => 'top_customers', 'position' => ['x' => 2, 'y' => 0, 'w' => 1, 'h' => 2]],
 *     ],
 * ]);
 * ```
 *
 * @example Render a dashboard
 * ```php
 * $data = $builder->render('sales_dashboard');
 * ```
 */
class DashboardBuilder implements DashboardBuilderContract
{
    /**
     * Registered dashboards.
     *
     * @var array<string, array>
     */
    protected array $dashboards = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Default dashboard configuration.
     */
    protected array $defaultConfig = [
        'title' => '',
        'description' => null,
        'layout' => 'grid',
        'columns' => 12,
        'row_height' => 100,
        'widgets' => [],
        'filters' => [],
        'refresh_interval' => null,
        'is_default' => false,
    ];

    public function __construct(
        protected WidgetRegistryContract $widgetRegistry
    ) {}

    public function register(string $name, array $config, ?string $pluginSlug = null): self
    {
        $this->dashboards[$name] = array_merge($this->defaultConfig, $config, [
            'name' => $name,
        ]);

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Persist to database
        Dashboard::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['title'] ?? $this->generateLabel($name),
                'description' => $config['description'] ?? null,
                'layout' => $config['layout'] ?? 'grid',
                'columns' => $config['columns'] ?? 12,
                'row_height' => $config['row_height'] ?? 100,
                'widgets' => $config['widgets'] ?? [],
                'filters' => $config['filters'] ?? [],
                'refresh_interval' => $config['refresh_interval'] ?? null,
                'config' => $config,
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
                'is_default' => $config['is_default'] ?? false,
            ]
        );

        return $this;
    }

    public function get(string $name): ?array
    {
        if (isset($this->dashboards[$name])) {
            return $this->dashboards[$name];
        }

        $dashboard = Dashboard::where('slug', $name)->first();
        if ($dashboard) {
            return array_merge($this->defaultConfig, $dashboard->config ?? [], [
                'name' => $name,
                'title' => $dashboard->name,
                'layout' => $dashboard->layout,
                'columns' => $dashboard->columns,
                'widgets' => $dashboard->widgets,
                'filters' => $dashboard->filters,
            ]);
        }

        return null;
    }

    public function all(): Collection
    {
        $dbDashboards = Dashboard::all()->keyBy('slug')->map(fn($dashboard) => array_merge(
            $this->defaultConfig,
            $dashboard->config ?? [],
            [
                'name' => $dashboard->slug,
                'title' => $dashboard->name,
                'layout' => $dashboard->layout,
                'columns' => $dashboard->columns,
                'widgets' => $dashboard->widgets,
            ]
        ));

        return collect($this->dashboards)->merge($dbDashboards);
    }

    public function render(string $name, array $context = []): array
    {
        $config = $this->get($name);

        if (!$config) {
            throw new \InvalidArgumentException("Dashboard not found: {$name}");
        }

        // Check for user customization
        $userId = auth()->id();
        if ($userId) {
            $userLayout = $this->getUserLayout($userId, $name);
            if ($userLayout) {
                $config['widgets'] = $userLayout['widgets'] ?? $config['widgets'];
            }
        }

        // Render each widget
        $renderedWidgets = [];
        foreach ($config['widgets'] as $widgetConfig) {
            $widgetName = $widgetConfig['name'];
            $position = $widgetConfig['position'] ?? [];
            $settings = $widgetConfig['settings'] ?? [];

            try {
                $widgetData = $this->widgetRegistry->render($widgetName, array_merge($context, $settings));
                $renderedWidgets[] = [
                    'name' => $widgetName,
                    'position' => $position,
                    'data' => $widgetData,
                    'settings' => $settings,
                ];
            } catch (\Exception $e) {
                $renderedWidgets[] = [
                    'name' => $widgetName,
                    'position' => $position,
                    'error' => $e->getMessage(),
                    'settings' => $settings,
                ];
            }
        }

        return [
            'name' => $name,
            'title' => $config['title'],
            'layout' => $config['layout'],
            'columns' => $config['columns'],
            'row_height' => $config['row_height'],
            'widgets' => $renderedWidgets,
            'filters' => $config['filters'],
            'refresh_interval' => $config['refresh_interval'],
            'rendered_at' => now()->toIso8601String(),
        ];
    }

    public function getUserLayout(int $userId, string $name): ?array
    {
        $layout = DashboardUserLayout::where('user_id', $userId)
            ->where('dashboard_slug', $name)
            ->first();

        return $layout ? $layout->layout : null;
    }

    public function saveUserLayout(int $userId, string $name, array $layout): void
    {
        DashboardUserLayout::updateOrCreate(
            ['user_id' => $userId, 'dashboard_slug' => $name],
            ['layout' => $layout]
        );

        // Fire hook
        do_action('dashboard_layout_saved', $name, $userId, $layout);
    }

    public function addWidget(string $name, string $widgetName, array $config = []): self
    {
        $dashboard = $this->get($name);

        if (!$dashboard) {
            throw new \InvalidArgumentException("Dashboard not found: {$name}");
        }

        // Check widget exists
        if (!$this->widgetRegistry->has($widgetName)) {
            throw new \InvalidArgumentException("Widget not found: {$widgetName}");
        }

        // Add widget with default position
        $widgets = $dashboard['widgets'] ?? [];
        $widgets[] = [
            'name' => $widgetName,
            'position' => $config['position'] ?? $this->calculateNextPosition($widgets),
            'settings' => $config['settings'] ?? [],
        ];

        // Update dashboard
        Dashboard::where('slug', $name)->update([
            'widgets' => $widgets,
        ]);

        // Update memory
        if (isset($this->dashboards[$name])) {
            $this->dashboards[$name]['widgets'] = $widgets;
        }

        return $this;
    }

    public function removeWidget(string $name, string $widgetName): bool
    {
        $dashboard = $this->get($name);

        if (!$dashboard) {
            return false;
        }

        $widgets = array_filter(
            $dashboard['widgets'] ?? [],
            fn($w) => $w['name'] !== $widgetName
        );

        Dashboard::where('slug', $name)->update([
            'widgets' => array_values($widgets),
        ]);

        if (isset($this->dashboards[$name])) {
            $this->dashboards[$name]['widgets'] = array_values($widgets);
        }

        return true;
    }

    public function getAvailableWidgets(string $name): Collection
    {
        $dashboard = $this->get($name);

        if (!$dashboard) {
            return collect();
        }

        $usedWidgets = array_column($dashboard['widgets'] ?? [], 'name');

        return $this->widgetRegistry->all()->filter(function ($widget) use ($usedWidgets) {
            // Filter out already used widgets or widgets not compatible
            return !in_array($widget->getName(), $usedWidgets);
        });
    }

    /**
     * Calculate next available position for a widget.
     */
    protected function calculateNextPosition(array $widgets): array
    {
        if (empty($widgets)) {
            return ['x' => 0, 'y' => 0, 'w' => 4, 'h' => 2];
        }

        // Find the lowest available position
        $maxY = 0;
        foreach ($widgets as $widget) {
            $pos = $widget['position'] ?? [];
            $endY = ($pos['y'] ?? 0) + ($pos['h'] ?? 2);
            $maxY = max($maxY, $endY);
        }

        return ['x' => 0, 'y' => $maxY, 'w' => 4, 'h' => 2];
    }

    /**
     * Generate a human-readable label.
     */
    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Reset user layout to default.
     */
    public function resetUserLayout(int $userId, string $name): void
    {
        DashboardUserLayout::where('user_id', $userId)
            ->where('dashboard_slug', $name)
            ->delete();
    }

    /**
     * Clone a dashboard.
     */
    public function clone(string $source, string $newName): self
    {
        $config = $this->get($source);

        if (!$config) {
            throw new \InvalidArgumentException("Dashboard not found: {$source}");
        }

        unset($config['name']);
        $config['title'] = $config['title'] . ' (Copy)';
        $config['is_default'] = false;

        return $this->register($newName, $config);
    }
}
