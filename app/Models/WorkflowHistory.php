<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Workflow History - Audit trail of workflow transitions.
 */
class WorkflowHistory extends Model
{
    protected $table = 'workflow_history';

    protected $fillable = [
        'instance_id',
        'transition_id',
        'from_state',
        'to_state',
        'triggered_by',
        'trigger_type',
        'condition_results',
        'actions_executed',
        'data_snapshot',
        'notes',
    ];

    protected $casts = [
        'condition_results' => 'array',
        'actions_executed' => 'array',
        'data_snapshot' => 'array',
    ];

    /**
     * Trigger types.
     */
    public const TRIGGER_MANUAL = 'manual';
    public const TRIGGER_AUTO = 'automatic';
    public const TRIGGER_SCHEDULED = 'scheduled';
    public const TRIGGER_API = 'api';

    /**
     * Get the workflow instance.
     */
    public function instance(): BelongsTo
    {
        return $this->belongsTo(WorkflowInstance::class, 'instance_id');
    }

    /**
     * Get the user who triggered the transition.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    /**
     * Scope for manual transitions.
     */
    public function scopeManual($query)
    {
        return $query->where('trigger_type', self::TRIGGER_MANUAL);
    }

    /**
     * Scope for automatic transitions.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('trigger_type', self::TRIGGER_AUTO);
    }
}
