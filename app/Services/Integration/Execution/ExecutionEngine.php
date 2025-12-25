<?php

declare(strict_types=1);

namespace App\Services\Integration\Execution;

use App\Contracts\Integration\ExecutionEngineContract;
use App\Services\Integration\Flow\FlowEngine;
use App\Services\Integration\Action\ActionEngine;
use App\Models\Integration\Flow;
use App\Models\Integration\FlowNode;
use App\Models\Integration\FlowExecution;
use App\Models\Integration\FlowStepExecution;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Execution Engine
 *
 * Executes flows, manages queues, and provides logging/debugging capabilities.
 * This is the runtime engine that processes automation workflows.
 *
 * @example Execute a flow
 * ```php
 * $execution = $engine->execute($flowId, [
 *     'trigger_data' => ['user_id' => 123],
 *     'connections' => ['slack' => $connectionId],
 * ]);
 * ```
 *
 * @example Resume paused execution
 * ```php
 * $engine->resume($executionId, ['continue' => true]);
 * ```
 */
class ExecutionEngine implements ExecutionEngineContract
{
    /**
     * Maximum execution time per flow (seconds).
     */
    protected int $maxExecutionTime = 300;

    /**
     * Maximum nodes to execute per flow.
     */
    protected int $maxNodes = 1000;

    /**
     * Maximum loop iterations.
     */
    protected int $maxLoopIterations = 10000;

    public function __construct(
        protected FlowEngine $flowEngine,
        protected ActionEngine $actionEngine
    ) {}

    // =========================================================================
    // EXECUTION
    // =========================================================================

    public function execute(string $flowId, array $context = []): FlowExecution
    {
        $flow = $this->flowEngine->get($flowId);

        if (!$flow) {
            throw new \InvalidArgumentException("Flow not found: {$flowId}");
        }

        if ($flow->status !== 'active' && !($context['force'] ?? false)) {
            throw new \App\Exceptions\Integration\FlowNotActiveException(
                "Flow is not active: {$flow->name}"
            );
        }

        // Create execution record
        $execution = FlowExecution::create([
            'id' => Str::uuid()->toString(),
            'flow_id' => $flowId,
            'flow_version' => $flow->version,
            'tenant_id' => $flow->tenant_id,
            'trigger_data' => $context['trigger_data'] ?? [],
            'context' => $context,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Execute synchronously or queue
        if ($context['async'] ?? true) {
            Queue::push(new \App\Jobs\Integration\ExecuteFlowJob(
                $execution->id,
                $flowId,
                $context
            ));
        } else {
            $this->runExecution($execution, $flow, $context);
        }

        return $execution->fresh();
    }

    public function executeAsync(string $flowId, array $context = []): string
    {
        $execution = $this->execute($flowId, array_merge($context, ['async' => true]));
        return $execution->id;
    }

    /**
     * Run the actual flow execution.
     */
    public function runExecution(FlowExecution $execution, Flow $flow, array $context): void
    {
        $startTime = microtime(true);
        $nodesExecuted = 0;

        try {
            // Build execution context
            $execContext = [
                'execution_id' => $execution->id,
                'flow_id' => $flow->id,
                'trigger_data' => $context['trigger_data'] ?? [],
                'connections' => $context['connections'] ?? [],
                'variables' => $context['variables'] ?? [],
                'node_outputs' => [],
                'data' => $context['trigger_data'] ?? [],
            ];

            // Find trigger node (entry point)
            $triggerNode = $flow->nodes->where('type', 'trigger')->first();

            if (!$triggerNode) {
                throw new \RuntimeException('Flow has no trigger node');
            }

            // Build execution graph
            $graph = $this->buildExecutionGraph($flow);

            // Execute from trigger
            $queue = [$triggerNode->node_id];
            $executed = [];

            while (!empty($queue)) {
                // Check limits
                $elapsed = microtime(true) - $startTime;
                if ($elapsed > $this->maxExecutionTime) {
                    throw new \App\Exceptions\Integration\ExecutionTimeoutException(
                        "Execution exceeded max time of {$this->maxExecutionTime}s"
                    );
                }

                if ($nodesExecuted > $this->maxNodes) {
                    throw new \App\Exceptions\Integration\ExecutionLimitException(
                        "Exceeded max nodes limit of {$this->maxNodes}"
                    );
                }

                $currentNodeId = array_shift($queue);

                // Skip if already executed (prevent cycles)
                if (in_array($currentNodeId, $executed)) {
                    continue;
                }

                $node = $flow->nodes->firstWhere('node_id', $currentNodeId);

                if (!$node) {
                    continue;
                }

                // Execute node
                $stepExecution = $this->executeNode($execution, $node, $execContext);
                $executed[] = $currentNodeId;
                $nodesExecuted++;

                // Store output
                $execContext['node_outputs'][$currentNodeId] = $stepExecution->output ?? [];
                $execContext['data'] = array_merge($execContext['data'], $stepExecution->output ?? []);

                // Handle special results
                $output = $stepExecution->output ?? [];

                // Check for end
                if (isset($output['_end'])) {
                    break;
                }

                // Check for wait (pause execution)
                if (isset($output['_wait'])) {
                    $execution->update([
                        'status' => 'waiting',
                        'resume_at' => $output['resume_at'],
                        'context' => $execContext,
                    ]);

                    // Schedule resume
                    Queue::later(
                        now()->timestamp($output['resume_at']),
                        new \App\Jobs\Integration\ResumeFlowJob($execution->id)
                    );

                    return;
                }

                // Handle branching
                if (isset($output['_branch'])) {
                    $branch = $output['_branch'];
                    $edges = $graph[$currentNodeId] ?? [];

                    foreach ($edges as $edge) {
                        if ($edge['handle'] === $branch || $edge['handle'] === 'output') {
                            $queue[] = $edge['target'];
                        }
                    }
                    continue;
                }

                // Handle loop
                if (isset($output['_loop'])) {
                    $this->handleLoop($execution, $node, $output, $graph, $execContext);
                    continue;
                }

                // Handle split (create multiple parallel executions)
                if (isset($output['_split'])) {
                    foreach ($output['items'] as $index => $item) {
                        $splitContext = $execContext;
                        $splitContext['data'] = $item;
                        $splitContext['split_index'] = $index;

                        // Queue each item for parallel execution
                        // This is simplified - real implementation would handle properly
                    }
                    continue;
                }

                // Normal flow - add next nodes to queue
                $edges = $graph[$currentNodeId] ?? [];
                foreach ($edges as $edge) {
                    $queue[] = $edge['target'];
                }
            }

            // Mark as completed
            $execution->update([
                'status' => 'completed',
                'completed_at' => now(),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'nodes_executed' => $nodesExecuted,
                'output' => $execContext['data'],
            ]);

            do_action('flow_execution_completed', $execution);

        } catch (\Exception $e) {
            $execution->update([
                'status' => 'failed',
                'completed_at' => now(),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'nodes_executed' => $nodesExecuted,
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                    'trace' => $e->getTraceAsString(),
                ],
            ]);

            do_action('flow_execution_failed', $execution, $e);

            throw $e;
        }
    }

    /**
     * Execute a single node.
     */
    protected function executeNode(
        FlowExecution $execution,
        FlowNode $node,
        array &$context
    ): FlowStepExecution {
        $stepExecution = FlowStepExecution::create([
            'id' => Str::uuid()->toString(),
            'execution_id' => $execution->id,
            'node_id' => $node->node_id,
            'node_type' => $node->type,
            'node_name' => $node->name,
            'input' => $context['data'],
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $output = $this->processNode($node, $context);

            // Handle action execution
            if (isset($output['_execute_action'])) {
                $actionResult = $this->actionEngine->execute(
                    $output['connector'],
                    $output['action'],
                    $output['connection_id'],
                    $output['input'],
                    ['execution_id' => $execution->id]
                );
                $output = $actionResult['data'] ?? [];
            }

            // Handle HTTP request
            if (isset($output['_http_request'])) {
                $output = $this->executeHttpRequest($output);
            }

            // Handle code execution
            if (isset($output['_execute_code'])) {
                $output = $this->executeCode($output['code'], $output['language'], $context);
            }

            $stepExecution->update([
                'status' => 'success',
                'output' => $output,
                'completed_at' => now(),
                'duration_ms' => now()->diffInMilliseconds($stepExecution->started_at),
            ]);

        } catch (\Exception $e) {
            $stepExecution->update([
                'status' => 'failed',
                'error' => [
                    'message' => $e->getMessage(),
                    'type' => get_class($e),
                ],
                'completed_at' => now(),
            ]);

            // Check if node has error handling
            $onError = $node->config['on_error'] ?? 'stop';

            if ($onError === 'continue') {
                return $stepExecution;
            }

            if ($onError === 'retry') {
                // Retry logic would go here
            }

            throw $e;
        }

        return $stepExecution;
    }

    /**
     * Process a node and get its output.
     */
    protected function processNode(FlowNode $node, array $context): array
    {
        // The FlowEngine has the node handlers
        // This is a simplified version - in practice, we'd use the FlowEngine's handlers
        return match ($node->type) {
            'trigger' => $context['trigger_data'] ?? [],
            'action' => [
                '_execute_action' => true,
                'connector' => $node->config['connector'] ?? '',
                'action' => $node->config['action'] ?? '',
                'connection_id' => $node->config['connection_id'] ?? $context['connections'][$node->config['connector']] ?? '',
                'input' => $this->resolveInput($node->config['input'] ?? [], $context),
            ],
            'condition' => $this->evaluateCondition($node, $context),
            'transform' => $this->executeTransform($node, $context),
            'http' => [
                '_http_request' => true,
                'url' => $this->resolveValue($node->config['url'] ?? '', $context),
                'method' => $node->config['method'] ?? 'GET',
                'headers' => $this->resolveInput($node->config['headers'] ?? [], $context),
                'body' => $this->resolveInput($node->config['body'] ?? [], $context),
            ],
            'set' => $this->executeSet($node, $context),
            'wait' => [
                '_wait' => true,
                'duration_ms' => $node->config['duration'] ?? 1000,
                'resume_at' => now()->addMilliseconds($node->config['duration'] ?? 1000)->timestamp,
            ],
            'loop' => [
                '_loop' => true,
                'items' => data_get($context['data'], $node->config['array_path'] ?? '', []),
                'item_variable' => $node->config['item_variable'] ?? 'item',
            ],
            'end' => ['_end' => true],
            default => $context['data'],
        };
    }

    // =========================================================================
    // RESUME & CONTROL
    // =========================================================================

    public function resume(string $executionId, array $data = []): FlowExecution
    {
        $execution = FlowExecution::findOrFail($executionId);

        if ($execution->status !== 'waiting' && $execution->status !== 'paused') {
            throw new \RuntimeException("Execution cannot be resumed: {$execution->status}");
        }

        $flow = $this->flowEngine->get($execution->flow_id);
        $context = array_merge($execution->context ?? [], $data);

        $execution->update(['status' => 'running']);

        $this->runExecution($execution, $flow, $context);

        return $execution->fresh();
    }

    public function pause(string $executionId): bool
    {
        return FlowExecution::where('id', $executionId)
            ->where('status', 'running')
            ->update(['status' => 'paused']) > 0;
    }

    public function cancel(string $executionId): bool
    {
        return FlowExecution::where('id', $executionId)
            ->whereIn('status', ['running', 'waiting', 'paused'])
            ->update([
                'status' => 'cancelled',
                'completed_at' => now(),
            ]) > 0;
    }

    public function retry(string $executionId): FlowExecution
    {
        $original = FlowExecution::findOrFail($executionId);

        return $this->execute($original->flow_id, [
            'trigger_data' => $original->trigger_data,
            'connections' => $original->context['connections'] ?? [],
            'retry_of' => $executionId,
        ]);
    }

    // =========================================================================
    // STATUS & LOGGING
    // =========================================================================

    public function getStatus(string $executionId): array
    {
        $execution = FlowExecution::with('steps')->findOrFail($executionId);

        return [
            'id' => $execution->id,
            'status' => $execution->status,
            'started_at' => $execution->started_at,
            'completed_at' => $execution->completed_at,
            'duration_ms' => $execution->duration_ms,
            'nodes_executed' => $execution->nodes_executed,
            'current_node' => $execution->steps()
                ->where('status', 'running')
                ->first()?->node_name,
            'progress' => $this->calculateProgress($execution),
            'error' => $execution->error,
        ];
    }

    public function getLogs(string $executionId): Collection
    {
        return FlowStepExecution::where('execution_id', $executionId)
            ->orderBy('started_at')
            ->get()
            ->map(fn($step) => [
                'node_id' => $step->node_id,
                'node_name' => $step->node_name,
                'node_type' => $step->node_type,
                'status' => $step->status,
                'started_at' => $step->started_at,
                'duration_ms' => $step->duration_ms,
                'input' => $step->input,
                'output' => $step->output,
                'error' => $step->error,
            ]);
    }

    public function getDebugInfo(string $executionId): array
    {
        $execution = FlowExecution::with('steps')->findOrFail($executionId);

        return [
            'execution' => [
                'id' => $execution->id,
                'flow_id' => $execution->flow_id,
                'status' => $execution->status,
                'trigger_data' => $execution->trigger_data,
                'context' => $execution->context,
                'output' => $execution->output,
                'error' => $execution->error,
            ],
            'steps' => $this->getLogs($executionId),
            'timeline' => $this->buildTimeline($execution),
            'metrics' => [
                'total_duration_ms' => $execution->duration_ms,
                'nodes_executed' => $execution->nodes_executed,
                'slowest_node' => $execution->steps->sortByDesc('duration_ms')->first()?->node_name,
                'failed_nodes' => $execution->steps->where('status', 'failed')->count(),
            ],
        ];
    }

    protected function calculateProgress(FlowExecution $execution): int
    {
        if ($execution->status === 'completed') {
            return 100;
        }

        if ($execution->status === 'failed' || $execution->status === 'cancelled') {
            return 0;
        }

        $flow = $this->flowEngine->get($execution->flow_id);
        $totalNodes = $flow->nodes->count();
        $executedNodes = $execution->steps()->count();

        if ($totalNodes === 0) {
            return 0;
        }

        return min(99, (int)(($executedNodes / $totalNodes) * 100));
    }

    protected function buildTimeline(FlowExecution $execution): array
    {
        $timeline = [];

        $timeline[] = [
            'event' => 'started',
            'timestamp' => $execution->started_at,
        ];

        foreach ($execution->steps as $step) {
            $timeline[] = [
                'event' => 'node_started',
                'node' => $step->node_name,
                'timestamp' => $step->started_at,
            ];

            if ($step->completed_at) {
                $timeline[] = [
                    'event' => $step->status === 'success' ? 'node_completed' : 'node_failed',
                    'node' => $step->node_name,
                    'timestamp' => $step->completed_at,
                    'duration_ms' => $step->duration_ms,
                ];
            }
        }

        if ($execution->completed_at) {
            $timeline[] = [
                'event' => $execution->status,
                'timestamp' => $execution->completed_at,
            ];
        }

        return $timeline;
    }

    // =========================================================================
    // QUERYING
    // =========================================================================

    public function getExecutions(array $filters = []): Collection
    {
        $query = FlowExecution::query();

        if (isset($filters['flow_id'])) {
            $query->where('flow_id', $filters['flow_id']);
        }

        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['since'])) {
            $query->where('created_at', '>=', $filters['since']);
        }

        return $query->orderBy('created_at', 'desc')
            ->limit($filters['limit'] ?? 100)
            ->get();
    }

    public function getStatistics(string $flowId, ?string $period = null): array
    {
        $query = FlowExecution::where('flow_id', $flowId);

        if ($period) {
            $since = match ($period) {
                'hour' => now()->subHour(),
                'day' => now()->subDay(),
                'week' => now()->subWeek(),
                'month' => now()->subMonth(),
                default => now()->subDay(),
            };
            $query->where('created_at', '>=', $since);
        }

        $total = $query->count();
        $completed = (clone $query)->where('status', 'completed')->count();
        $failed = (clone $query)->where('status', 'failed')->count();
        $avgDuration = (clone $query)->where('status', 'completed')->avg('duration_ms');

        return [
            'total_executions' => $total,
            'completed' => $completed,
            'failed' => $failed,
            'success_rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
            'avg_duration_ms' => round($avgDuration ?? 0, 2),
            'running' => (clone $query)->where('status', 'running')->count(),
            'waiting' => (clone $query)->where('status', 'waiting')->count(),
        ];
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    protected function buildExecutionGraph(Flow $flow): array
    {
        $graph = [];

        foreach ($flow->nodes as $node) {
            $graph[$node->node_id] = [];
        }

        foreach ($flow->edges as $edge) {
            $graph[$edge->source_node][] = [
                'target' => $edge->target_node,
                'handle' => $edge->source_handle,
                'condition' => $edge->condition,
            ];
        }

        return $graph;
    }

    protected function handleLoop(
        FlowExecution $execution,
        FlowNode $node,
        array $loopOutput,
        array $graph,
        array &$context
    ): void {
        $items = $loopOutput['items'] ?? [];
        $itemVar = $loopOutput['item_variable'] ?? 'item';
        $indexVar = $loopOutput['index_variable'] ?? 'index';

        $iterations = 0;

        foreach ($items as $index => $item) {
            if ($iterations >= $this->maxLoopIterations) {
                throw new \App\Exceptions\Integration\ExecutionLimitException(
                    "Loop exceeded max iterations: {$this->maxLoopIterations}"
                );
            }

            // Set loop variables
            $context['data'][$itemVar] = $item;
            $context['data'][$indexVar] = $index;

            // Execute loop body nodes
            $edges = $graph[$node->node_id] ?? [];
            foreach ($edges as $edge) {
                if ($edge['handle'] === 'loop' || $edge['handle'] === 'output') {
                    // Execute the connected node for this iteration
                    // In a real implementation, this would recursively execute the subgraph
                }
            }

            $iterations++;
        }
    }

    protected function resolveInput(array $input, array $context): array
    {
        $resolved = [];

        foreach ($input as $key => $value) {
            $resolved[$key] = $this->resolveValue($value, $context);
        }

        return $resolved;
    }

    protected function resolveValue($value, array $context)
    {
        if (!is_string($value)) {
            return $value;
        }

        return preg_replace_callback('/\{\{\s*([^}]+)\s*\}\}/', function ($matches) use ($context) {
            $path = trim($matches[1]);

            if (str_starts_with($path, 'trigger.')) {
                return data_get($context['trigger_data'] ?? [], substr($path, 8), '');
            }

            if (str_starts_with($path, 'node.')) {
                $parts = explode('.', substr($path, 5), 2);
                return data_get($context['node_outputs'][$parts[0]] ?? [], $parts[1] ?? '', '');
            }

            return data_get($context['data'] ?? [], $path, '');
        }, $value);
    }

    protected function evaluateCondition(FlowNode $node, array $context): array
    {
        $conditions = $node->config['conditions'] ?? [];
        $combine = $node->config['combine_with'] ?? 'and';

        $results = [];
        foreach ($conditions as $cond) {
            $field = data_get($context['data'], $cond['field'] ?? '');
            $value = $cond['value'] ?? null;
            $op = $cond['operator'] ?? '==';

            $results[] = match ($op) {
                '==' => $field == $value,
                '!=' => $field != $value,
                '>' => $field > $value,
                '<' => $field < $value,
                'contains' => str_contains((string)$field, (string)$value),
                'is_empty' => empty($field),
                default => false,
            };
        }

        $result = $combine === 'and'
            ? !in_array(false, $results, true)
            : in_array(true, $results, true);

        return ['_branch' => $result ? 'true' : 'false', 'result' => $result];
    }

    protected function executeTransform(FlowNode $node, array $context): array
    {
        $mappings = $node->config['mappings'] ?? [];
        $result = [];

        foreach ($mappings as $map) {
            $value = data_get($context['data'], $map['source'] ?? '');
            data_set($result, $map['target'] ?? '', $value);
        }

        return $result;
    }

    protected function executeSet(FlowNode $node, array $context): array
    {
        $result = $context['data'];

        foreach ($node->config['assignments'] ?? [] as $assign) {
            $value = $this->resolveValue($assign['value'] ?? '', $context);
            data_set($result, $assign['key'] ?? '', $value);
        }

        return $result;
    }

    protected function executeHttpRequest(array $config): array
    {
        $response = Http::withHeaders($config['headers'] ?? [])
            ->timeout(30);

        $url = $config['url'];
        $method = strtoupper($config['method'] ?? 'GET');

        $response = match ($method) {
            'GET' => $response->get($url, $config['query'] ?? []),
            'POST' => $response->post($url, $config['body'] ?? []),
            'PUT' => $response->put($url, $config['body'] ?? []),
            'PATCH' => $response->patch($url, $config['body'] ?? []),
            'DELETE' => $response->delete($url, $config['body'] ?? []),
            default => throw new \InvalidArgumentException("Invalid HTTP method: {$method}"),
        };

        return [
            'status' => $response->status(),
            'headers' => $response->headers(),
            'body' => $response->json() ?? $response->body(),
            'success' => $response->successful(),
        ];
    }

    protected function executeCode(string $code, string $language, array $context): array
    {
        // Code execution in sandbox
        // This is a placeholder - real implementation would use a sandboxed environment
        // like V8js for JavaScript or a Docker container

        if ($language === 'javascript') {
            // Would use V8js or similar
            throw new \RuntimeException('JavaScript execution requires V8js extension');
        }

        throw new \RuntimeException("Unsupported code language: {$language}");
    }
}
