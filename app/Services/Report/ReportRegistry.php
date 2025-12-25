<?php

declare(strict_types=1);

namespace App\Services\Report;

use App\Contracts\ReportRegistryContract;
use App\Models\ReportDefinition;
use App\Models\ReportSchedule;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Report Registry
 *
 * Manages report definitions, execution, and export.
 * Supports parameterized queries, aggregations, and multiple formats.
 *
 * @example Register a report
 * ```php
 * $registry->register('sales_by_region', [
 *     'title' => 'Sales by Region',
 *     'entity' => 'invoice',
 *     'query' => function($params) { ... },
 *     'parameters' => [
 *         'date_from' => ['type' => 'date', 'required' => true],
 *         'date_to' => ['type' => 'date', 'required' => true],
 *     ],
 *     'columns' => [...],
 * ]);
 * ```
 *
 * @example Execute a report
 * ```php
 * $data = $registry->execute('sales_by_region', [
 *     'date_from' => '2024-01-01',
 *     'date_to' => '2024-12-31',
 * ]);
 * ```
 */
class ReportRegistry implements ReportRegistryContract
{
    /**
     * Registered reports.
     *
     * @var array<string, array>
     */
    protected array $reports = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Default report configuration.
     */
    protected array $defaultConfig = [
        'title' => '',
        'description' => null,
        'entity' => null,
        'query' => null,
        'sql' => null,
        'parameters' => [],
        'columns' => [],
        'grouping' => [],
        'sorting' => [],
        'totals' => [],
        'charts' => [],
        'category' => 'general',
        'permissions' => [],
    ];

    public function register(string $name, array $config, ?string $pluginSlug = null): self
    {
        $this->reports[$name] = array_merge($this->defaultConfig, $config, [
            'name' => $name,
        ]);

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        // Persist to database (without callable query)
        $dbConfig = $config;
        unset($dbConfig['query']); // Can't store closures

        ReportDefinition::updateOrCreate(
            ['slug' => $name],
            [
                'name' => $config['title'] ?? $this->generateLabel($name),
                'description' => $config['description'] ?? null,
                'entity' => $config['entity'] ?? null,
                'sql' => $config['sql'] ?? null,
                'parameters' => $config['parameters'] ?? [],
                'columns' => $config['columns'] ?? [],
                'config' => $dbConfig,
                'category' => $config['category'] ?? 'general',
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
            ]
        );

        return $this;
    }

    public function unregister(string $name): bool
    {
        if (!isset($this->reports[$name])) {
            return false;
        }

        unset($this->reports[$name]);
        unset($this->pluginOwnership[$name]);

        ReportDefinition::where('slug', $name)->delete();

        return true;
    }

    public function get(string $name): ?array
    {
        if (isset($this->reports[$name])) {
            return $this->reports[$name];
        }

        $report = ReportDefinition::where('slug', $name)->first();
        if ($report) {
            return array_merge($this->defaultConfig, $report->config ?? [], [
                'name' => $name,
                'title' => $report->name,
                'entity' => $report->entity,
                'sql' => $report->sql,
                'parameters' => $report->parameters,
                'columns' => $report->columns,
            ]);
        }

        return null;
    }

    public function has(string $name): bool
    {
        return isset($this->reports[$name]) || ReportDefinition::where('slug', $name)->exists();
    }

    public function all(): Collection
    {
        $dbReports = ReportDefinition::all()->keyBy('slug')->map(fn($report) => array_merge(
            $this->defaultConfig,
            $report->config ?? [],
            [
                'name' => $report->slug,
                'title' => $report->name,
                'entity' => $report->entity,
                'parameters' => $report->parameters,
                'columns' => $report->columns,
            ]
        ));

        return collect($this->reports)->merge($dbReports);
    }

    public function execute(string $name, array $params = []): array
    {
        $config = $this->get($name);

        if (!$config) {
            throw new \InvalidArgumentException("Report not found: {$name}");
        }

        // Validate parameters
        $this->validateParams($config, $params);

        // Execute query
        $data = $this->executeQuery($config, $params);

        // Apply transformations
        $data = $this->applyTransformations($data, $config);

        // Calculate totals
        $totals = $this->calculateTotals($data, $config);

        // Fire hook
        do_action('report_executed', $name, $params);

        return [
            'title' => $config['title'],
            'parameters' => $params,
            'columns' => $config['columns'],
            'data' => $data,
            'totals' => $totals,
            'row_count' => count($data),
            'executed_at' => now()->toIso8601String(),
        ];
    }

    public function export(string $name, array $params = [], string $format = 'pdf'): string
    {
        $result = $this->execute($name, $params);

        $filename = Str::slug($name) . '_' . date('Y-m-d_His') . '.' . $format;
        $path = "reports/{$filename}";

        $content = match ($format) {
            'csv' => $this->exportCsv($result),
            'xlsx' => $this->exportXlsx($result),
            'pdf' => $this->exportPdf($result),
            'json' => json_encode($result, JSON_PRETTY_PRINT),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}"),
        };

        Storage::put($path, $content);

        // Fire hook
        do_action('report_exported', $name, $format, $path);

        return Storage::path($path);
    }

    public function schedule(string $name, array $schedule, array $recipients): array
    {
        $config = $this->get($name);

        if (!$config) {
            throw new \InvalidArgumentException("Report not found: {$name}");
        }

        $scheduleRecord = ReportSchedule::create([
            'id' => Str::uuid()->toString(),
            'report_slug' => $name,
            'schedule' => $schedule,
            'recipients' => $recipients,
            'parameters' => $schedule['parameters'] ?? [],
            'format' => $schedule['format'] ?? 'pdf',
            'is_active' => true,
            'next_run_at' => $this->calculateNextRun($schedule),
            'created_by' => auth()->id(),
        ]);

        return $scheduleRecord->toArray();
    }

    public function getParameters(string $name): array
    {
        $config = $this->get($name);

        return $config ? ($config['parameters'] ?? []) : [];
    }

    /**
     * Validate report parameters.
     */
    protected function validateParams(array $config, array $params): void
    {
        foreach ($config['parameters'] ?? [] as $paramName => $paramConfig) {
            if (($paramConfig['required'] ?? false) && !isset($params[$paramName])) {
                throw new \InvalidArgumentException("Required parameter missing: {$paramName}");
            }
        }
    }

    /**
     * Execute the report query.
     */
    protected function executeQuery(array $config, array $params): array
    {
        // Use callable query if provided
        if (isset($config['query']) && is_callable($config['query'])) {
            return call_user_func($config['query'], $params);
        }

        // Use SQL query
        if ($sql = $config['sql'] ?? null) {
            return DB::select($sql, $params);
        }

        // Use entity-based query
        if ($entity = $config['entity'] ?? null) {
            return $this->executeEntityQuery($entity, $config, $params);
        }

        throw new \RuntimeException("No query defined for report");
    }

    /**
     * Execute entity-based query.
     */
    protected function executeEntityQuery(string $entity, array $config, array $params): array
    {
        $table = Str::plural(Str::snake($entity));
        $query = DB::table($table);

        // Apply filters from parameters
        foreach ($params as $key => $value) {
            if ($value !== null) {
                $paramConfig = $config['parameters'][$key] ?? [];
                $operator = $paramConfig['operator'] ?? '=';
                $field = $paramConfig['field'] ?? $key;

                if ($operator === 'between' && is_array($value)) {
                    $query->whereBetween($field, $value);
                } elseif ($operator === 'like') {
                    $query->where($field, 'like', "%{$value}%");
                } else {
                    $query->where($field, $operator, $value);
                }
            }
        }

        // Apply grouping
        foreach ($config['grouping'] ?? [] as $groupField) {
            $query->groupBy($groupField);
        }

        // Apply sorting
        foreach ($config['sorting'] ?? [] as $sortField => $direction) {
            $query->orderBy($sortField, $direction);
        }

        // Select columns
        if (!empty($config['columns'])) {
            $selects = [];
            foreach ($config['columns'] as $column) {
                if (isset($column['aggregate'])) {
                    $selects[] = DB::raw("{$column['aggregate']}({$column['field']}) as {$column['name']}");
                } else {
                    $selects[] = $column['field'] . ' as ' . ($column['name'] ?? $column['field']);
                }
            }
            $query->select($selects);
        }

        return $query->get()->toArray();
    }

    /**
     * Apply transformations to data.
     */
    protected function applyTransformations(array $data, array $config): array
    {
        // Convert to array of arrays for consistency
        $data = array_map(fn($row) => (array) $row, $data);

        // Apply column transformations
        foreach ($config['columns'] ?? [] as $column) {
            if ($transform = $column['transform'] ?? null) {
                $data = array_map(function ($row) use ($column, $transform) {
                    $row[$column['name']] = $this->applyTransform($row[$column['name']] ?? null, $transform);
                    return $row;
                }, $data);
            }
        }

        return $data;
    }

    /**
     * Apply a single transformation.
     */
    protected function applyTransform(mixed $value, string $transform): mixed
    {
        return match ($transform) {
            'currency' => number_format((float) $value, 2),
            'percentage' => number_format((float) $value * 100, 1) . '%',
            'date' => $value ? date('Y-m-d', strtotime($value)) : null,
            'datetime' => $value ? date('Y-m-d H:i', strtotime($value)) : null,
            'uppercase' => strtoupper((string) $value),
            'lowercase' => strtolower((string) $value),
            default => $value,
        };
    }

    /**
     * Calculate totals for columns.
     */
    protected function calculateTotals(array $data, array $config): array
    {
        $totals = [];

        foreach ($config['totals'] ?? [] as $column => $aggregation) {
            $values = array_column($data, $column);
            $values = array_filter($values, fn($v) => is_numeric($v));

            $totals[$column] = match ($aggregation) {
                'sum' => array_sum($values),
                'avg' => count($values) > 0 ? array_sum($values) / count($values) : 0,
                'min' => !empty($values) ? min($values) : null,
                'max' => !empty($values) ? max($values) : null,
                'count' => count($values),
                default => null,
            };
        }

        return $totals;
    }

    /**
     * Export to CSV.
     */
    protected function exportCsv(array $result): string
    {
        $output = fopen('php://temp', 'r+');

        // Headers
        $headers = array_map(fn($col) => $col['label'] ?? $col['name'], $result['columns']);
        fputcsv($output, $headers);

        // Data
        foreach ($result['data'] as $row) {
            $values = [];
            foreach ($result['columns'] as $col) {
                $values[] = $row[$col['name']] ?? '';
            }
            fputcsv($output, $values);
        }

        rewind($output);
        $content = stream_get_contents($output);
        fclose($output);

        return $content;
    }

    /**
     * Export to XLSX (placeholder).
     */
    protected function exportXlsx(array $result): string
    {
        // Would use a library like PhpSpreadsheet
        throw new \RuntimeException("XLSX export requires PhpSpreadsheet");
    }

    /**
     * Export to PDF (placeholder).
     */
    protected function exportPdf(array $result): string
    {
        // Would use a library like DomPDF or Snappy
        throw new \RuntimeException("PDF export requires a PDF library");
    }

    /**
     * Calculate next run time for schedule.
     */
    protected function calculateNextRun(array $schedule): string
    {
        $frequency = $schedule['frequency'] ?? 'daily';

        return match ($frequency) {
            'hourly' => now()->addHour()->startOfHour(),
            'daily' => now()->addDay()->startOfDay()->addHours($schedule['hour'] ?? 8),
            'weekly' => now()->next($schedule['day'] ?? 'monday')->startOfDay()->addHours($schedule['hour'] ?? 8),
            'monthly' => now()->addMonth()->startOfMonth()->addDays(($schedule['day_of_month'] ?? 1) - 1)->addHours($schedule['hour'] ?? 8),
            default => now()->addDay(),
        };
    }

    /**
     * Generate a human-readable label.
     */
    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Get reports by category.
     */
    public function getByCategory(string $category): Collection
    {
        return $this->all()->filter(fn($config) => ($config['category'] ?? 'general') === $category);
    }
}
