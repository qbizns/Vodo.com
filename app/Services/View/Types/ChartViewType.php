<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Chart View Type - Standalone visualization.
 *
 * Features:
 * - Multiple chart types (bar, line, pie, etc.)
 * - Interactive tooltips
 * - Legends
 * - Axis configuration
 * - Real-time updates
 */
class ChartViewType extends AbstractViewType
{
    protected string $name = 'chart';
    protected string $label = 'Chart View';
    protected string $description = 'Standalone chart and graph visualization for analytics';
    protected string $icon = 'bar-chart';
    protected string $category = 'analytics';
    protected int $priority = 16;

    protected array $supportedFeatures = [
        'bar_chart',
        'line_chart',
        'pie_chart',
        'doughnut_chart',
        'area_chart',
        'scatter_chart',
        'tooltips',
        'legends',
        'zoom',
        'export',
    ];

    protected array $defaultConfig = [
        'chart_type' => 'bar',
        'show_legend' => true,
        'show_tooltips' => true,
        'show_grid' => true,
        'animate' => true,
        'responsive' => true,
        'aspect_ratio' => 2,
    ];

    protected array $extensionPoints = [
        'before_chart' => 'Content before the chart',
        'after_chart' => 'Content after the chart',
        'tooltip_content' => 'Custom tooltip content',
    ];

    protected array $availableActions = [
        'export_image' => ['label' => 'Export Image', 'icon' => 'image'],
        'fullscreen' => ['label' => 'Fullscreen', 'icon' => 'maximize'],
        'refresh' => ['label' => 'Refresh', 'icon' => 'refresh'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'chart_type', 'data_source'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'chart'],
                'name' => ['type' => 'string'],
                'chart_type' => [
                    'type' => 'string',
                    'enum' => ['bar', 'line', 'pie', 'doughnut', 'area', 'scatter', 'radar', 'polar'],
                ],
                'data_source' => ['type' => 'string'],
                'labels' => ['type' => 'string'],
                'datasets' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'field' => ['type' => 'string'],
                            'color' => ['type' => 'string'],
                            'aggregate' => ['type' => 'string'],
                        ],
                    ],
                ],
                'options' => [
                    'type' => 'object',
                    'properties' => [
                        'x_axis' => ['type' => 'object'],
                        'y_axis' => ['type' => 'object'],
                        'legend' => ['type' => 'object'],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['chart_type'])) {
            $this->addError('chart_type', 'Chart type is required');
        }

        if (empty($definition['data_source']) && empty($definition['datasets'])) {
            $this->addError('data_source', 'Data source or datasets are required');
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Find numeric field for chart data
        $numericField = $fields->first(fn($f) =>
            in_array(is_array($f) ? $f['type'] : $f->type, ['integer', 'decimal', 'money'])
        );

        $valueField = $numericField
            ? (is_array($numericField) ? $numericField['slug'] : $numericField->slug)
            : 'count';

        return [
            'type' => 'chart',
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Chart',
            'chart_type' => 'bar',
            'data_source' => "{$entityName}.by_date",
            'datasets' => [
                [
                    'label' => Str::title(str_replace('_', ' ', $valueField)),
                    'field' => $valueField,
                    'aggregate' => 'sum',
                ],
            ],
            'config' => $this->getDefaultConfig(),
        ];
    }
}
