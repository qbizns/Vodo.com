<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Models\WebhookSubscription;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queued Webhook Delivery Job
 *
 * Handles asynchronous webhook delivery with retry logic.
 */
class DeliverWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff;

    public function __construct(
        protected WebhookSubscription $subscription,
        protected string $event,
        protected array $payload,
        protected bool $isRetry = false
    ) {
        $this->tries = $subscription->retry_count;
        $this->backoff = $subscription->retry_delay;
    }

    /**
     * Execute the job.
     */
    public function handle(WebhookRegistry $registry): void
    {
        $result = $registry->deliver($this->subscription, $this->event, $this->payload);

        if (!$result['success'] && $this->attempts() < $this->tries) {
            $this->release($this->backoff);
        }
    }

    /**
     * Handle job failure.
     */
    public function failed(\Throwable $exception): void
    {
        // Final failure is already logged in deliver()
        // Fire hook for monitoring
        do_action('webhook_delivery_failed', $this->subscription, $this->event, $exception);
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'webhook',
            'event:' . $this->event,
            'subscription:' . $this->subscription->id,
        ];
    }
}
