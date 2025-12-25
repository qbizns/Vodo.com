<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Chart Engine.
 *
 * Handles chart definitions, data aggregation, and rendering.
 */
interface ChartEngineContract
{
    /**
     * Register a chart type.
     *
     * @param string $type Chart type name
     * @param array $config Type configuration
     * @return self
     */
    public function registerType(string $type, array $config): self;

    /**
     * Get available chart types.
     *
     * @return Collection
     */
    public function getTypes(): Collection;

    /**
     * Register a chart definition.
     *
     * @param string $name Chart name
     * @param array $config Chart configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Get a chart configuration.
     *
     * @param string $name Chart name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Get all charts.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Render chart data.
     *
     * @param string $name Chart name
     * @param array $params Parameters
     * @return array Chart data for frontend
     */
    public function render(string $name, array $params = []): array;

    /**
     * Create a chart from query builder.
     *
     * @param string $type Chart type
     * @param mixed $query Query builder
     * @param array $config Aggregation config
     * @return array Chart data
     */
    public function fromQuery(string $type, mixed $query, array $config): array;

    /**
     * Aggregate data for chart.
     *
     * @param string $entityName Entity name
     * @param array $config Aggregation config
     * @return array Aggregated data
     */
    public function aggregate(string $entityName, array $config): array;

    /**
     * Export chart as image.
     *
     * @param string $name Chart name
     * @param array $params Parameters
     * @param string $format Format (png, svg)
     * @return string Base64 encoded image
     */
    public function export(string $name, array $params = [], string $format = 'png'): string;
}
