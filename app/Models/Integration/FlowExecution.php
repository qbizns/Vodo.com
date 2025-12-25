<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Flow Execution Model
 *
 * Represents an execution of a flow.
 */
class FlowExecution extends Model
{
    use HasUuids;

    protected $table = 'integration_flow_executions';

    protected $fillable = [
        'id',
        'flow_id',
        'flow_version',
        'tenant_id',
        'trigger_data',
        'context',
        'output',
        'status',
        'error',
        'nodes_executed',
        'duration_ms',
        'started_at',
        'completed_at',
        'resume_at',
    ];

    protected $casts = [
        'trigger_data' => 'array',
        'context' => 'array',
        'output' => 'array',
        'error' => 'array',
        'nodes_executed' => 'integer',
        'duration_ms' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'resume_at' => 'datetime',
    ];

    public function flow()
    {
        return $this->belongsTo(Flow::class);
    }

    public function steps()
    {
        return $this->hasMany(FlowStepExecution::class, 'execution_id');
    }

    public function scopeRunning($query)
    {
        return $query->where('status', 'running');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
