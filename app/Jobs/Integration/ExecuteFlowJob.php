<?php

declare(strict_types=1);

namespace App\Jobs\Integration;

use App\Services\Integration\Execution\ExecutionEngine;
use App\Services\Integration\Flow\FlowEngine;
use App\Models\Integration\FlowExecution;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to execute a flow asynchronously.
 */
class ExecuteFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The maximum number of seconds the job can run.
     */
    public int $timeout = 300;

    public function __construct(
        public string $executionId,
        public string $flowId,
        public array $context = []
    ) {}

    public function handle(ExecutionEngine $executionEngine, FlowEngine $flowEngine): void
    {
        $execution = FlowExecution::find($this->executionId);

        if (!$execution || $execution->status !== 'running') {
            return;
        }

        $flow = $flowEngine->get($this->flowId);

        if (!$flow) {
            $execution->update([
                'status' => 'failed',
                'error' => ['message' => 'Flow not found'],
                'completed_at' => now(),
            ]);
            return;
        }

        try {
            $executionEngine->runExecution($execution, $flow, $this->context);
        } catch (\Exception $e) {
            // Error is already recorded by ExecutionEngine
            report($e);
        }
    }

    public function failed(\Throwable $exception): void
    {
        $execution = FlowExecution::find($this->executionId);

        if ($execution && $execution->status === 'running') {
            $execution->update([
                'status' => 'failed',
                'error' => [
                    'message' => $exception->getMessage(),
                    'type' => get_class($exception),
                ],
                'completed_at' => now(),
            ]);
        }
    }

    public function tags(): array
    {
        return ['integration', 'flow', "flow:{$this->flowId}"];
    }
}
