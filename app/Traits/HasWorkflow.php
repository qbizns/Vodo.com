<?php

declare(strict_types=1);

namespace App\Traits;

use App\Models\WorkflowInstance;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Database\Eloquent\Relations\MorphOne;

/**
 * HasWorkflow - Trait for models that use workflow state machines.
 * 
 * Usage:
 * 
 * class Invoice extends Model
 * {
 *     use HasWorkflow;
 * 
 *     protected string $workflowSlug = 'invoice_workflow';
 * }
 * 
 * // Then use:
 * $invoice->getWorkflowState();        // Get current state
 * $invoice->canTransition('send');     // Check if transition allowed
 * $invoice->transition('send');        // Execute transition
 * $invoice->getAvailableTransitions(); // Get available transitions
 */
trait HasWorkflow
{
    /**
     * Boot the trait.
     */
    public static function bootHasWorkflow(): void
    {
        static::created(function ($model) {
            if ($model->shouldAutoInitializeWorkflow()) {
                $model->initializeWorkflow();
            }
        });
    }

    /**
     * Get the workflow instance relationship.
     */
    public function workflowInstance(): MorphOne
    {
        return $this->morphOne(WorkflowInstance::class, 'workflowable');
    }

    /**
     * Get the workflow engine.
     */
    protected function getWorkflowEngine(): WorkflowEngine
    {
        return app(WorkflowEngine::class);
    }

    /**
     * Get the workflow slug for this model.
     */
    public function getWorkflowSlug(): ?string
    {
        return $this->workflowSlug ?? null;
    }

    /**
     * Get the state field name.
     */
    public function getStateField(): string
    {
        return $this->stateField ?? 'state';
    }

    /**
     * Check if workflow should auto-initialize on create.
     */
    protected function shouldAutoInitializeWorkflow(): bool
    {
        return $this->autoInitializeWorkflow ?? true;
    }

    /**
     * Initialize workflow for this record.
     */
    public function initializeWorkflow(?string $workflowSlug = null): WorkflowInstance
    {
        $slug = $workflowSlug ?? $this->getWorkflowSlug();
        
        if (!$slug) {
            throw new \RuntimeException('No workflow slug defined for ' . static::class);
        }

        return $this->getWorkflowEngine()->initializeWorkflow($this, $slug);
    }

    /**
     * Get the current workflow state.
     */
    public function getWorkflowState(): ?string
    {
        return $this->getWorkflowEngine()->getCurrentState($this, $this->getWorkflowSlug());
    }

    /**
     * Get the workflow instance.
     */
    public function getWorkflow(): ?WorkflowInstance
    {
        return $this->getWorkflowEngine()->getWorkflowInstance($this, $this->getWorkflowSlug());
    }

    /**
     * Get available transitions from current state.
     */
    public function getAvailableTransitions(): array
    {
        return $this->getWorkflowEngine()->getAvailableTransitions($this, $this->getWorkflowSlug());
    }

    /**
     * Check if a transition can be executed.
     */
    public function canTransition(string $transitionId): bool
    {
        return $this->getWorkflowEngine()->canTransition($this, $transitionId, $this->getWorkflowSlug());
    }

    /**
     * Execute a workflow transition.
     */
    public function transition(string $transitionId, array $data = []): WorkflowInstance
    {
        return $this->getWorkflowEngine()->transition($this, $transitionId, $data, $this->getWorkflowSlug());
    }

    /**
     * Get workflow history.
     */
    public function getWorkflowHistory(): \Illuminate\Support\Collection
    {
        return $this->getWorkflowEngine()->getHistory($this, $this->getWorkflowSlug());
    }

    /**
     * Check if in a specific state.
     */
    public function isInState(string $state): bool
    {
        return $this->getWorkflowState() === $state;
    }

    /**
     * Check if in any of the given states.
     */
    public function isInAnyState(array $states): bool
    {
        return in_array($this->getWorkflowState(), $states);
    }

    /**
     * Check if in a final state.
     */
    public function isInFinalState(): bool
    {
        $instance = $this->getWorkflow();
        return $instance?->isInFinalState() ?? false;
    }

    /**
     * Scope for records in a specific state.
     */
    public function scopeInState($query, string $state)
    {
        return $query->whereHas('workflowInstance', function ($q) use ($state) {
            $q->where('current_state', $state);
        });
    }

    /**
     * Scope for records in any of the given states.
     */
    public function scopeInAnyState($query, array $states)
    {
        return $query->whereHas('workflowInstance', function ($q) use ($states) {
            $q->whereIn('current_state', $states);
        });
    }
}
