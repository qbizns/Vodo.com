<?php

declare(strict_types=1);

namespace App\Services\Webhook;

use App\Contracts\WebhookRegistryContract;
use App\Models\WebhookEvent;
use App\Models\WebhookSubscription;
use App\Models\WebhookLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Webhook Registry
 *
 * Manages webhook events, subscriptions, and delivery.
 * Supports event registration, signature verification, and retry logic.
 *
 * @example Register a webhook event
 * ```php
 * $registry->registerEvent('contact.created', [
 *     'description' => 'Fired when a new contact is created',
 *     'payload' => ['id', 'name', 'email', 'created_at'],
 * ]);
 * ```
 *
 * @example Create a subscription
 * ```php
 * $webhook = $registry->subscribe('https://example.com/webhook', [
 *     'contact.created',
 *     'contact.updated',
 * ], [
 *     'secret' => 'my-secret-key',
 * ]);
 * ```
 *
 * @example Dispatch an event
 * ```php
 * $registry->dispatch('contact.created', [
 *     'id' => 123,
 *     'name' => 'John Doe',
 *     'email' => 'john@example.com',
 * ]);
 * ```
 */
class WebhookRegistry implements WebhookRegistryContract
{
    /**
     * Registered event types.
     *
     * @var array<string, array>
     */
    protected array $events = [];

    /**
     * Plugin ownership.
     *
     * @var array<string, string>
     */
    protected array $pluginOwnership = [];

    /**
     * Default subscription options.
     */
    protected array $defaultOptions = [
        'secret' => null,
        'headers' => [],
        'timeout' => 30,
        'retry_count' => 3,
        'retry_delay' => 60,
        'is_active' => true,
    ];

    public function registerEvent(string $event, array $config = [], ?string $pluginSlug = null): self
    {
        $this->events[$event] = array_merge([
            'name' => $event,
            'description' => null,
            'payload' => [],
            'sample' => [],
        ], $config);

        if ($pluginSlug) {
            $this->pluginOwnership[$event] = $pluginSlug;
        }

        // Persist to database
        WebhookEvent::updateOrCreate(
            ['slug' => $event],
            [
                'name' => $config['label'] ?? $this->generateLabel($event),
                'description' => $config['description'] ?? null,
                'payload_schema' => $config['payload'] ?? [],
                'sample_payload' => $config['sample'] ?? [],
                'plugin_slug' => $pluginSlug,
                'is_system' => $config['is_system'] ?? false,
            ]
        );

        return $this;
    }

    public function getEvents(): Collection
    {
        $dbEvents = WebhookEvent::all()->keyBy('slug')->map(fn($event) => [
            'name' => $event->slug,
            'label' => $event->name,
            'description' => $event->description,
            'payload' => $event->payload_schema,
            'sample' => $event->sample_payload,
        ]);

        return collect($this->events)->merge($dbEvents);
    }

    public function subscribe(string $url, array $events, array $options = []): array
    {
        $options = array_merge($this->defaultOptions, $options);

        // Generate secret if not provided
        if (empty($options['secret'])) {
            $options['secret'] = Str::random(32);
        }

        $subscription = WebhookSubscription::create([
            'id' => Str::uuid()->toString(),
            'url' => $url,
            'events' => $events,
            'secret' => $options['secret'],
            'headers' => $options['headers'],
            'timeout' => $options['timeout'],
            'retry_count' => $options['retry_count'],
            'retry_delay' => $options['retry_delay'],
            'is_active' => $options['is_active'],
            'created_by' => auth()->id(),
        ]);

        // Fire hook
        do_action('webhook_subscribed', $subscription);

        return $subscription->toArray();
    }

    public function update(string $id, array $data): array
    {
        $subscription = WebhookSubscription::findOrFail($id);

        $subscription->update($data);

        // Fire hook
        do_action('webhook_updated', $subscription);

        return $subscription->fresh()->toArray();
    }

    public function unsubscribe(string $id): bool
    {
        $subscription = WebhookSubscription::find($id);

        if (!$subscription) {
            return false;
        }

        // Fire hook
        do_action('webhook_unsubscribed', $subscription);

        return $subscription->delete();
    }

    public function getSubscriptions(?string $event = null): Collection
    {
        $query = WebhookSubscription::query();

        if ($event) {
            $query->whereJsonContains('events', $event);
        }

        return $query->get();
    }

    public function dispatch(string $event, array $payload): int
    {
        // Get all active subscriptions for this event
        $subscriptions = WebhookSubscription::where('is_active', true)
            ->whereJsonContains('events', $event)
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            Queue::push(new DeliverWebhookJob(
                $subscription,
                $event,
                $payload
            ));
            $count++;
        }

        // Fire hook
        do_action('webhook_dispatched', $event, $payload, $count);

        return $count;
    }

    public function getLogs(?string $subscriptionId = null, int $limit = 50): Collection
    {
        $query = WebhookLog::orderBy('created_at', 'desc');

        if ($subscriptionId) {
            $query->where('subscription_id', $subscriptionId);
        }

        return $query->limit($limit)->get();
    }

    public function retry(string $logId): bool
    {
        $log = WebhookLog::findOrFail($logId);

        if ($log->status === 'success') {
            return false;
        }

        Queue::push(new DeliverWebhookJob(
            $log->subscription,
            $log->event,
            $log->payload,
            true // is retry
        ));

        return true;
    }

    public function test(string $url, array $payload = []): array
    {
        $testPayload = array_merge([
            'event' => 'webhook.test',
            'timestamp' => now()->toIso8601String(),
            'data' => ['message' => 'This is a test webhook'],
        ], $payload);

        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => 'webhook.test',
                    'X-Webhook-Timestamp' => now()->timestamp,
                ])
                ->post($url, $testPayload);

            $duration = (microtime(true) - $startTime) * 1000;

            return [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'duration_ms' => round($duration, 2),
                'response' => $response->body(),
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Generate a human-readable label from event name.
     */
    protected function generateLabel(string $name): string
    {
        return Str::title(str_replace(['.', '_', '-'], ' ', $name));
    }

    /**
     * Deliver a webhook synchronously.
     *
     * @param WebhookSubscription $subscription
     * @param string $event
     * @param array $payload
     * @return array Delivery result
     */
    public function deliver(WebhookSubscription $subscription, string $event, array $payload): array
    {
        $fullPayload = [
            'event' => $event,
            'timestamp' => now()->toIso8601String(),
            'data' => $payload,
        ];

        $signature = $this->generateSignature($fullPayload, $subscription->secret);

        try {
            $startTime = microtime(true);

            $response = Http::timeout($subscription->timeout)
                ->withHeaders(array_merge($subscription->headers ?? [], [
                    'Content-Type' => 'application/json',
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Timestamp' => now()->timestamp,
                    'X-Webhook-Id' => Str::uuid()->toString(),
                ]))
                ->post($subscription->url, $fullPayload);

            $duration = (microtime(true) - $startTime) * 1000;

            $result = [
                'success' => $response->successful(),
                'status_code' => $response->status(),
                'duration_ms' => round($duration, 2),
                'response' => $response->body(),
            ];

            // Log delivery
            $this->logDelivery($subscription, $event, $payload, $result);

            return $result;
        } catch (\Exception $e) {
            $result = [
                'success' => false,
                'error' => $e->getMessage(),
            ];

            // Log failure
            $this->logDelivery($subscription, $event, $payload, $result);

            return $result;
        }
    }

    /**
     * Generate HMAC signature for payload.
     */
    protected function generateSignature(array $payload, ?string $secret): string
    {
        if (!$secret) {
            return '';
        }

        $json = json_encode($payload);

        return 'sha256=' . hash_hmac('sha256', $json, $secret);
    }

    /**
     * Log webhook delivery.
     */
    protected function logDelivery(
        WebhookSubscription $subscription,
        string $event,
        array $payload,
        array $result
    ): void {
        WebhookLog::create([
            'subscription_id' => $subscription->id,
            'event' => $event,
            'payload' => $payload,
            'status' => $result['success'] ? 'success' : 'failed',
            'status_code' => $result['status_code'] ?? null,
            'response' => $result['response'] ?? $result['error'] ?? null,
            'duration_ms' => $result['duration_ms'] ?? null,
        ]);

        // Update subscription stats
        $subscription->increment('delivery_count');
        if (!$result['success']) {
            $subscription->increment('failure_count');
        }
        $subscription->update(['last_triggered_at' => now()]);
    }

    /**
     * Get webhook statistics.
     *
     * @param string|null $subscriptionId Filter by subscription
     * @param array $dateRange Date range
     * @return array
     */
    public function getStatistics(?string $subscriptionId = null, array $dateRange = []): array
    {
        $query = WebhookLog::query();

        if ($subscriptionId) {
            $query->where('subscription_id', $subscriptionId);
        }

        if (!empty($dateRange)) {
            $query->whereBetween('created_at', $dateRange);
        }

        return [
            'total' => $query->count(),
            'success' => (clone $query)->where('status', 'success')->count(),
            'failed' => (clone $query)->where('status', 'failed')->count(),
            'avg_duration_ms' => (clone $query)->avg('duration_ms'),
            'by_event' => (clone $query)->selectRaw('event, count(*) as count')
                ->groupBy('event')
                ->pluck('count', 'event')
                ->toArray(),
        ];
    }

    /**
     * Verify incoming webhook signature.
     *
     * @param string $payload Raw payload
     * @param string $signature Signature header
     * @param string $secret Secret key
     * @return bool
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        return hash_equals($expected, $signature);
    }
}
