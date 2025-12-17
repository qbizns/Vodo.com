<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowHistory;
use App\Services\PluginBus\PluginBus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

/**
 * Workflow Engine - State machine management for entity records.
 * 
 * Features:
 * - Declarative workflow definitions
 * - Transition conditions (guards)
 * - Automatic actions on transitions
 * - Complete audit trail
 * - Visual diagram generation
 * 
 * Example usage:
 * 
 * // Define a workflow
 * $workflow = $engine->defineWorkflow('invoice_workflow', 'invoice', [
 *     'states' => [
 *         'draft' => ['label' => 'Draft', 'color' => 'gray'],
 *         'sent' => ['label' => 'Sent', 'color' => 'blue'],
 *         'paid' => ['label' => 'Paid', 'color' => 'green', 'is_final' => true],
 *         'cancelled' => ['label' => 'Cancelled', 'color' => 'red', 'is_final' => true],
 *     ],
 *     'transitions' => [
 *         'send' => [
 *             'from' => 'draft',
 *             'to' => 'sent',
 *             'label' => 'Send Invoice',
 *             'conditions' => ['has_lines', 'has_customer'],
 *             'actions' => ['send_email', 'log_activity'],
 *         ],
 *         'pay' => [
 *             'from' => 'sent',
 *             'to' => 'paid',
 *             'label' => 'Mark as Paid',
 *             'actions' => ['create_payment', 'update_balance'],
 *         ],
 *     ],
 * ]);
 * 
 * // Apply workflow to a record
 * $instance = $engine->initializeWorkflow($invoice, 'invoice_workflow');
 * 
 * // Perform transition
 * $engine->transition($invoice, 'send');
 */
class WorkflowEngine
{
    /**
     * Registered condition handlers.
     * @var array<string, callable>
     */
    protected array $conditions = [];

    /**
     * Registered action handlers.
     * @var array<string, callable>
     */
    protected array $actions = [];

    /**
     * Plugin bus for cross-plugin actions.
     */
    protected PluginBus $bus;

    public function __construct(PluginBus $bus)
    {
        $this->bus = $bus;
        $this->registerBuiltInConditions();
        $this->registerBuiltInActions();
    }

    /**
     * Define a new workflow.
     */
    public function defineWorkflow(
        string $slug,
        string $entityName,
        array $definition,
        ?string $pluginSlug = null
    ): WorkflowDefinition {
        $this->validateWorkflowDefinition($definition);

        return WorkflowDefinition::updateOrCreate(
            ['slug' => $slug, 'plugin_slug' => $pluginSlug],
            [
                'name' => $definition['name'] ?? ucwords(str_replace('_', ' ', $slug)),
                'entity_name' => $entityName,
                'description' => $definition['description'] ?? null,
                'initial_state' => $definition['initial_state'] ?? array_key_first($definition['states']),
                'states' => $definition['states'],
                'transitions' => $definition['transitions'],
                'config' => $definition['config'] ?? [],
                'is_active' => true,
            ]
        );
    }

    /**
     * Initialize workflow for a record.
     */
    public function initializeWorkflow(Model $record, string $workflowSlug): WorkflowInstance
    {
        $workflow = WorkflowDefinition::where('slug', $workflowSlug)->active()->firstOrFail();

        $existing = WorkflowInstance::where('workflowable_type', get_class($record))
            ->where('workflowable_id', $record->getKey())
            ->where('workflow_id', $workflow->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        return WorkflowInstance::create([
            'workflow_id' => $workflow->id,
            'workflowable_type' => get_class($record),
            'workflowable_id' => $record->getKey(),
            'current_state' => $workflow->initial_state,
            'transitioned_at' => now(),
            'transitioned_by' => Auth::id(),
        ]);
    }

    /**
     * Get workflow instance for a record.
     */
    public function getWorkflowInstance(Model $record, ?string $workflowSlug = null): ?WorkflowInstance
    {
        $query = WorkflowInstance::where('workflowable_type', get_class($record))
            ->where('workflowable_id', $record->getKey());

        if ($workflowSlug) {
            $query->whereHas('workflow', fn($q) => $q->where('slug', $workflowSlug));
        }

        return $query->first();
    }

    /**
     * Get current state of a record.
     */
    public function getCurrentState(Model $record, ?string $workflowSlug = null): ?string
    {
        $instance = $this->getWorkflowInstance($record, $workflowSlug);
        return $instance?->current_state;
    }

    /**
     * Get available transitions for a record.
     */
    public function getAvailableTransitions(Model $record, ?string $workflowSlug = null): array
    {
        $instance = $this->getWorkflowInstance($record, $workflowSlug);
        if (!$instance) {
            return [];
        }

        $transitions = $instance->getAvailableTransitions();
        $available = [];

        foreach ($transitions as $transitionId => $transition) {
            $conditionResult = $this->evaluateConditions($record, $transition['conditions'] ?? []);
            $available[$transitionId] = [
                'id' => $transitionId,
                'label' => $transition['label'] ?? $transitionId,
                'to' => $transition['to'],
                'can_execute' => $conditionResult['passed'],
                'failed_conditions' => $conditionResult['failed'],
                'icon' => $transition['icon'] ?? null,
                'confirm' => $transition['confirm'] ?? null,
            ];
        }

        return $available;
    }

    /**
     * Check if a transition can be executed.
     */
    public function canTransition(Model $record, string $transitionId, ?string $workflowSlug = null): bool
    {
        $instance = $this->getWorkflowInstance($record, $workflowSlug);
        if (!$instance) {
            return false;
        }

        $workflow = $instance->workflow;
        if (!$workflow->canTransition($instance->current_state, $transitionId)) {
            return false;
        }

        $transition = $workflow->getTransition($transitionId);
        $conditions = $transition['conditions'] ?? [];

        return $this->evaluateConditions($record, $conditions)['passed'];
    }

    /**
     * Execute a transition.
     */
    public function transition(
        Model $record,
        string $transitionId,
        array $data = [],
        ?string $workflowSlug = null,
        string $triggerType = WorkflowHistory::TRIGGER_MANUAL
    ): WorkflowInstance {
        return DB::transaction(function () use ($record, $transitionId, $data, $workflowSlug, $triggerType) {
            $instance = $this->getWorkflowInstance($record, $workflowSlug);
            if (!$instance) {
                throw new WorkflowException("Record has no workflow instance");
            }

            $workflow = $instance->workflow;
            $transition = $workflow->getTransition($transitionId);

            if (!$transition) {
                throw new WorkflowException("Unknown transition: {$transitionId}");
            }

            // Check if transition is valid from current state
            if (!$workflow->canTransition($instance->current_state, $transitionId)) {
                throw new WorkflowException(
                    "Cannot execute '{$transitionId}' from state '{$instance->current_state}'"
                );
            }

            // Evaluate conditions
            $conditions = $transition['conditions'] ?? [];
            $conditionResult = $this->evaluateConditions($record, $conditions);

            if (!$conditionResult['passed']) {
                throw new WorkflowConditionException(
                    "Transition conditions not met: " . implode(', ', $conditionResult['failed']),
                    $conditionResult['failed']
                );
            }

            // Store previous state
            $fromState = $instance->current_state;
            $toState = $transition['to'];

            // Execute pre-transition actions
            $actionsExecuted = [];
            $preActions = $transition['pre_actions'] ?? [];
            foreach ($preActions as $action) {
                $this->executeAction($action, $record, $instance, $data);
                $actionsExecuted[] = ['action' => $action, 'phase' => 'pre'];
            }

            // Update state
            $instance->update([
                'current_state' => $toState,
                'previous_state' => $fromState,
                'transitioned_at' => now(),
                'transitioned_by' => Auth::id(),
                'data' => array_merge($instance->data ?? [], $data),
            ]);

            // Update record if it has a state field
            if (method_exists($record, 'getStateField')) {
                $record->update([$record->getStateField() => $toState]);
            } elseif (isset($record->state) || isset($record->status)) {
                $stateField = isset($record->state) ? 'state' : 'status';
                $record->update([$stateField => $toState]);
            }

            // Execute post-transition actions
            $postActions = $transition['actions'] ?? $transition['post_actions'] ?? [];
            foreach ($postActions as $action) {
                $this->executeAction($action, $record, $instance, $data);
                $actionsExecuted[] = ['action' => $action, 'phase' => 'post'];
            }

            // Record history
            WorkflowHistory::create([
                'instance_id' => $instance->id,
                'transition_id' => $transitionId,
                'from_state' => $fromState,
                'to_state' => $toState,
                'triggered_by' => Auth::id(),
                'trigger_type' => $triggerType,
                'condition_results' => $conditionResult,
                'actions_executed' => $actionsExecuted,
                'data_snapshot' => $record->toArray(),
                'notes' => $data['notes'] ?? null,
            ]);

            // Publish event
            $this->bus->publish("workflow.{$workflow->slug}.transitioned", [
                'workflow' => $workflow->slug,
                'transition' => $transitionId,
                'from_state' => $fromState,
                'to_state' => $toState,
                'record_type' => get_class($record),
                'record_id' => $record->getKey(),
            ]);

            Log::info("Workflow transition completed", [
                'workflow' => $workflow->slug,
                'transition' => $transitionId,
                'from' => $fromState,
                'to' => $toState,
                'record' => get_class($record) . ':' . $record->getKey(),
            ]);

            return $instance->fresh();
        });
    }

    /**
     * Register a condition handler.
     */
    public function registerCondition(string $name, callable $handler): void
    {
        $this->conditions[$name] = $handler;
    }

    /**
     * Register an action handler.
     */
    public function registerAction(string $name, callable $handler): void
    {
        $this->actions[$name] = $handler;
    }

    /**
     * Evaluate transition conditions.
     */
    protected function evaluateConditions(Model $record, array $conditions): array
    {
        $result = ['passed' => true, 'failed' => [], 'details' => []];

        foreach ($conditions as $condition) {
            // Parse condition (can be string or array with parameters)
            if (is_array($condition)) {
                $conditionName = $condition['name'] ?? $condition[0];
                $params = $condition['params'] ?? array_slice($condition, 1);
            } else {
                $conditionName = $condition;
                $params = [];
            }

            // Check if negated
            $negated = str_starts_with($conditionName, '!');
            if ($negated) {
                $conditionName = substr($conditionName, 1);
            }

            // Find handler
            if (!isset($this->conditions[$conditionName])) {
                // Try as a method on the record
                if (method_exists($record, $conditionName)) {
                    $passed = $record->$conditionName(...$params);
                } elseif (method_exists($record, 'can' . ucfirst($conditionName))) {
                    $methodName = 'can' . ucfirst($conditionName);
                    $passed = $record->$methodName(...$params);
                } else {
                    Log::warning("Unknown workflow condition: {$conditionName}");
                    continue;
                }
            } else {
                $passed = call_user_func($this->conditions[$conditionName], $record, ...$params);
            }

            // Apply negation
            if ($negated) {
                $passed = !$passed;
            }

            $result['details'][$condition] = $passed;

            if (!$passed) {
                $result['passed'] = false;
                $result['failed'][] = $negated ? "!{$conditionName}" : $conditionName;
            }
        }

        return $result;
    }

    /**
     * Execute a transition action.
     */
    protected function executeAction(
        string|array $action,
        Model $record,
        WorkflowInstance $instance,
        array $data
    ): mixed {
        // Parse action
        if (is_array($action)) {
            $actionName = $action['name'] ?? $action[0];
            $params = $action['params'] ?? array_slice($action, 1);
        } else {
            $actionName = $action;
            $params = [];
        }

        // Check if it's a plugin bus service call
        if (str_contains($actionName, '.') && $this->bus->hasService($actionName)) {
            return $this->bus->call($actionName, array_merge([
                'record' => $record,
                'instance' => $instance,
                'data' => $data,
            ], $params));
        }

        // Check registered actions
        if (isset($this->actions[$actionName])) {
            return call_user_func($this->actions[$actionName], $record, $instance, $data, ...$params);
        }

        // Try as a method on the record
        if (method_exists($record, $actionName)) {
            return $record->$actionName($data, ...$params);
        }

        Log::warning("Unknown workflow action: {$actionName}");
        return null;
    }

    /**
     * Validate workflow definition.
     */
    protected function validateWorkflowDefinition(array $definition): void
    {
        if (empty($definition['states'])) {
            throw new \InvalidArgumentException('Workflow must have at least one state');
        }

        if (empty($definition['transitions'])) {
            throw new \InvalidArgumentException('Workflow must have at least one transition');
        }

        // Validate transitions reference valid states
        foreach ($definition['transitions'] as $transitionId => $transition) {
            $fromStates = (array)($transition['from'] ?? []);
            $toState = $transition['to'] ?? null;

            foreach ($fromStates as $state) {
                if (!isset($definition['states'][$state])) {
                    throw new \InvalidArgumentException(
                        "Transition '{$transitionId}' references unknown state: {$state}"
                    );
                }
            }

            if (!$toState || !isset($definition['states'][$toState])) {
                throw new \InvalidArgumentException(
                    "Transition '{$transitionId}' has invalid target state: {$toState}"
                );
            }
        }
    }

    /**
     * Register built-in conditions.
     */
    protected function registerBuiltInConditions(): void
    {
        // Check if record is not empty/null
        $this->registerCondition('exists', fn($record) => $record->exists);

        // Check if a field has a value
        $this->registerCondition('has_field', fn($record, $field) => !empty($record->$field));

        // Check if a field equals a value
        $this->registerCondition('field_equals', fn($record, $field, $value) => $record->$field === $value);

        // Check if a relationship has items
        $this->registerCondition('has_relation', fn($record, $relation) => $record->$relation()->exists());

        // Check if relation count meets minimum
        $this->registerCondition('relation_count_min', fn($record, $relation, $min) => 
            $record->$relation()->count() >= $min
        );

        // Check user permission
        $this->registerCondition('user_can', fn($record, $permission) => 
            Auth::check() && Auth::user()->can($permission, $record)
        );

        // Check if record was created within timeframe
        $this->registerCondition('created_within', fn($record, $hours) => 
            $record->created_at->diffInHours(now()) <= $hours
        );
    }

    /**
     * Register built-in actions.
     */
    protected function registerBuiltInActions(): void
    {
        // Log activity
        $this->registerAction('log_activity', function ($record, $instance, $data) {
            Log::info("Workflow activity", [
                'record' => get_class($record) . ':' . $record->getKey(),
                'state' => $instance->current_state,
                'data' => $data,
            ]);
        });

        // Update record field
        $this->registerAction('update_field', function ($record, $instance, $data, $field, $value) {
            $record->update([$field => $value]);
        });

        // Update timestamp
        $this->registerAction('touch_timestamp', function ($record, $instance, $data, $field) {
            $record->update([$field => now()]);
        });

        // Dispatch event
        $this->registerAction('dispatch_event', function ($record, $instance, $data, $eventClass) {
            event(new $eventClass($record, $instance));
        });
    }

    /**
     * Get workflow history for a record.
     */
    public function getHistory(Model $record, ?string $workflowSlug = null): \Illuminate\Support\Collection
    {
        $instance = $this->getWorkflowInstance($record, $workflowSlug);
        if (!$instance) {
            return collect();
        }

        return $instance->history;
    }

    /**
     * Generate visual diagram for a workflow.
     */
    public function generateDiagram(string $workflowSlug): string
    {
        $workflow = WorkflowDefinition::where('slug', $workflowSlug)->firstOrFail();
        return $workflow->toMermaidDiagram();
    }
}

/**
 * Base workflow exception.
 */
class WorkflowException extends \Exception {}

/**
 * Exception for failed conditions.
 */
class WorkflowConditionException extends WorkflowException
{
    protected array $failedConditions;

    public function __construct(string $message, array $failedConditions)
    {
        parent::__construct($message);
        $this->failedConditions = $failedConditions;
    }

    public function getFailedConditions(): array
    {
        return $this->failedConditions;
    }
}
