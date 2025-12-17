<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessEventListener implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(
        public string $listener,
        public array $payload = []
    ) {}

    public function handle(): void
    {
        try {
            if (str_contains($this->listener, '@')) {
                [$class, $method] = explode('@', $this->listener);
            } else {
                $class = $this->listener;
                $method = 'handle';
            }

            $instance = app($class);
            $instance->{$method}($this->payload);

        } catch (\Throwable $e) {
            Log::error("Async event listener failed: {$this->listener}", [
                'error' => $e->getMessage(),
                'payload' => $this->payload,
            ]);
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        Log::error("ProcessEventListener job failed permanently", [
            'listener' => $this->listener,
            'error' => $exception->getMessage(),
        ]);
    }
}
