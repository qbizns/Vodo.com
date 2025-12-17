<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Workflow Instance - Tracks the workflow state of a specific record.
 */
class WorkflowInstance extends Model
{
    protected $fillable = [
        'workflow_id',
        'workflowable_type',
        'workflowable_id',
        'current_state',
        'previous_state',
        'data',
        'transitioned_at',
        'transitioned_by',
    ];

    protected $casts = [
        'data' => 'array',
        'transitioned_at' => 'datetime',
    ];

    /**
     * Get the workflow definition.
     */
    public function workflow(): BelongsTo
    {
        return $this->belongsTo(WorkflowDefinition::class, 'workflow_id');
    }

    /**
     * Get the record this workflow is for.
     */
    public function workflowable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who last transitioned.
     */
    public function transitioner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'transitioned_by');
    }

    /**
     * Get transition history.
     */
    public function history(): HasMany
    {
        return $this->hasMany(WorkflowHistory::class, 'instance_id')->orderBy('created_at', 'desc');
    }

    /**
     * Get current state info.
     */
    public function getCurrentStateInfo(): ?array
    {
        return $this->workflow->getState($this->current_state);
    }

    /**
     * Get available transitions from current state.
     */
    public function getAvailableTransitions(): array
    {
        return $this->workflow->getTransitionsFrom($this->current_state);
    }

    /**
     * Check if in a final state.
     */
    public function isInFinalState(): bool
    {
        $state = $this->getCurrentStateInfo();
        return $state['is_final'] ?? false;
    }
}
