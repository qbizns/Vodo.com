<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

use Illuminate\Support\Collection;

/**
 * Contract for Execution Engine.
 *
 * Handles the actual execution of flows, including:
 * - Job queuing
 * - Retry logic
 * - Rate limiting
 * - Logging
 * - Error handling
 */
interface ExecutionEngineContract
{
    // =========================================================================
    // EXECUTION
    // =========================================================================

    /**
     * Execute a flow.
     *
     * @param string $flowId Flow ID
     * @param array $triggerData Trigger data
     * @param array $options Execution options
     * @return string Execution ID
     */
    public function execute(string $flowId, array $triggerData, array $options = []): string;

    /**
     * Execute a single action.
     *
     * @param string $connectionId Connection ID
     * @param string $actionName Action name
     * @param array $input Action input
     * @param array $options Execution options
     * @return array Action output
     */
    public function executeAction(
        string $connectionId,
        string $actionName,
        array $input,
        array $options = []
    ): array;

    /**
     * Execute a node within a flow.
     *
     * @param string $executionId Execution ID
     * @param string $nodeId Node ID
     * @param array $input Node input
     * @return array Node output
     */
    public function executeNode(string $executionId, string $nodeId, array $input): array;

    /**
     * Resume a paused execution.
     *
     * @param string $executionId Execution ID
     * @param array $resumeData Data to resume with
     * @return void
     */
    public function resume(string $executionId, array $resumeData = []): void;

    /**
     * Cancel a running execution.
     *
     * @param string $executionId Execution ID
     * @param string $reason Cancellation reason
     * @return bool
     */
    public function cancel(string $executionId, string $reason = ''): bool;

    // =========================================================================
    // RETRY
    // =========================================================================

    /**
     * Retry a failed execution.
     *
     * @param string $executionId Execution ID
     * @param string|null $fromNodeId Start from specific node
     * @return string New execution ID
     */
    public function retry(string $executionId, ?string $fromNodeId = null): string;

    /**
     * Retry a failed node.
     *
     * @param string $executionId Execution ID
     * @param string $nodeId Node ID
     * @return array Node output
     */
    public function retryNode(string $executionId, string $nodeId): array;

    // =========================================================================
    // STATUS
    // =========================================================================

    /**
     * Get execution status.
     *
     * @param string $executionId Execution ID
     * @return array Execution status and data
     */
    public function getStatus(string $executionId): array;

    /**
     * Get execution progress.
     *
     * @param string $executionId Execution ID
     * @return array ['completed' => int, 'total' => int, 'current_node' => string]
     */
    public function getProgress(string $executionId): array;

    /**
     * Get execution log.
     *
     * @param string $executionId Execution ID
     * @return Collection
     */
    public function getLog(string $executionId): Collection;

    /**
     * Get node execution details.
     *
     * @param string $executionId Execution ID
     * @param string $nodeId Node ID
     * @return array
     */
    public function getNodeExecution(string $executionId, string $nodeId): array;

    // =========================================================================
    // HISTORY
    // =========================================================================

    /**
     * Get executions for a flow.
     *
     * @param string $flowId Flow ID
     * @param array $filters Filters
     * @param int $limit Limit
     * @return Collection
     */
    public function getExecutions(string $flowId, array $filters = [], int $limit = 50): Collection;

    /**
     * Get execution statistics.
     *
     * @param string|null $flowId Flow ID (null for all)
     * @param array $dateRange Date range
     * @return array
     */
    public function getStatistics(?string $flowId = null, array $dateRange = []): array;

    // =========================================================================
    // RATE LIMITING
    // =========================================================================

    /**
     * Check if action can be executed (rate limit).
     *
     * @param string $connectionId Connection ID
     * @param string $actionName Action name
     * @return bool
     */
    public function canExecute(string $connectionId, string $actionName): bool;

    /**
     * Get remaining rate limit quota.
     *
     * @param string $connectionId Connection ID
     * @return array ['remaining' => int, 'reset_at' => Carbon]
     */
    public function getRateLimitStatus(string $connectionId): array;

    // =========================================================================
    // CLEANUP
    // =========================================================================

    /**
     * Clean up old executions.
     *
     * @param int $daysToKeep Days to keep
     * @return int Number of deleted executions
     */
    public function cleanup(int $daysToKeep = 30): int;
}
