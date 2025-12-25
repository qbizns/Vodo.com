<?php

declare(strict_types=1);

namespace App\Jobs\Integration;

use App\Services\Integration\Execution\ExecutionEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to resume a paused/waiting flow execution.
 */
class ResumeFlowJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(
        public string $executionId,
        public array $data = []
    ) {}

    public function handle(ExecutionEngine $executionEngine): void
    {
        try {
            $executionEngine->resume($this->executionId, $this->data);
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    public function tags(): array
    {
        return ['integration', 'resume', "execution:{$this->executionId}"];
    }
}
