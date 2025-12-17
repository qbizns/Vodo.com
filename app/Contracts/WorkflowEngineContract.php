<?php

declare(strict_types=1);

namespace App\Contracts;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for Workflow Engine implementations.
 */
interface WorkflowEngineContract
{
    /**
     * Define a new workflow.
     */
    public function defineWorkflow(
        string $slug,
        string $entityName,
        array $definition,
        ?string $pluginSlug = null
    ): WorkflowDefinition;

    /**
     * Initialize workflow for a record.
     */
    public function initializeWorkflow(Model $record, string $workflowSlug): WorkflowInstance;

    /**
     * Get workflow instance for a record.
     */
    public function getWorkflowInstance(Model $record, ?string $workflowSlug = null): ?WorkflowInstance;

    /**
     * Get current state of a record.
     */
    public function getCurrentState(Model $record, ?string $workflowSlug = null): ?string;

    /**
     * Get available transitions for a record.
     */
    public function getAvailableTransitions(Model $record, ?string $workflowSlug = null): array;

    /**
     * Check if a transition can be executed.
     */
    public function canTransition(Model $record, string $transitionId, ?string $workflowSlug = null): bool;

    /**
     * Execute a transition.
     */
    public function transition(
        Model $record,
        string $transitionId,
        array $data = [],
        ?string $workflowSlug = null,
        string $triggerType = 'manual'
    ): WorkflowInstance;

    /**
     * Register a condition handler.
     */
    public function registerCondition(string $name, callable $handler): void;

    /**
     * Register an action handler.
     */
    public function registerAction(string $name, callable $handler): void;

    /**
     * Get workflow history for a record.
     */
    public function getHistory(Model $record, ?string $workflowSlug = null): Collection;

    /**
     * Generate visual diagram for a workflow.
     */
    public function generateDiagram(string $workflowSlug): string;
}
