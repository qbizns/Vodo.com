<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Dashboard View Type - Widget container.
 *
 * Features:
 * - Grid-based widget layout
 * - Multiple widget types (stat, chart, list, table)
 * - Drag and drop widget arrangement
 * - Widget resizing
 * - Auto-refresh
 * - User customization
 */
class DashboardViewType extends AbstractViewType
{
    protected string $name = 'dashboard';
    protected string $label = 'Dashboard View';
    protected string $description = 'Widget container for overview pages and home screens';
    protected string $icon = 'layout-dashboard';
    protected string $category = 'analytics';
    protected int $priority = 8;
    protected bool $requiresEntity = false;

    protected array $supportedFeatures = [
        'grid_layout',
        'drag_drop',
        'widget_resize',
        'auto_refresh',
        'user_customization',
        'widget_filters',
        'full_screen',
    ];

    protected array $defaultConfig = [
        'layout' => 'grid', // grid, columns, free
        'columns' => 12,
        'row_height' => 80,
        'gap' => 16,
        'refresh_interval' => 0, // 0 = no auto-refresh
        'user_customizable' => true,
        'allow_add_widgets' => true,
        'allow_remove_widgets' => true,
    ];

    protected array $extensionPoints = [
        'before_dashboard' => 'Content before the dashboard',
        'after_dashboard' => 'Content after the dashboard',
        'widget_header' => 'Custom widget header',
        'widget_footer' => 'Custom widget footer',
    ];

    protected array $availableActions = [
        'dashboard' => [
            'refresh' => ['label' => 'Refresh', 'icon' => 'refresh'],
            'add_widget' => ['label' => 'Add Widget', 'icon' => 'plus'],
            'reset_layout' => ['label' => 'Reset Layout', 'icon' => 'refresh-cw'],
            'export' => ['label' => 'Export', 'icon' => 'download'],
        ],
        'widget' => [
            'refresh' => ['label' => 'Refresh', 'icon' => 'refresh'],
            'configure' => ['label' => 'Configure', 'icon' => 'settings'],
            'remove' => ['label' => 'Remove', 'icon' => 'x'],
            'fullscreen' => ['label' => 'Fullscreen', 'icon' => 'maximize'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'widgets'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'dashboard'],
                'name' => ['type' => 'string'],
                'layout' => ['type' => 'string', 'enum' => ['grid', 'columns', 'free']],
                'widgets' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'required' => ['type', 'title'],
                        'properties' => [
                            'type' => [
                                'type' => 'string',
                                'enum' => ['stat', 'chart', 'list', 'table', 'custom'],
                            ],
                            'title' => ['type' => 'string'],
                            'position' => [
                                'type' => 'object',
                                'properties' => [
                                    'x' => ['type' => 'integer'],
                                    'y' => ['type' => 'integer'],
                                    'w' => ['type' => 'integer'],
                                    'h' => ['type' => 'integer'],
                                ],
                            ],
                            'data_source' => ['type' => 'string'],
                            'config' => ['type' => 'object'],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['widgets']) || !is_array($definition['widgets'])) {
            $this->addError('widgets', 'Dashboard requires at least one widget');
            return;
        }

        foreach ($definition['widgets'] as $index => $widget) {
            if (empty($widget['type'])) {
                $this->addError("widgets.{$index}.type", 'Widget type is required');
            }
            if (empty($widget['title'])) {
                $this->addError("widgets.{$index}.title", 'Widget title is required');
            }
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        return [
            'type' => 'dashboard',
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Dashboard',
            'widgets' => [
                [
                    'type' => 'stat',
                    'title' => 'Total Records',
                    'position' => ['x' => 0, 'y' => 0, 'w' => 3, 'h' => 1],
                    'data_source' => "{$entityName}.count",
                ],
                [
                    'type' => 'chart',
                    'title' => 'Records Over Time',
                    'position' => ['x' => 3, 'y' => 0, 'w' => 9, 'h' => 2],
                    'data_source' => "{$entityName}.by_date",
                    'config' => ['chart_type' => 'line'],
                ],
                [
                    'type' => 'list',
                    'title' => 'Recent Records',
                    'position' => ['x' => 0, 'y' => 1, 'w' => 6, 'h' => 2],
                    'data_source' => "{$entityName}.recent",
                    'config' => ['limit' => 5],
                ],
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
