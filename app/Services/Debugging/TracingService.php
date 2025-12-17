<?php

declare(strict_types=1);

namespace App\Services\Debugging;

use App\Models\DebugTrace;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Model;

/**
 * TracingService - Live debugging and tracing for business logic.
 * 
 * Features:
 * - Request tracing
 * - Workflow execution visualization
 * - Computed field dependency tracking
 * - Hook execution timeline
 * - Query performance monitoring
 * - Real-time log streaming
 */
class TracingService
{
    /**
     * Whether tracing is enabled.
     */
    protected bool $enabled = false;

    /**
     * Current request ID.
     */
    protected ?string $requestId = null;

    /**
     * Active trace stack.
     */
    protected array $traceStack = [];

    /**
     * Query log.
     */
    protected array $queryLog = [];

    /**
     * Memory tracking.
     */
    protected array $memorySnapshots = [];

    /**
     * Tenant ID.
     */
    protected ?int $tenantId = null;

    /**
     * Persist traces to database.
     */
    protected bool $persist = true;

    /**
     * In-memory traces for current request.
     */
    protected array $traces = [];

    /**
     * Enable tracing.
     */
    public function enable(): self
    {
        $this->enabled = true;
        $this->requestId = $this->requestId ?? Str::uuid()->toString();
        $this->startQueryLogging();
        return $this;
    }

    /**
     * Disable tracing.
     */
    public function disable(): self
    {
        $this->enabled = false;
        $this->stopQueryLogging();
        return $this;
    }

    /**
     * Check if tracing is enabled.
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Set tenant context.
     */
    public function setTenant(?int $tenantId): self
    {
        $this->tenantId = $tenantId;
        return $this;
    }

    /**
     * Set persistence mode.
     */
    public function setPersist(bool $persist): self
    {
        $this->persist = $persist;
        return $this;
    }

    /**
     * Get current request ID.
     */
    public function getRequestId(): ?string
    {
        return $this->requestId;
    }

    /**
     * Start a new trace.
     */
    public function startTrace(
        string $type,
        string $name,
        array $input = [],
        array $context = []
    ): string {
        if (!$this->enabled) {
            return '';
        }

        $traceId = Str::uuid()->toString();
        $parentTraceId = end($this->traceStack) ?: null;

        $trace = [
            'trace_id' => $traceId,
            'parent_trace_id' => $parentTraceId,
            'type' => $type,
            'name' => $name,
            'input' => $input,
            'context' => $context,
            'started_at' => microtime(true),
            'memory_start' => memory_get_usage(true),
            'status' => DebugTrace::STATUS_RUNNING,
        ];

        $this->traces[$traceId] = $trace;
        $this->traceStack[] = $traceId;

        return $traceId;
    }

    /**
     * End a trace.
     */
    public function endTrace(string $traceId, $output = null, ?string $error = null): array
    {
        if (!$this->enabled || !isset($this->traces[$traceId])) {
            return [];
        }

        $trace = &$this->traces[$traceId];
        $trace['ended_at'] = microtime(true);
        $trace['duration_ms'] = ($trace['ended_at'] - $trace['started_at']) * 1000;
        $trace['memory_bytes'] = memory_get_usage(true) - $trace['memory_start'];
        $trace['output'] = $this->sanitizeOutput($output);
        $trace['status'] = $error ? DebugTrace::STATUS_ERROR : DebugTrace::STATUS_SUCCESS;
        $trace['error'] = $error;

        // Remove from stack
        $this->traceStack = array_filter($this->traceStack, fn($id) => $id !== $traceId);

        // Persist if enabled
        if ($this->persist) {
            $this->persistTrace($trace);
        }

        return $trace;
    }

    /**
     * Trace a callback execution.
     */
    public function trace(string $type, string $name, callable $callback, array $context = []): mixed
    {
        if (!$this->enabled) {
            return $callback();
        }

        $traceId = $this->startTrace($type, $name, [], $context);

        try {
            $result = $callback();
            $this->endTrace($traceId, $result);
            return $result;
        } catch (\Throwable $e) {
            $this->endTrace($traceId, null, $e->getMessage());
            throw $e;
        }
    }

    /**
     * Trace a model operation.
     */
    public function traceModel(Model $model, string $operation, callable $callback): mixed
    {
        return $this->trace(
            DebugTrace::TYPE_REQUEST,
            get_class($model) . '::' . $operation,
            $callback,
            [
                'entity_type' => get_class($model),
                'entity_id' => $model->getKey(),
                'operation' => $operation,
            ]
        );
    }

    /**
     * Trace a hook execution.
     */
    public function traceHook(string $hookName, callable $callback, array $params = []): mixed
    {
        return $this->trace(
            DebugTrace::TYPE_HOOK,
            $hookName,
            $callback,
            ['params' => $this->sanitizeOutput($params)]
        );
    }

    /**
     * Trace a workflow transition.
     */
    public function traceWorkflow(
        string $entityName,
        $entityId,
        string $transition,
        callable $callback,
        array $context = []
    ): mixed {
        return $this->trace(
            DebugTrace::TYPE_WORKFLOW,
            "{$entityName}::{$transition}",
            $callback,
            array_merge($context, [
                'entity_type' => $entityName,
                'entity_id' => $entityId,
                'transition' => $transition,
            ])
        );
    }

    /**
     * Trace computed field calculation.
     */
    public function traceComputedField(
        string $entityName,
        $entityId,
        string $fieldName,
        callable $callback,
        array $dependencies = []
    ): mixed {
        return $this->trace(
            DebugTrace::TYPE_COMPUTED_FIELD,
            "{$entityName}.{$fieldName}",
            $callback,
            [
                'entity_type' => $entityName,
                'entity_id' => $entityId,
                'field' => $fieldName,
                'dependencies' => $dependencies,
            ]
        );
    }

    /**
     * Trace record rule evaluation.
     */
    public function traceRecordRule(
        string $ruleName,
        string $entityName,
        callable $callback,
        array $context = []
    ): mixed {
        return $this->trace(
            DebugTrace::TYPE_RECORD_RULE,
            "{$entityName}::{$ruleName}",
            $callback,
            array_merge($context, [
                'rule_name' => $ruleName,
                'entity_type' => $entityName,
            ])
        );
    }

    /**
     * Trace service call.
     */
    public function traceServiceCall(string $serviceId, callable $callback, array $params = []): mixed
    {
        return $this->trace(
            DebugTrace::TYPE_SERVICE_CALL,
            $serviceId,
            $callback,
            ['params' => $this->sanitizeOutput($params)]
        );
    }

    /**
     * Start query logging.
     */
    protected function startQueryLogging(): void
    {
        DB::listen(function ($query) {
            if ($this->enabled) {
                $this->queryLog[] = [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                    'connection' => $query->connectionName,
                    'trace_id' => end($this->traceStack) ?: null,
                ];
            }
        });
    }

    /**
     * Stop query logging.
     */
    protected function stopQueryLogging(): void
    {
        // DB::disableQueryLog() doesn't stop the listener,
        // but we check $this->enabled in the listener
    }

    /**
     * Get query log.
     */
    public function getQueryLog(): array
    {
        return $this->queryLog;
    }

    /**
     * Get queries for a specific trace.
     */
    public function getQueriesForTrace(string $traceId): array
    {
        return array_filter($this->queryLog, fn($q) => $q['trace_id'] === $traceId);
    }

    /**
     * Take memory snapshot.
     */
    public function memorySnapshot(string $label): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->memorySnapshots[] = [
            'label' => $label,
            'memory' => memory_get_usage(true),
            'peak' => memory_get_peak_usage(true),
            'time' => microtime(true),
            'trace_id' => end($this->traceStack) ?: null,
        ];
    }

    /**
     * Get memory snapshots.
     */
    public function getMemorySnapshots(): array
    {
        return $this->memorySnapshots;
    }

    /**
     * Persist trace to database.
     */
    protected function persistTrace(array $trace): void
    {
        try {
            DebugTrace::create([
                'tenant_id' => $this->tenantId,
                'trace_id' => $trace['trace_id'],
                'parent_trace_id' => $trace['parent_trace_id'],
                'type' => $trace['type'],
                'name' => $trace['name'],
                'entity_type' => $trace['context']['entity_type'] ?? null,
                'entity_id' => $trace['context']['entity_id'] ?? null,
                'user_id' => Auth::id(),
                'request_id' => $this->requestId,
                'input' => $trace['input'],
                'output' => $trace['output'],
                'context' => $trace['context'],
                'duration_ms' => $trace['duration_ms'],
                'memory_bytes' => $trace['memory_bytes'],
                'status' => $trace['status'],
                'error' => $trace['error'],
                'started_at' => \Carbon\Carbon::createFromTimestamp($trace['started_at']),
                'ended_at' => \Carbon\Carbon::createFromTimestamp($trace['ended_at']),
            ]);
        } catch (\Throwable $e) {
            Log::warning('Failed to persist debug trace', [
                'error' => $e->getMessage(),
                'trace_id' => $trace['trace_id'],
            ]);
        }
    }

    /**
     * Sanitize output for storage.
     */
    protected function sanitizeOutput($output): mixed
    {
        if ($output instanceof Model) {
            return [
                '__type' => 'model',
                'class' => get_class($output),
                'key' => $output->getKey(),
                'attributes' => $output->attributesToArray(),
            ];
        }

        if (is_object($output)) {
            return [
                '__type' => 'object',
                'class' => get_class($output),
            ];
        }

        if (is_array($output)) {
            return array_map(fn($v) => $this->sanitizeOutput($v), $output);
        }

        return $output;
    }

    /**
     * Get all traces for current request.
     */
    public function getTraces(): array
    {
        return $this->traces;
    }

    /**
     * Get trace tree (hierarchical).
     */
    public function getTraceTree(): array
    {
        $rootTraces = array_filter($this->traces, fn($t) => $t['parent_trace_id'] === null);
        
        return array_map(function ($trace) {
            return $this->buildTraceNode($trace);
        }, $rootTraces);
    }

    /**
     * Build trace node with children.
     */
    protected function buildTraceNode(array $trace): array
    {
        $children = array_filter(
            $this->traces,
            fn($t) => $t['parent_trace_id'] === $trace['trace_id']
        );

        $trace['children'] = array_map(
            fn($child) => $this->buildTraceNode($child),
            $children
        );

        return $trace;
    }

    /**
     * Get summary of current request.
     */
    public function getSummary(): array
    {
        $traces = array_values($this->traces);
        $queries = $this->queryLog;

        $totalDuration = array_sum(array_column($traces, 'duration_ms'));
        $totalQueries = count($queries);
        $totalQueryTime = array_sum(array_column($queries, 'time'));
        $errors = array_filter($traces, fn($t) => $t['status'] === DebugTrace::STATUS_ERROR);

        // Group by type
        $byType = [];
        foreach ($traces as $trace) {
            $type = $trace['type'];
            if (!isset($byType[$type])) {
                $byType[$type] = ['count' => 0, 'duration_ms' => 0];
            }
            $byType[$type]['count']++;
            $byType[$type]['duration_ms'] += $trace['duration_ms'] ?? 0;
        }

        return [
            'request_id' => $this->requestId,
            'total_traces' => count($traces),
            'total_duration_ms' => round($totalDuration, 2),
            'total_queries' => $totalQueries,
            'total_query_time_ms' => round($totalQueryTime, 2),
            'errors' => count($errors),
            'memory_peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'by_type' => $byType,
        ];
    }

    /**
     * Clear all traces.
     */
    public function clear(): void
    {
        $this->traces = [];
        $this->traceStack = [];
        $this->queryLog = [];
        $this->memorySnapshots = [];
        $this->requestId = null;
    }

    /**
     * Export traces for analysis.
     */
    public function export(): array
    {
        return [
            'request_id' => $this->requestId,
            'summary' => $this->getSummary(),
            'traces' => $this->getTraceTree(),
            'queries' => $this->queryLog,
            'memory_snapshots' => $this->memorySnapshots,
        ];
    }
}
