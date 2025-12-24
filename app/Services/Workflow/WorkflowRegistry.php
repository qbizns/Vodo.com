<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Contracts\WorkflowContract;
use App\Contracts\WorkflowRegistryContract;
use App\Models\WorkflowHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Workflow Registry - Manages workflow definitions.
 *
 * Supports state machines with states, transitions, guards,
 * and automated actions.
 *
 * @example Register a workflow
 * ```php
 * $registry->register(new OrderWorkflow());
 * ```
 *
 * @example Register from array
 * ```php
 * $registry->registerFromArray('order', [
 *     'entity' => 'order',
 *     'states' => [
 *         'draft' => ['label' => 'Draft', 'color' => 'gray'],
 *         'pending' => ['label' => 'Pending', 'color' => 'yellow'],
 *         'confirmed' => ['label' => 'Confirmed', 'color' => 'green'],
 *     ],
 *     'transitions' => [
 *         'submit' => ['from' => 'draft', 'to' => 'pending'],
 *         'confirm' => ['from' => 'pending', 'to' => 'confirmed'],
 *     ],
 * ]);
 * ```
 */
class WorkflowRegistry implements WorkflowRegistryContract
{
    /**
     * Registered workflows.
     *
     * @var array<string, WorkflowContract>
     */
    protected array $workflows = [];

    /**
     * Entity to workflow mapping.
     *
     * @var array<string, string>
     */
    protected array $entityMapping = [];

    /**
     * Plugin ownership mapping.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    public function register(WorkflowContract $workflow, ?string $pluginSlug = null): self
    {
        $name = $workflow->getName();
        $this->workflows[$name] = $workflow;
        $this->entityMapping[$workflow->getEntity()] = $name;

        if ($pluginSlug) {
            $this->pluginOwnership[$name] = $pluginSlug;
        }

        return $this;
    }

    public function registerFromArray(string $name, array $config, ?string $pluginSlug = null): self
    {
        $workflow = new GenericWorkflow($name, $config);

        return $this->register($workflow, $pluginSlug);
    }

    public function unregister(string $name): bool
    {
        if (!isset($this->workflows[$name])) {
            return false;
        }

        $entity = $this->workflows[$name]->getEntity();
        unset($this->workflows[$name]);
        unset($this->entityMapping[$entity]);
        unset($this->pluginOwnership[$name]);

        return true;
    }

    public function get(string $name): ?WorkflowContract
    {
        return $this->workflows[$name] ?? null;
    }

    public function getForEntity(string $entityName): ?WorkflowContract
    {
        $workflowName = $this->entityMapping[$entityName] ?? null;

        if (!$workflowName) {
            return null;
        }

        return $this->get($workflowName);
    }

    public function has(string $name): bool
    {
        return isset($this->workflows[$name]);
    }

    public function all(): Collection
    {
        return collect($this->workflows);
    }

    public function getAvailableTransitions(Model $model): array
    {
        $entityName = $this->getEntityName($model);
        $workflow = $this->getForEntity($entityName);

        if (!$workflow) {
            return [];
        }

        $currentState = $model->{$workflow->getStateField()};

        return $workflow->getAvailableTransitions($currentState, $model);
    }

    public function apply(Model $model, string $transition, array $context = []): bool
    {
        $entityName = $this->getEntityName($model);
        $workflow = $this->getForEntity($entityName);

        if (!$workflow) {
            throw new \RuntimeException("No workflow found for entity: {$entityName}");
        }

        return $workflow->apply($transition, $model, $context);
    }

    public function getCurrentState(Model $model): ?string
    {
        $entityName = $this->getEntityName($model);
        $workflow = $this->getForEntity($entityName);

        if (!$workflow) {
            return null;
        }

        return $model->{$workflow->getStateField()};
    }

    public function getHistory(Model $model): Collection
    {
        // Try to get from workflow_history table if it exists
        try {
            return WorkflowHistory::where('model_type', get_class($model))
                ->where('model_id', $model->getKey())
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Exception) {
            return collect([]);
        }
    }

    /**
     * Get the entity name for a model.
     *
     * @param Model $model Model instance
     * @return string
     */
    protected function getEntityName(Model $model): string
    {
        // Check if model has getEntityName method
        if (method_exists($model, 'getEntityName')) {
            return $model->getEntityName();
        }

        // Fall back to table name
        return $model->getTable();
    }

    /**
     * Get workflows by plugin.
     *
     * @param string $pluginSlug Plugin slug
     * @return Collection
     */
    public function getByPlugin(string $pluginSlug): Collection
    {
        return $this->all()->filter(
            fn(WorkflowContract $workflow, string $name) => ($this->pluginOwnership[$name] ?? null) === $pluginSlug
        );
    }

    /**
     * Initialize the state for a new model.
     *
     * @param Model $model Model instance
     * @return bool
     */
    public function initializeState(Model $model): bool
    {
        $entityName = $this->getEntityName($model);
        $workflow = $this->getForEntity($entityName);

        if (!$workflow) {
            return false;
        }

        $stateField = $workflow->getStateField();

        if (empty($model->{$stateField})) {
            $model->{$stateField} = $workflow->getInitialState();
        }

        return true;
    }

    /**
     * Get state configuration for a model.
     *
     * @param Model $model Model instance
     * @return array|null
     */
    public function getStateConfig(Model $model): ?array
    {
        $entityName = $this->getEntityName($model);
        $workflow = $this->getForEntity($entityName);

        if (!$workflow) {
            return null;
        }

        $currentState = $model->{$workflow->getStateField()};

        return $workflow->getState($currentState);
    }

    /**
     * Check if a model is in a final state.
     *
     * @param Model $model Model instance
     * @return bool
     */
    public function isInFinalState(Model $model): bool
    {
        $entityName = $this->getEntityName($model);
        $workflow = $this->getForEntity($entityName);

        if (!$workflow) {
            return false;
        }

        $currentState = $model->{$workflow->getStateField()};

        return $workflow->isFinal($currentState);
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return $this->all()
            ->map(fn(WorkflowContract $workflow) => $workflow->toArray())
            ->toArray();
    }
}
