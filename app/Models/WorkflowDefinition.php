<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Workflow Definition - Defines a state machine for entity records.
 * 
 * Example workflow for Invoice:
 * States: draft, sent, paid, cancelled
 * Transitions: 
 *   - send: draft → sent (condition: has_lines)
 *   - pay: sent → paid (action: create_payment)
 *   - cancel: [draft, sent] → cancelled
 */
class WorkflowDefinition extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'entity_name',
        'description',
        'initial_state',
        'states',
        'transitions',
        'config',
        'plugin_slug',
        'is_active',
    ];

    protected $casts = [
        'states' => 'array',
        'transitions' => 'array',
        'config' => 'array',
        'is_active' => 'boolean',
    ];

    /**
     * Get workflow instances (records using this workflow).
     */
    public function instances(): HasMany
    {
        return $this->hasMany(WorkflowInstance::class, 'workflow_id');
    }

    /**
     * Get a state definition.
     */
    public function getState(string $stateId): ?array
    {
        return $this->states[$stateId] ?? null;
    }

    /**
     * Get all available transitions from a state.
     */
    public function getTransitionsFrom(string $stateId): array
    {
        return array_filter(
            $this->transitions ?? [],
            fn($t) => in_array($stateId, (array)($t['from'] ?? []))
        );
    }

    /**
     * Get a specific transition definition.
     */
    public function getTransition(string $transitionId): ?array
    {
        return $this->transitions[$transitionId] ?? null;
    }

    /**
     * Check if a transition is valid from current state.
     */
    public function canTransition(string $fromState, string $transitionId): bool
    {
        $transition = $this->getTransition($transitionId);
        if (!$transition) {
            return false;
        }

        $fromStates = (array)($transition['from'] ?? []);
        return in_array($fromState, $fromStates);
    }

    /**
     * Get the target state for a transition.
     */
    public function getTransitionTarget(string $transitionId): ?string
    {
        return $this->transitions[$transitionId]['to'] ?? null;
    }

    /**
     * Scope for active workflows.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for entity workflows.
     */
    public function scopeForEntity($query, string $entityName)
    {
        return $query->where('entity_name', $entityName);
    }

    /**
     * Generate Mermaid diagram for visualization.
     */
    public function toMermaidDiagram(): string
    {
        $lines = ['stateDiagram-v2'];
        
        // Add initial state
        $lines[] = "    [*] --> {$this->initial_state}";

        // Add states with labels
        foreach ($this->states ?? [] as $stateId => $state) {
            $label = $state['label'] ?? $stateId;
            if ($state['is_final'] ?? false) {
                $lines[] = "    {$stateId} --> [*]";
            }
        }

        // Add transitions
        foreach ($this->transitions ?? [] as $transitionId => $transition) {
            $fromStates = (array)($transition['from'] ?? []);
            $toState = $transition['to'];
            $label = $transition['label'] ?? $transitionId;

            foreach ($fromStates as $fromState) {
                $lines[] = "    {$fromState} --> {$toState} : {$label}";
            }
        }

        return implode("\n", $lines);
    }
}
