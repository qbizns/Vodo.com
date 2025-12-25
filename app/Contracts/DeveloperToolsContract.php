<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Developer Tools.
 *
 * Provides debugging, profiling, and development utilities.
 */
interface DeveloperToolsContract
{
    /**
     * Log a debug message.
     *
     * @param string $channel Debug channel
     * @param string $message Message
     * @param array $context Context data
     * @return void
     */
    public function log(string $channel, string $message, array $context = []): void;

    /**
     * Start a profiling timer.
     *
     * @param string $name Timer name
     * @return void
     */
    public function startTimer(string $name): void;

    /**
     * Stop a profiling timer.
     *
     * @param string $name Timer name
     * @return float Elapsed time in milliseconds
     */
    public function stopTimer(string $name): float;

    /**
     * Get profiling data.
     *
     * @return array
     */
    public function getProfilingData(): array;

    /**
     * Trace a query.
     *
     * @param string $sql SQL query
     * @param array $bindings Query bindings
     * @param float $time Execution time
     * @return void
     */
    public function traceQuery(string $sql, array $bindings, float $time): void;

    /**
     * Get query log.
     *
     * @return Collection
     */
    public function getQueryLog(): Collection;

    /**
     * Dump variable for debugging.
     *
     * @param mixed $value Value to dump
     * @param string|null $label Optional label
     * @return void
     */
    public function dump(mixed $value, ?string $label = null): void;

    /**
     * Get system information.
     *
     * @return array
     */
    public function getSystemInfo(): array;

    /**
     * Get registered services.
     *
     * @return Collection
     */
    public function getRegisteredServices(): Collection;

    /**
     * Get plugin information.
     *
     * @return Collection
     */
    public function getPluginInfo(): Collection;

    /**
     * Check configuration validity.
     *
     * @return array Validation results
     */
    public function validateConfiguration(): array;

    /**
     * Generate diagnostic report.
     *
     * @return array
     */
    public function generateDiagnosticReport(): array;

    /**
     * Clear caches.
     *
     * @param array $types Cache types to clear
     * @return array Results
     */
    public function clearCaches(array $types = []): array;
}
