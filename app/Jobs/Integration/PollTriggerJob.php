<?php

declare(strict_types=1);

namespace App\Jobs\Integration;

use App\Services\Integration\Trigger\TriggerEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Job to poll a trigger for new events.
 */
class PollTriggerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 60;

    public function __construct(
        public string $subscriptionId
    ) {}

    public function handle(TriggerEngine $triggerEngine): void
    {
        try {
            $triggerEngine->poll($this->subscriptionId);
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    public function tags(): array
    {
        return ['integration', 'polling', "subscription:{$this->subscriptionId}"];
    }
}
