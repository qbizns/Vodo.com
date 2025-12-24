<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Contracts\WorkflowContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Abstract base class for workflows.
 *
 * Provides state machine functionality with states, transitions,
 * guards, and automated actions.
 */
abstract class AbstractWorkflow implements WorkflowContract
{
    /**
     * Workflow name.
     */
    protected string $name;

    /**
     * Human-readable label.
     */
    protected string $label;

    /**
     * Target entity name.
     */
    protected string $entity;

    /**
     * State field name on the model.
     */
    protected string $stateField = 'state';

    /**
     * State definitions.
     *
     * @var array<string, array>
     */
    protected array $states = [];

    /**
     * Transition definitions.
     *
     * @var array<string, array>
     */
    protected array $transitions = [];

    /**
     * Initial state.
     */
    protected string $initialState = 'draft';

    /**
     * Final states.
     *
     * @var array<string>
     */
    protected array $finalStates = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getEntity(): string
    {
        return $this->entity;
    }

    public function getStateField(): string
    {
        return $this->stateField;
    }

    public function getStates(): array
    {
        return $this->states;
    }

    public function getState(string $state): ?array
    {
        return $this->states[$state] ?? null;
    }

    public function getTransitions(): array
    {
        return $this->transitions;
    }

    public function getAvailableTransitions(string $fromState, ?Model $model = null): array
    {
        $available = [];

        foreach ($this->transitions as $name => $transition) {
            $from = $transition['from'] ?? [];
            if (is_string($from)) {
                $from = [$from];
            }

            if (in_array($fromState, $from, true) || in_array('*', $from, true)) {
                if ($model === null || $this->checkGuards($name, $model)) {
                    $available[$name] = $transition;
                }
            }
        }

        return $available;
    }

    public function canTransition(string $transition, Model $model): bool
    {
        if (!isset($this->transitions[$transition])) {
            return false;
        }

        $currentState = $model->{$this->stateField};
        $from = $this->transitions[$transition]['from'] ?? [];

        if (is_string($from)) {
            $from = [$from];
        }

        if (!in_array($currentState, $from, true) && !in_array('*', $from, true)) {
            return false;
        }

        return $this->checkGuards($transition, $model);
    }

    /**
     * Check transition guards.
     *
     * @param string $transition Transition name
     * @param Model $model Model instance
     * @return bool
     */
    protected function checkGuards(string $transition, Model $model): bool
    {
        $config = $this->transitions[$transition] ?? [];

        // Check permission guard
        if (isset($config['permission'])) {
            if (!Gate::allows($config['permission'])) {
                return false;
            }
        }

        // Check custom guard
        if (isset($config['guard']) && is_callable($config['guard'])) {
            if (!call_user_func($config['guard'], $model)) {
                return false;
            }
        }

        // Check guard method on workflow
        $guardMethod = 'guard' . ucfirst($transition);
        if (method_exists($this, $guardMethod)) {
            if (!$this->$guardMethod($model)) {
                return false;
            }
        }

        return true;
    }

    public function apply(string $transition, Model $model, array $context = []): bool
    {
        if (!$this->canTransition($transition, $model)) {
            return false;
        }

        $config = $this->transitions[$transition] ?? [];
        $fromState = $model->{$this->stateField};
        $toState = $config['to'] ?? null;

        if (!$toState) {
            return false;
        }

        return DB::transaction(function () use ($transition, $model, $context, $config, $fromState, $toState) {
            // Execute before callback
            if (isset($config['before']) && is_callable($config['before'])) {
                call_user_func($config['before'], $model, $context);
            }

            // Execute before method on workflow
            $beforeMethod = 'before' . ucfirst($transition);
            if (method_exists($this, $beforeMethod)) {
                $this->$beforeMethod($model, $context);
            }

            // Update state
            $model->{$this->stateField} = $toState;
            $model->save();

            // Log transition
            $this->logTransition($model, $transition, $fromState, $toState, $context);

            // Execute after callback
            if (isset($config['after']) && is_callable($config['after'])) {
                call_user_func($config['after'], $model, $context);
            }

            // Execute after method on workflow
            $afterMethod = 'after' . ucfirst($transition);
            if (method_exists($this, $afterMethod)) {
                $this->$afterMethod($model, $context);
            }

            // Fire hook
            do_action('workflow_transition', $this->name, $transition, $model, $fromState, $toState);

            return true;
        });
    }

    /**
     * Log a workflow transition.
     *
     * @param Model $model Model instance
     * @param string $transition Transition name
     * @param string $fromState From state
     * @param string $toState To state
     * @param array $context Context
     */
    protected function logTransition(Model $model, string $transition, string $fromState, string $toState, array $context): void
    {
        // Can be overridden to log to workflow_history table
        do_action('workflow_transition_logged', [
            'workflow' => $this->name,
            'transition' => $transition,
            'model_type' => get_class($model),
            'model_id' => $model->getKey(),
            'from_state' => $fromState,
            'to_state' => $toState,
            'context' => $context,
            'user_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    public function getInitialState(): string
    {
        return $this->initialState;
    }

    public function getFinalStates(): array
    {
        return $this->finalStates;
    }

    public function isFinal(string $state): bool
    {
        return in_array($state, $this->finalStates, true);
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'entity' => $this->getEntity(),
            'state_field' => $this->getStateField(),
            'initial_state' => $this->getInitialState(),
            'final_states' => $this->getFinalStates(),
            'states' => $this->getStates(),
            'transitions' => array_map(fn($t) => [
                'from' => $t['from'] ?? [],
                'to' => $t['to'] ?? null,
                'label' => $t['label'] ?? null,
            ], $this->getTransitions()),
        ];
    }
}
