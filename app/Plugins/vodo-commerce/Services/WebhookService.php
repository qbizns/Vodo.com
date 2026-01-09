<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\WebhookEvent;
use VodoCommerce\Models\WebhookLog;
use VodoCommerce\Models\WebhookSubscription;

class WebhookService
{
    public function __construct(
        protected Store $store
    ) {
    }

    /**
     * Create a new webhook subscription.
     */
    public function createSubscription(array $data): WebhookSubscription
    {
        return DB::transaction(function () use ($data) {
            $subscription = WebhookSubscription::create([
                'store_id' => $this->store->id,
                'name' => $data['name'],
                'url' => $data['url'],
                'description' => $data['description'] ?? null,
                'events' => $data['events'],
                'secret' => $data['secret'] ?? 'whsec_' . Str::random(40),
                'is_active' => $data['is_active'] ?? true,
                'timeout_seconds' => $data['timeout_seconds'] ?? 30,
                'max_retry_attempts' => $data['max_retry_attempts'] ?? 3,
                'retry_delay_seconds' => $data['retry_delay_seconds'] ?? 60,
                'custom_headers' => $data['custom_headers'] ?? null,
                'meta' => $data['meta'] ?? null,
            ]);

            WebhookLog::info(
                $this->store->id,
                "Webhook subscription created: {$subscription->name}",
                ['subscription_id' => $subscription->id],
                $subscription->id
            );

            return $subscription;
        });
    }

    /**
     * Update a webhook subscription.
     */
    public function updateSubscription(WebhookSubscription $subscription, array $data): WebhookSubscription
    {
        return DB::transaction(function () use ($subscription, $data) {
            $subscription->update($data);

            WebhookLog::info(
                $this->store->id,
                "Webhook subscription updated: {$subscription->name}",
                ['subscription_id' => $subscription->id, 'changes' => array_keys($data)],
                $subscription->id
            );

            return $subscription->fresh();
        });
    }

    /**
     * Delete a webhook subscription.
     */
    public function deleteSubscription(WebhookSubscription $subscription): bool
    {
        WebhookLog::info(
            $this->store->id,
            "Webhook subscription deleted: {$subscription->name}",
            ['subscription_id' => $subscription->id],
            $subscription->id
        );

        return $subscription->delete();
    }

    /**
     * Dispatch a webhook event to all matching subscriptions.
     */
    public function dispatchEvent(string $eventType, array $payload): int
    {
        $subscriptions = WebhookSubscription::where('store_id', $this->store->id)
            ->active()
            ->subscribedToEvent($eventType)
            ->get();

        if ($subscriptions->isEmpty()) {
            WebhookLog::debug(
                $this->store->id,
                "No active subscriptions found for event: {$eventType}",
                ['event_type' => $eventType]
            );

            return 0;
        }

        $eventCount = 0;

        foreach ($subscriptions as $subscription) {
            $this->createWebhookEvent($subscription, $eventType, $payload);
            $eventCount++;
        }

        WebhookLog::info(
            $this->store->id,
            "Event dispatched to {$eventCount} subscription(s): {$eventType}",
            ['event_type' => $eventType, 'subscription_count' => $eventCount]
        );

        return $eventCount;
    }

    /**
     * Create a webhook event for a specific subscription.
     */
    public function createWebhookEvent(
        WebhookSubscription $subscription,
        string $eventType,
        array $payload
    ): WebhookEvent {
        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'subscription_id' => $subscription->id,
            'event_type' => $eventType,
            'event_id' => 'evt_' . Str::uuid(),
            'payload' => $payload,
            'status' => WebhookEvent::STATUS_PENDING,
            'next_retry_at' => now(),
            'max_retries' => $subscription->max_retry_attempts,
        ]);

        WebhookLog::debug(
            $this->store->id,
            "Webhook event created: {$eventType}",
            ['event_id' => $event->event_id, 'subscription_id' => $subscription->id],
            $subscription->id,
            $event->id
        );

        return $event;
    }

    /**
     * Get pending events ready for delivery.
     */
    public function getPendingEvents(int $limit = 100): \Illuminate\Support\Collection
    {
        return WebhookEvent::where('store_id', $this->store->id)
            ->readyForRetry()
            ->with('subscription')
            ->limit($limit)
            ->get();
    }

    /**
     * Cancel a webhook event.
     */
    public function cancelEvent(WebhookEvent $event, ?string $reason = null): void
    {
        $event->cancel();

        WebhookLog::warning(
            $this->store->id,
            "Webhook event cancelled: {$event->event_type}",
            ['event_id' => $event->event_id, 'reason' => $reason],
            $event->subscription_id,
            $event->id
        );
    }

    /**
     * Retry a failed webhook event.
     */
    public function retryEvent(WebhookEvent $event): void
    {
        if (!$event->canRetry()) {
            throw new \RuntimeException('Event cannot be retried');
        }

        $event->resetRetries();

        WebhookLog::info(
            $this->store->id,
            "Webhook event retry initiated: {$event->event_type}",
            ['event_id' => $event->event_id],
            $event->subscription_id,
            $event->id
        );
    }

    /**
     * Test a webhook subscription by sending a test event.
     */
    public function testWebhook(WebhookSubscription $subscription): WebhookEvent
    {
        $payload = [
            'event' => 'webhook.test',
            'subscription_id' => $subscription->id,
            'subscription_name' => $subscription->name,
            'timestamp' => now()->toIso8601String(),
            'message' => 'This is a test webhook event',
        ];

        return $this->createWebhookEvent($subscription, 'webhook.test', $payload);
    }

    /**
     * Get webhook statistics for the store.
     */
    public function getStatistics(?string $period = 'last_7_days'): array
    {
        $dateRange = $this->getDateRange($period);

        $totalEvents = WebhookEvent::where('store_id', $this->store->id)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->count();

        $deliveredEvents = WebhookEvent::where('store_id', $this->store->id)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->delivered()
            ->count();

        $failedEvents = WebhookEvent::where('store_id', $this->store->id)
            ->whereBetween('created_at', [$dateRange['from'], $dateRange['to']])
            ->failed()
            ->count();

        $pendingEvents = WebhookEvent::where('store_id', $this->store->id)
            ->pending()
            ->count();

        $activeSubscriptions = WebhookSubscription::where('store_id', $this->store->id)
            ->active()
            ->count();

        return [
            'total_events' => $totalEvents,
            'delivered_events' => $deliveredEvents,
            'failed_events' => $failedEvents,
            'pending_events' => $pendingEvents,
            'active_subscriptions' => $activeSubscriptions,
            'success_rate' => $totalEvents > 0 ? round(($deliveredEvents / $totalEvents) * 100, 2) : 0,
            'failure_rate' => $totalEvents > 0 ? round(($failedEvents / $totalEvents) * 100, 2) : 0,
            'period' => $period,
        ];
    }

    /**
     * Get date range based on period.
     */
    protected function getDateRange(string $period): array
    {
        $now = now();

        return match ($period) {
            'today' => ['from' => $now->copy()->startOfDay(), 'to' => $now->copy()->endOfDay()],
            'last_7_days' => ['from' => $now->copy()->subDays(7), 'to' => $now],
            'last_30_days' => ['from' => $now->copy()->subDays(30), 'to' => $now],
            'last_90_days' => ['from' => $now->copy()->subDays(90), 'to' => $now],
            default => ['from' => $now->copy()->subDays(7), 'to' => $now],
        };
    }

    /**
     * Verify webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expectedSignature = hash_hmac('sha256', $payload, $secret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Generate webhook signature.
     */
    public function generateSignature(string $payload, string $secret): string
    {
        return hash_hmac('sha256', $payload, $secret);
    }
}
