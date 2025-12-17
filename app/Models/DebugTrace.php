<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * DebugTrace Model - Stores execution traces for debugging.
 */
class DebugTrace extends Model
{
    protected $table = 'debug_traces';

    protected $fillable = [
        'tenant_id',
        'trace_id',
        'parent_trace_id',
        'type',
        'name',
        'entity_type',
        'entity_id',
        'user_id',
        'request_id',
        'input',
        'output',
        'context',
        'duration_ms',
        'memory_bytes',
        'status',
        'error',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'input' => 'array',
        'output' => 'array',
        'context' => 'array',
        'duration_ms' => 'float',
        'memory_bytes' => 'integer',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    /**
     * Trace types.
     */
    public const TYPE_REQUEST = 'request';
    public const TYPE_HOOK = 'hook';
    public const TYPE_WORKFLOW = 'workflow';
    public const TYPE_COMPUTED_FIELD = 'computed_field';
    public const TYPE_RECORD_RULE = 'record_rule';
    public const TYPE_QUERY = 'query';
    public const TYPE_SERVICE_CALL = 'service_call';
    public const TYPE_PLUGIN = 'plugin';
    public const TYPE_CUSTOM = 'custom';

    /**
     * Statuses.
     */
    public const STATUS_RUNNING = 'running';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_ERROR = 'error';

    /**
     * Get user.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get parent trace.
     */
    public function parentTrace(): BelongsTo
    {
        return $this->belongsTo(DebugTrace::class, 'parent_trace_id');
    }

    /**
     * Get child traces.
     */
    public function childTraces()
    {
        return $this->hasMany(DebugTrace::class, 'parent_trace_id');
    }

    /**
     * Scope by trace ID.
     */
    public function scopeForTraceId($query, string $traceId)
    {
        return $query->where('trace_id', $traceId);
    }

    /**
     * Scope by request ID.
     */
    public function scopeForRequest($query, string $requestId)
    {
        return $query->where('request_id', $requestId);
    }

    /**
     * Scope by entity.
     */
    public function scopeForEntity($query, string $type, $id)
    {
        return $query->where('entity_type', $type)->where('entity_id', $id);
    }

    /**
     * Scope by type.
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope errors only.
     */
    public function scopeErrors($query)
    {
        return $query->where('status', self::STATUS_ERROR);
    }

    /**
     * Scope slow traces.
     */
    public function scopeSlow($query, float $thresholdMs = 1000)
    {
        return $query->where('duration_ms', '>', $thresholdMs);
    }
}
