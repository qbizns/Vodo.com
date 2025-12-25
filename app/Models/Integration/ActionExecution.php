<?php

declare(strict_types=1);

namespace App\Models\Integration;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

/**
 * Action Execution Model
 *
 * Represents an execution of an action.
 */
class ActionExecution extends Model
{
    use HasUuids;

    protected $table = 'integration_action_executions';

    protected $fillable = [
        'id',
        'connector_name',
        'action_name',
        'connection_id',
        'input',
        'output',
        'context',
        'status',
        'error',
        'duration_ms',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'context' => 'array',
        'error' => 'array',
        'duration_ms' => 'float',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function connection()
    {
        return $this->belongsTo(Connection::class);
    }

    public function scopeForConnector($query, string $connectorName)
    {
        return $query->where('connector_name', $connectorName);
    }

    public function scopeSuccessful($query)
    {
        return $query->where('status', 'success');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
}
