<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Contract for the Workflow Registry.
 *
 * Manages workflow definitions and execution.
 */
interface WorkflowRegistryContract
{
    /**
     * Register a workflow.
     *
     * @param WorkflowContract $workflow The workflow to register
     * @param string|null $pluginSlug Owner plugin slug
     * @return self
     */
    public function register(WorkflowContract $workflow, ?string $pluginSlug = null): self;

    /**
     * Register a workflow from array configuration.
     *
     * @param string $name Workflow name
     * @param array $config Workflow configuration
     * @param string|null $pluginSlug Owner plugin slug
     * @return self
     */
    public function registerFromArray(string $name, array $config, ?string $pluginSlug = null): self;

    /**
     * Unregister a workflow.
     *
     * @param string $name Workflow name
     * @return bool
     */
    public function unregister(string $name): bool;

    /**
     * Get a workflow by name.
     *
     * @param string $name Workflow name
     * @return WorkflowContract|null
     */
    public function get(string $name): ?WorkflowContract;

    /**
     * Get the workflow for an entity.
     *
     * @param string $entityName Entity name
     * @return WorkflowContract|null
     */
    public function getForEntity(string $entityName): ?WorkflowContract;

    /**
     * Check if a workflow exists.
     *
     * @param string $name Workflow name
     */
    public function has(string $name): bool;

    /**
     * Get all registered workflows.
     *
     * @return Collection<string, WorkflowContract>
     */
    public function all(): Collection;

    /**
     * Get available transitions for a model.
     *
     * @param Model $model The model instance
     * @return array<string, array>
     */
    public function getAvailableTransitions(Model $model): array;

    /**
     * Apply a transition to a model.
     *
     * @param Model $model The model instance
     * @param string $transition Transition name
     * @param array $context Additional context
     * @return bool
     */
    public function apply(Model $model, string $transition, array $context = []): bool;

    /**
     * Get the current state of a model.
     *
     * @param Model $model The model instance
     * @return string|null
     */
    public function getCurrentState(Model $model): ?string;

    /**
     * Get workflow history for a model.
     *
     * @param Model $model The model instance
     * @return Collection
     */
    public function getHistory(Model $model): Collection;
}
