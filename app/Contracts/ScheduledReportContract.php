<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for Scheduled Reports.
 *
 * Manages scheduled report execution and delivery.
 */
interface ScheduledReportContract
{
    /**
     * Create a scheduled report.
     *
     * @param string $reportName Report name
     * @param array $schedule Schedule configuration
     * @param array $options Delivery options
     * @return array Schedule record
     */
    public function create(string $reportName, array $schedule, array $options = []): array;

    /**
     * Update a scheduled report.
     *
     * @param string $id Schedule ID
     * @param array $data Update data
     * @return array Updated schedule
     */
    public function update(string $id, array $data): array;

    /**
     * Delete a scheduled report.
     *
     * @param string $id Schedule ID
     * @return bool
     */
    public function delete(string $id): bool;

    /**
     * Get a scheduled report.
     *
     * @param string $id Schedule ID
     * @return array|null
     */
    public function get(string $id): ?array;

    /**
     * Get all scheduled reports.
     *
     * @param string|null $reportName Filter by report
     * @return Collection
     */
    public function all(?string $reportName = null): Collection;

    /**
     * Get scheduled reports due for execution.
     *
     * @return Collection
     */
    public function getDue(): Collection;

    /**
     * Execute a scheduled report.
     *
     * @param string $id Schedule ID
     * @return array Execution result
     */
    public function execute(string $id): array;

    /**
     * Pause a scheduled report.
     *
     * @param string $id Schedule ID
     * @return bool
     */
    public function pause(string $id): bool;

    /**
     * Resume a paused scheduled report.
     *
     * @param string $id Schedule ID
     * @return bool
     */
    public function resume(string $id): bool;

    /**
     * Get execution history.
     *
     * @param string $id Schedule ID
     * @param int $limit Limit results
     * @return Collection
     */
    public function getHistory(string $id, int $limit = 10): Collection;
}
