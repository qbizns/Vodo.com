<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Report Registry.
 *
 * Manages report definitions, execution, and scheduling.
 */
interface ReportRegistryContract
{
    /**
     * Register a report.
     *
     * @param string $name Report name
     * @param array $config Report configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function register(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Unregister a report.
     *
     * @param string $name Report name
     * @return bool
     */
    public function unregister(string $name): bool;

    /**
     * Get a report configuration.
     *
     * @param string $name Report name
     * @return array|null
     */
    public function get(string $name): ?array;

    /**
     * Check if a report exists.
     *
     * @param string $name Report name
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Get all reports.
     *
     * @return Collection
     */
    public function all(): Collection;

    /**
     * Execute a report with parameters.
     *
     * @param string $name Report name
     * @param array $params Report parameters
     * @return array Report data
     */
    public function execute(string $name, array $params = []): array;

    /**
     * Export a report.
     *
     * @param string $name Report name
     * @param array $params Report parameters
     * @param string $format Export format (pdf, xlsx, csv)
     * @return string File path
     */
    public function export(string $name, array $params = [], string $format = 'pdf'): string;

    /**
     * Schedule a report for recurring execution.
     *
     * @param string $name Report name
     * @param array $schedule Schedule configuration
     * @param array $recipients Email recipients
     * @return array Schedule record
     */
    public function schedule(string $name, array $schedule, array $recipients): array;

    /**
     * Get report parameter schema.
     *
     * @param string $name Report name
     * @return array
     */
    public function getParameters(string $name): array;
}
