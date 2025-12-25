<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Flow Step Execution Model
 *
 * Represents the execution of a single node in a flow.
 */
class FlowStepExecution extends Model
{
    use HasUuids;

    protected $table = 'integration_flow_step_executions';

    protected $fillable = [
        'id',
        'execution_id',
        'node_id',
        'node_type',
        'node_name',
        'input',
        'output',
        'status',
        'error',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'error' => 'array',
        'duration_ms' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function execution()
    {
        return $this->belongsTo(FlowExecution::class, 'execution_id');
    }
}
