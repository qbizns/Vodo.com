<?php

declare(strict_types=1);

namespace App\Services\Debugging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\App;

/**
 * DebugManager - Provides debug wrappers for models and operations.
 * 
 * Usage:
 * $invoice = Invoice::debug()->create([...]);
 * // Returns model + detailed trace
 * 
 * $result = Debug::operation('custom', function() { ... });
 * // Wraps any operation with tracing
 */
class DebugManager
{
    protected TracingService $tracer;
    protected bool $autoEnable = false;

    public function __construct(TracingService $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * Create a debug-enabled model query builder.
     */
    public function forModel(string $modelClass): DebugModelWrapper
    {
        $this->tracer->enable();
        return new DebugModelWrapper($modelClass, $this->tracer);
    }

    /**
     * Wrap an operation with debug tracing.
     */
    public function operation(string $name, callable $callback, array $context = []): DebugResult
    {
        $this->tracer->enable();

        $traceId = $this->tracer->startTrace('custom', $name, [], $context);

        try {
            $result = $callback();
            $trace = $this->tracer->endTrace($traceId, $result);

            return new DebugResult($result, $trace, $this->tracer->getSummary());
        } catch (\Throwable $e) {
            $trace = $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw new DebuggedException($e, $trace, $this->tracer->getSummary());
        }
    }

    /**
     * Get the underlying tracer.
     */
    public function tracer(): TracingService
    {
        return $this->tracer;
    }

    /**
     * Export current debug session.
     */
    public function export(): array
    {
        return $this->tracer->export();
    }

    /**
     * Clear debug session.
     */
    public function clear(): void
    {
        $this->tracer->clear();
    }
}

/**
 * Debug wrapper for model operations.
 */
class DebugModelWrapper
{
    protected string $modelClass;
    protected TracingService $tracer;
    protected Builder $query;

    public function __construct(string $modelClass, TracingService $tracer)
    {
        $this->modelClass = $modelClass;
        $this->tracer = $tracer;
        $this->query = $modelClass::query();
    }

    /**
     * Create a new model with debug tracing.
     */
    public function create(array $attributes = []): DebugResult
    {
        $traceId = $this->tracer->startTrace(
            'model_create',
            "{$this->modelClass}::create",
            ['attributes' => $attributes],
            ['model' => $this->modelClass]
        );

        try {
            $model = $this->modelClass::create($attributes);
            $trace = $this->tracer->endTrace($traceId, $model);

            return new DebugResult($model, $trace, $this->tracer->getSummary());
        } catch (\Throwable $e) {
            $trace = $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw new DebuggedException($e, $trace, $this->tracer->getSummary());
        }
    }

    /**
     * Find a model with debug tracing.
     */
    public function find($id): DebugResult
    {
        $traceId = $this->tracer->startTrace(
            'model_find',
            "{$this->modelClass}::find",
            ['id' => $id],
            ['model' => $this->modelClass]
        );

        try {
            $model = $this->modelClass::find($id);
            $trace = $this->tracer->endTrace($traceId, $model);

            return new DebugResult($model, $trace, $this->tracer->getSummary());
        } catch (\Throwable $e) {
            $trace = $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw new DebuggedException($e, $trace, $this->tracer->getSummary());
        }
    }

    /**
     * Update a model with debug tracing.
     */
    public function update(Model $model, array $attributes): DebugResult
    {
        $traceId = $this->tracer->startTrace(
            'model_update',
            "{$this->modelClass}::update",
            ['id' => $model->getKey(), 'attributes' => $attributes],
            ['model' => $this->modelClass]
        );

        try {
            $model->update($attributes);
            $trace = $this->tracer->endTrace($traceId, $model->fresh());

            return new DebugResult($model->fresh(), $trace, $this->tracer->getSummary());
        } catch (\Throwable $e) {
            $trace = $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw new DebuggedException($e, $trace, $this->tracer->getSummary());
        }
    }

    /**
     * Delete a model with debug tracing.
     */
    public function delete(Model $model): DebugResult
    {
        $traceId = $this->tracer->startTrace(
            'model_delete',
            "{$this->modelClass}::delete",
            ['id' => $model->getKey()],
            ['model' => $this->modelClass]
        );

        try {
            $result = $model->delete();
            $trace = $this->tracer->endTrace($traceId, $result);

            return new DebugResult($result, $trace, $this->tracer->getSummary());
        } catch (\Throwable $e) {
            $trace = $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw new DebuggedException($e, $trace, $this->tracer->getSummary());
        }
    }

    /**
     * Execute a query with debug tracing.
     */
    public function get(): DebugResult
    {
        $traceId = $this->tracer->startTrace(
            'model_query',
            "{$this->modelClass}::get",
            ['query' => $this->query->toSql()],
            ['model' => $this->modelClass]
        );

        try {
            $results = $this->query->get();
            $trace = $this->tracer->endTrace($traceId, ['count' => $results->count()]);

            return new DebugResult($results, $trace, $this->tracer->getSummary());
        } catch (\Throwable $e) {
            $trace = $this->tracer->endTrace($traceId, null, $e->getMessage());
            throw new DebuggedException($e, $trace, $this->tracer->getSummary());
        }
    }

    /**
     * Forward query builder methods.
     */
    public function __call(string $method, array $arguments): self
    {
        $this->query = $this->query->$method(...$arguments);
        return $this;
    }
}

/**
 * Debug result wrapper.
 */
class DebugResult
{
    public function __construct(
        public readonly mixed $result,
        public readonly array $trace,
        public readonly array $summary
    ) {}

    /**
     * Get the actual result.
     */
    public function getResult(): mixed
    {
        return $this->result;
    }

    /**
     * Get the trace data.
     */
    public function getTrace(): array
    {
        return $this->trace;
    }

    /**
     * Get the summary.
     */
    public function getSummary(): array
    {
        return $this->summary;
    }

    /**
     * Check if there was an error.
     */
    public function hasError(): bool
    {
        return ($this->trace['status'] ?? '') === 'error';
    }

    /**
     * Get duration in milliseconds.
     */
    public function getDurationMs(): float
    {
        return $this->trace['duration_ms'] ?? 0;
    }

    /**
     * Get query count.
     */
    public function getQueryCount(): int
    {
        return $this->summary['total_queries'] ?? 0;
    }

    /**
     * Convert to array.
     */
    public function toArray(): array
    {
        return [
            'result' => $this->result,
            'trace' => $this->trace,
            'summary' => $this->summary,
        ];
    }

    /**
     * Pretty print debug info.
     */
    public function dump(): void
    {
        dump($this->toArray());
    }
}

/**
 * Exception with debug information.
 */
class DebuggedException extends \Exception
{
    public function __construct(
        \Throwable $previous,
        public readonly array $trace,
        public readonly array $summary
    ) {
        parent::__construct($previous->getMessage(), (int) $previous->getCode(), $previous);
    }

    /**
     * Get debug trace.
     */
    public function getDebugTrace(): array
    {
        return $this->trace;
    }

    /**
     * Get debug summary.
     */
    public function getDebugSummary(): array
    {
        return $this->summary;
    }
}
