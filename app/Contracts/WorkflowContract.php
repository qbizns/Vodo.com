<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Contract for Workflow implementations.
 *
 * Workflows define state machines with states, transitions,
 * and automated actions.
 */
interface WorkflowContract
{
    /**
     * Get the workflow's unique identifier.
     */
    public function getName(): string;

    /**
     * Get the human-readable label.
     */
    public function getLabel(): string;

    /**
     * Get the entity this workflow applies to.
     */
    public function getEntity(): string;

    /**
     * Get the state field name on the entity.
     */
    public function getStateField(): string;

    /**
     * Get all defined states.
     *
     * @return array<string, array>
     */
    public function getStates(): array;

    /**
     * Get a specific state configuration.
     *
     * @param string $state State name
     * @return array|null
     */
    public function getState(string $state): ?array;

    /**
     * Get all defined transitions.
     *
     * @return array<string, array>
     */
    public function getTransitions(): array;

    /**
     * Get available transitions from a state.
     *
     * @param string $fromState Current state
     * @param Model|null $model The model instance (for guards)
     * @return array<string, array>
     */
    public function getAvailableTransitions(string $fromState, ?Model $model = null): array;

    /**
     * Check if a transition is allowed.
     *
     * @param string $transition Transition name
     * @param Model $model The model instance
     * @return bool
     */
    public function canTransition(string $transition, Model $model): bool;

    /**
     * Apply a transition to a model.
     *
     * @param string $transition Transition name
     * @param Model $model The model instance
     * @param array $context Additional context
     * @return bool
     */
    public function apply(string $transition, Model $model, array $context = []): bool;

    /**
     * Get the initial state.
     */
    public function getInitialState(): string;

    /**
     * Get final (end) states.
     *
     * @return array<string>
     */
    public function getFinalStates(): array;

    /**
     * Check if a state is final.
     *
     * @param string $state State name
     */
    public function isFinal(string $state): bool;
}
