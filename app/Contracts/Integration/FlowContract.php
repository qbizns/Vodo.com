<?php

declare(strict_types=1);

namespace App\Contracts\Integration;

use Illuminate\Support\Collection;

/**
 * Contract for Automation Flows.
 *
 * A Flow is an automation that connects triggers to actions.
 * Like n8n workflows or Make.com scenarios.
 *
 * Flow Structure:
 * - One or more triggers (start points)
 * - One or more actions (operations)
 * - Conditions (branching logic)
 * - Loops (iterate over data)
 * - Data transformations
 *
 * @example Simple Flow
 * ```
 * [Telegram: New Message]
 *         ↓
 *     [Condition: Contains "help"]
 *        ↓ Yes           ↓ No
 * [Send FAQ Reply]   [Forward to Support]
 * ```
 */
interface FlowContract
{
    // =========================================================================
    // IDENTITY
    // =========================================================================

    /**
     * Get flow ID.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Get flow name.
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Get flow description.
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Get flow owner user ID.
     *
     * @return int
     */
    public function getUserId(): int;

    // =========================================================================
    // STATUS
    // =========================================================================

    /**
     * Is flow active?
     *
     * @return bool
     */
    public function isActive(): bool;

    /**
     * Activate the flow.
     *
     * @return void
     */
    public function activate(): void;

    /**
     * Deactivate the flow.
     *
     * @return void
     */
    public function deactivate(): void;

    /**
     * Get flow status.
     *
     * @return string 'active', 'inactive', 'error', 'draft'
     */
    public function getStatus(): string;

    // =========================================================================
    // STRUCTURE
    // =========================================================================

    /**
     * Get flow trigger configuration.
     *
     * @return array
     */
    public function getTrigger(): array;

    /**
     * Set flow trigger.
     *
     * @param array $trigger Trigger configuration
     * @return void
     */
    public function setTrigger(array $trigger): void;

    /**
     * Get all nodes in the flow.
     *
     * @return Collection
     */
    public function getNodes(): Collection;

    /**
     * Get a specific node.
     *
     * @param string $nodeId Node ID
     * @return array|null
     */
    public function getNode(string $nodeId): ?array;

    /**
     * Add a node to the flow.
     *
     * @param array $node Node configuration
     * @return string Node ID
     */
    public function addNode(array $node): string;

    /**
     * Update a node.
     *
     * @param string $nodeId Node ID
     * @param array $node Node configuration
     * @return void
     */
    public function updateNode(string $nodeId, array $node): void;

    /**
     * Remove a node.
     *
     * @param string $nodeId Node ID
     * @return void
     */
    public function removeNode(string $nodeId): void;

    /**
     * Get connections between nodes.
     *
     * @return array
     */
    public function getConnections(): array;

    /**
     * Connect two nodes.
     *
     * @param string $fromNodeId Source node
     * @param string $toNodeId Target node
     * @param string $outputPort Source port (default 'main')
     * @param string $inputPort Target port (default 'main')
     * @return void
     */
    public function connect(
        string $fromNodeId,
        string $toNodeId,
        string $outputPort = 'main',
        string $inputPort = 'main'
    ): void;

    /**
     * Disconnect two nodes.
     *
     * @param string $fromNodeId Source node
     * @param string $toNodeId Target node
     * @return void
     */
    public function disconnect(string $fromNodeId, string $toNodeId): void;

    // =========================================================================
    // VALIDATION
    // =========================================================================

    /**
     * Validate the flow configuration.
     *
     * @return array Validation errors (empty if valid)
     */
    public function validate(): array;

    /**
     * Check if all required connections are configured.
     *
     * @return bool
     */
    public function hasValidConnections(): bool;

    // =========================================================================
    // EXECUTION
    // =========================================================================

    /**
     * Execute the flow with trigger data.
     *
     * @param array $triggerData Data from trigger
     * @return string Execution ID
     */
    public function execute(array $triggerData): string;

    /**
     * Test the flow with sample data.
     *
     * @param array $sampleData Sample trigger data
     * @return array Test results
     */
    public function test(array $sampleData): array;

    // =========================================================================
    // HISTORY
    // =========================================================================

    /**
     * Get execution history.
     *
     * @param int $limit Limit results
     * @return Collection
     */
    public function getExecutions(int $limit = 50): Collection;

    /**
     * Get last execution.
     *
     * @return array|null
     */
    public function getLastExecution(): ?array;

    // =========================================================================
    // SERIALIZATION
    // =========================================================================

    /**
     * Export flow to array.
     *
     * @return array
     */
    public function toArray(): array;

    /**
     * Import flow from array.
     *
     * @param array $data Flow data
     * @return void
     */
    public function fromArray(array $data): void;

    /**
     * Clone the flow.
     *
     * @param string $newName New flow name
     * @return FlowContract
     */
    public function clone(string $newName): FlowContract;
}
