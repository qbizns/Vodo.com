<?php

declare(strict_types=1);

namespace App\Services\Debugging;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

/**
 * WorkflowTracer - Traces and visualizes workflow executions.
 * 
 * Features:
 * - Transition path visualization
 * - Condition evaluation tracking
 * - Action execution logging
 * - Failure point identification
 */
class WorkflowTracer
{
    protected TracingService $tracer;
    protected array $workflowTraces = [];

    public function __construct(TracingService $tracer)
    {
        $this->tracer = $tracer;
    }

    /**
     * Start tracing a workflow transition.
     */
    public function startTransition(
        string $workflowName,
        string $entityName,
        $entityId,
        string $transition,
        string $fromState,
        string $toState
    ): string {
        $traceId = $this->tracer->startTrace(
            'workflow',
            "{$workflowName}::{$transition}",
            [
                'from_state' => $fromState,
                'to_state' => $toState,
            ],
            [
                'workflow' => $workflowName,
                'entity_type' => $entityName,
                'entity_id' => $entityId,
                'transition' => $transition,
            ]
        );

        $this->workflowTraces[$traceId] = [
            'workflow' => $workflowName,
            'entity' => $entityName,
            'entity_id' => $entityId,
            'transition' => $transition,
            'from_state' => $fromState,
            'to_state' => $toState,
            'conditions' => [],
            'actions' => [],
            'started_at' => microtime(true),
        ];

        return $traceId;
    }

    /**
     * Trace a condition evaluation.
     */
    public function traceCondition(
        string $traceId,
        string $conditionName,
        array $conditionDef,
        bool $result,
        array $context = []
    ): void {
        if (!isset($this->workflowTraces[$traceId])) {
            return;
        }

        $this->workflowTraces[$traceId]['conditions'][] = [
            'name' => $conditionName,
            'definition' => $conditionDef,
            'result' => $result,
            'context' => $context,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * Trace an action execution.
     */
    public function traceAction(
        string $traceId,
        string $actionName,
        array $actionDef,
        bool $success,
        $result = null,
        ?string $error = null
    ): void {
        if (!isset($this->workflowTraces[$traceId])) {
            return;
        }

        $this->workflowTraces[$traceId]['actions'][] = [
            'name' => $actionName,
            'definition' => $actionDef,
            'success' => $success,
            'result' => $result,
            'error' => $error,
            'timestamp' => microtime(true),
        ];
    }

    /**
     * End workflow transition trace.
     */
    public function endTransition(string $traceId, bool $success, ?string $error = null): array
    {
        if (!isset($this->workflowTraces[$traceId])) {
            return [];
        }

        $workflowTrace = $this->workflowTraces[$traceId];
        $workflowTrace['success'] = $success;
        $workflowTrace['error'] = $error;
        $workflowTrace['ended_at'] = microtime(true);
        $workflowTrace['duration_ms'] = ($workflowTrace['ended_at'] - $workflowTrace['started_at']) * 1000;

        // End the parent trace
        $this->tracer->endTrace($traceId, $workflowTrace, $error);

        return $workflowTrace;
    }

    /**
     * Generate visualization data for a workflow trace.
     */
    public function visualize(string $traceId): array
    {
        if (!isset($this->workflowTraces[$traceId])) {
            return [];
        }

        $trace = $this->workflowTraces[$traceId];

        return [
            'summary' => [
                'workflow' => $trace['workflow'],
                'transition' => $trace['transition'],
                'from' => $trace['from_state'],
                'to' => $trace['to_state'],
                'success' => $trace['success'] ?? null,
                'duration_ms' => $trace['duration_ms'] ?? null,
            ],
            'timeline' => $this->buildTimeline($trace),
            'mermaid' => $this->generateMermaid($trace),
        ];
    }

    /**
     * Build execution timeline.
     */
    protected function buildTimeline(array $trace): array
    {
        $events = [];
        $startTime = $trace['started_at'];

        $events[] = [
            'type' => 'start',
            'label' => "Start transition: {$trace['transition']}",
            'time_offset_ms' => 0,
        ];

        foreach ($trace['conditions'] as $condition) {
            $events[] = [
                'type' => 'condition',
                'label' => "Condition: {$condition['name']}",
                'result' => $condition['result'] ? 'passed' : 'failed',
                'time_offset_ms' => ($condition['timestamp'] - $startTime) * 1000,
                'details' => $condition['definition'],
            ];
        }

        foreach ($trace['actions'] as $action) {
            $events[] = [
                'type' => 'action',
                'label' => "Action: {$action['name']}",
                'result' => $action['success'] ? 'success' : 'error',
                'error' => $action['error'],
                'time_offset_ms' => ($action['timestamp'] - $startTime) * 1000,
            ];
        }

        if (isset($trace['ended_at'])) {
            $events[] = [
                'type' => 'end',
                'label' => ($trace['success'] ?? false) ? 'Transition completed' : 'Transition failed',
                'result' => ($trace['success'] ?? false) ? 'success' : 'error',
                'error' => $trace['error'] ?? null,
                'time_offset_ms' => $trace['duration_ms'] ?? 0,
            ];
        }

        return $events;
    }

    /**
     * Generate Mermaid diagram for workflow trace.
     */
    protected function generateMermaid(array $trace): string
    {
        $lines = ['flowchart TD'];

        // States
        $lines[] = "    {$trace['from_state']}(({$trace['from_state']}))";
        $lines[] = "    {$trace['to_state']}(({$trace['to_state']}))";

        // Conditions
        $conditionNodes = [];
        foreach ($trace['conditions'] as $i => $condition) {
            $nodeId = "cond{$i}";
            $result = $condition['result'] ? '✓' : '✗';
            $lines[] = "    {$nodeId}{{{$condition['name']} {$result}}}";
            $conditionNodes[] = $nodeId;
        }

        // Actions
        $actionNodes = [];
        foreach ($trace['actions'] as $i => $action) {
            $nodeId = "act{$i}";
            $result = $action['success'] ? '✓' : '✗';
            $lines[] = "    {$nodeId}[{$action['name']} {$result}]";
            $actionNodes[] = $nodeId;
        }

        // Connections
        $prev = $trace['from_state'];
        foreach ($conditionNodes as $node) {
            $lines[] = "    {$prev} --> {$node}";
            $prev = $node;
        }
        foreach ($actionNodes as $node) {
            $lines[] = "    {$prev} --> {$node}";
            $prev = $node;
        }
        if ($trace['success'] ?? false) {
            $lines[] = "    {$prev} --> {$trace['to_state']}";
        }

        // Styling
        $lines[] = "    classDef success fill:#90EE90";
        $lines[] = "    classDef error fill:#FFB6C1";
        $lines[] = "    classDef state fill:#87CEEB";

        $lines[] = "    class {$trace['from_state']},{$trace['to_state']} state";

        foreach ($trace['conditions'] as $i => $condition) {
            $class = $condition['result'] ? 'success' : 'error';
            $lines[] = "    class cond{$i} {$class}";
        }

        foreach ($trace['actions'] as $i => $action) {
            $class = $action['success'] ? 'success' : 'error';
            $lines[] = "    class act{$i} {$class}";
        }

        return implode("\n", $lines);
    }

    /**
     * Get workflow trace.
     */
    public function getTrace(string $traceId): ?array
    {
        return $this->workflowTraces[$traceId] ?? null;
    }

    /**
     * Clear traces.
     */
    public function clear(): void
    {
        $this->workflowTraces = [];
    }
}
