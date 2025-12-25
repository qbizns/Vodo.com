<?php

declare(strict_types=1);

namespace App\Services\Chart;

use App\Contracts\ChartEngineContract;
use App\Models\ChartDefinition;
use App\Models\EntityDefinition;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Chart Engine
 *
 * Handles chart definitions, data aggregation, and rendering.
 * Supports multiple chart types with dynamic data sources.
 *
 * @example Register a chart
 * ```php
 * $engine->register('monthly_revenue', [
 *     'type' => 'line',
 *     'title' => 'Monthly Revenue',
 *     'entity' => 'invoice',
 *     'x_axis' => ['field' => 'created_at', 'group' => 'month'],
 *     'y_axis' => ['field' => 'total', 'aggregate' => 'sum'],
 * ]);
 * ```
 *
 * @example Render chart data
 * ```php
 * $data = $engine->render('monthly_revenue', [
 *     'date_from' => '2024-01-01',
 *     'date_to' => '2024-12-31',
 * ]);
 * ```
 */
class ChartEngine implements ChartEngineContract
{
    /**
     * Registered chart types.
     *
     * @var array<string, array>
     */
    protected array $types = [];

    /**
     * Registered charts.
     *
     * @var array<string, array>
     */
    protected array $charts = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Default chart configuration.
     */
    protected array $defaultConfig = [
        'type' => 'bar',
        'title' => '',
        'subtitle' => null,
        'entity' => null,
        'query' => null,
        'x_axis' => [],
        'y_axis' => [],
        'series' => [],
        'colors' => [],
        'legend' => true,
        'stacked' => false,
        'parameters' => [],
    ];

    public function __construct()
    {
        $this->registerDefaultTypes();
    }

    /**
     * Register default chart types.
     */
    protected function registerDefaultTypes(): void
    {
        $this->types = [
            'line' => [
                'name' => 'Line Chart',
                'icon' => 'chart-line',
                'supports' => ['timeseries', 'comparison'],
            ],
            'bar' => [
                'name' => 'Bar Chart',
                'icon' => 'chart-bar',
                'supports' => ['categorical', 'comparison'],
            ],
            'horizontal_bar' => [
                'name' => 'Horizontal Bar Chart',
                'icon' => 'chart-bar-horizontal',
                'supports' => ['categorical', 'ranking'],
            ],
            'pie' => [
                'name' => 'Pie Chart',
                'icon' => 'chart-pie',
                'supports' => ['distribution'],
            ],
            'doughnut' => [
                'name' => 'Doughnut Chart',
                'icon' => 'chart-doughnut',
                'supports' => ['distribution'],
            ],
            'area' => [
                'name' => 'Area Chart',
                'icon' => 'chart-area',
                'supports' => ['timeseries', 'cumulative'],
            ],
            'scatter' => [
                'name' => 'Scatter Plot',
                'icon' => 'chart-scatter',
                'supports' => ['correlation'],
            ],
            'radar' => [
                'name' => 'Radar Chart',
                'icon' => 'chart-radar',
                'supports' => ['multivariate'],
            ],
            'gauge' => [
                'name' => 'Gauge',
                'icon' => 'gauge',
                'supports' => ['single-value'],
            ],
            'funnel' => [
                'name' => 'Funnel Chart',
                'icon' => 'filter',
                'supports' => ['stages'],
            ],
            'heatmap' => [
                'name' => 'Heatmap',
                'icon' => 'grid',
                'supports' => ['matrix'],
            ],
            'treemap' => [
                'name' => 'Treemap',
                'icon' => 'layout-grid',
                'supports' => ['hierarchical'],
            ],
        ];
    }

    public function registerType(string $type, array $config): self
    {
        $this->types[$type] = $config;

        return $this;
    }

    public function getTypes(): Collection
    {
        return collect($this->types);
    }

    public function register(string $name, array $config, ?string $pluginSlug = null): self
    {
        $this->charts[$name] = array_merge($this->defaultConfig, $config, [
            'name' => $name,
        ]);

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Persist to database (without callable query)
        $dbConfig = $config;
        unset($dbConfig['query']);

        ChartDefinition::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['title'] ?? $this->generateLabel($name),
                'type' => $config['type'] ?? 'bar',
                'entity' => $config['entity'] ?? null,
                'x_axis' => $config['x_axis'] ?? [],
                'y_axis' => $config['y_axis'] ?? [],
                'series' => $config['series'] ?? [],
                'config' => $dbConfig,
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
            ]
        );

        return $this;
    }

    public function get(string $name): ?array
    {
        if (isset($this->charts[$name])) {
            return $this->charts[$name];
        }

        $chart = ChartDefinition::where('slug', $name)->first();
        if ($chart) {
            return array_merge($this->defaultConfig, $chart->config ?? [], [
                'name' => $name,
                'title' => $chart->name,
                'type' => $chart->type,
                'entity' => $chart->entity,
                'x_axis' => $chart->x_axis,
                'y_axis' => $chart->y_axis,
                'series' => $chart->series,
            ]);
        }

        return null;
    }

    public function all(): Collection
    {
        $dbCharts = ChartDefinition::all()->keyBy('slug')->map(fn($chart) => array_merge(
            $this->defaultConfig,
            $chart->config ?? [],
            [
                'name' => $chart->slug,
                'title' => $chart->name,
                'type' => $chart->type,
                'entity' => $chart->entity,
                'x_axis' => $chart->x_axis,
                'y_axis' => $chart->y_axis,
            ]
        ));

        return collect($this->charts)->merge($dbCharts);
    }

    public function render(string $name, array $params = []): array
    {
        $config = $this->get($name);

        if (!$config) {
            throw new \InvalidArgumentException("Chart not found: {$name}");
        }

        // Get data
        $data = $this->getData($config, $params);

        // Format for frontend
        return [
            'name' => $name,
            'type' => $config['type'],
            'title' => $config['title'],
            'subtitle' => $config['subtitle'],
            'labels' => $data['labels'],
            'datasets' => $data['datasets'],
            'options' => $this->getChartOptions($config),
            'rendered_at' => now()->toIso8601String(),
        ];
    }

    public function fromQuery(string $type, mixed $query, array $config): array
    {
        $xField = $config['x_field'];
        $yField = $config['y_field'];
        $groupField = $config['group_field'] ?? null;
        $aggregate = $config['aggregate'] ?? 'count';

        // Clone query for grouping
        $results = (clone $query)
            ->select([
                $xField,
                $groupField,
                DB::raw($this->buildAggregate($aggregate, $yField) . ' as value'),
            ])
            ->when($groupField, fn($q) => $q->groupBy($groupField))
            ->groupBy($xField)
            ->orderBy($xField)
            ->get();

        return $this->formatChartData($results, $xField, $groupField, $type);
    }

    public function aggregate(string $entityName, array $config): array
    {
        $entity = EntityDefinition::where('name', $entityName)->first();
        if (!$entity) {
            throw new \InvalidArgumentException("Entity not found: {$entityName}");
        }

        $table = Str::plural(Str::snake($entityName));
        $query = DB::table($table);

        // Apply filters
        foreach ($config['filters'] ?? [] as $field => $value) {
            if (is_array($value) && isset($value['operator'])) {
                $query->where($field, $value['operator'], $value['value']);
            } else {
                $query->where($field, $value);
            }
        }

        // Apply date range
        if ($dateField = $config['date_field'] ?? null) {
            if ($from = $config['date_from'] ?? null) {
                $query->where($dateField, '>=', $from);
            }
            if ($to = $config['date_to'] ?? null) {
                $query->where($dateField, '<=', $to);
            }
        }

        $xField = $config['x_axis']['field'];
        $yField = $config['y_axis']['field'];
        $aggregate = $config['y_axis']['aggregate'] ?? 'sum';
        $groupBy = $config['x_axis']['group'] ?? null;

        // Apply grouping for time-based fields
        if ($groupBy) {
            $xField = $this->buildDateGrouping($xField, $groupBy);
        }

        $results = $query
            ->select([
                DB::raw("{$xField} as label"),
                DB::raw($this->buildAggregate($aggregate, $yField) . ' as value'),
            ])
            ->groupBy(DB::raw($xField))
            ->orderBy(DB::raw($xField))
            ->get();

        return [
            'labels' => $results->pluck('label')->toArray(),
            'datasets' => [
                [
                    'label' => $config['y_axis']['label'] ?? 'Value',
                    'data' => $results->pluck('value')->toArray(),
                ],
            ],
        ];
    }

    public function export(string $name, array $params = [], string $format = 'png'): string
    {
        // This would typically use a headless browser or chart library
        throw new \RuntimeException("Chart export requires a rendering library");
    }

    /**
     * Get data for a chart.
     */
    protected function getData(array $config, array $params): array
    {
        // Use callable query if provided
        if (isset($config['query']) && is_callable($config['query'])) {
            return call_user_func($config['query'], $params);
        }

        // Use entity-based aggregation
        if ($entity = $config['entity'] ?? null) {
            return $this->aggregate($entity, array_merge($config, $params));
        }

        return ['labels' => [], 'datasets' => []];
    }

    /**
     * Build aggregate expression.
     */
    protected function buildAggregate(string $aggregate, string $field): string
    {
        return match ($aggregate) {
            'sum' => "SUM({$field})",
            'avg' => "AVG({$field})",
            'min' => "MIN({$field})",
            'max' => "MAX({$field})",
            'count' => "COUNT({$field})",
            'count_distinct' => "COUNT(DISTINCT {$field})",
            default => "SUM({$field})",
        };
    }

    /**
     * Build date grouping expression.
     */
    protected function buildDateGrouping(string $field, string $group): string
    {
        return match ($group) {
            'year' => "YEAR({$field})",
            'quarter' => "CONCAT(YEAR({$field}), '-Q', QUARTER({$field}))",
            'month' => "DATE_FORMAT({$field}, '%Y-%m')",
            'week' => "DATE_FORMAT({$field}, '%Y-%u')",
            'day' => "DATE({$field})",
            'hour' => "DATE_FORMAT({$field}, '%Y-%m-%d %H:00')",
            default => $field,
        };
    }

    /**
     * Get chart options for frontend.
     */
    protected function getChartOptions(array $config): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'legend' => ['display' => $config['legend'] ?? true],
            'stacked' => $config['stacked'] ?? false,
            'colors' => $config['colors'] ?? [],
        ];
    }

    /**
     * Format raw data for chart rendering.
     */
    protected function formatChartData(Collection $results, string $xField, ?string $groupField, string $type): array
    {
        if (!$groupField) {
            return [
                'labels' => $results->pluck($xField)->toArray(),
                'datasets' => [
                    [
                        'label' => 'Value',
                        'data' => $results->pluck('value')->toArray(),
                    ],
                ],
            ];
        }

        // Multiple series (grouped)
        $labels = $results->pluck($xField)->unique()->values()->toArray();
        $groups = $results->pluck($groupField)->unique()->values()->toArray();

        $datasets = [];
        foreach ($groups as $group) {
            $groupData = $results->where($groupField, $group);
            $data = [];

            foreach ($labels as $label) {
                $value = $groupData->firstWhere($xField, $label);
                $data[] = $value ? $value->value : 0;
            }

            $datasets[] = [
                'label' => $group,
                'data' => $data,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * Generate a human-readable label.
     */
    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Get charts by type.
     */
    public function getByType(string $type): Collection
    {
        return $this->all()->filter(fn($config) => ($config['type'] ?? 'bar') === $type);
    }
}
