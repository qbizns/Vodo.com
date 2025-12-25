<?php

declare(strict_types=1);

namespace App\Services\Integration\Trigger;

use App\Contracts\Integration\ConnectorRegistryContract;
use App\Contracts\Integration\CredentialVaultContract;
use App\Contracts\Integration\TriggerContract;
use App\Models\Integration\TriggerSubscription;
use App\Models\Integration\TriggerEvent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;

/**
 * Trigger Engine
 *
 * Manages trigger subscriptions, webhook handling, and polling.
 */
class TriggerEngine
{
    public function __construct(
        protected ConnectorRegistryContract $connectorRegistry,
        protected CredentialVaultContract $credentialVault
    ) {}

    // =========================================================================
    // SUBSCRIPTION MANAGEMENT
    // =========================================================================

    /**
     * Subscribe to a trigger.
     */
    public function subscribe(
        string $flowId,
        string $connectorName,
        string $triggerName,
        string $connectionId,
        array $config = []
    ): TriggerSubscription {
        $trigger = $this->connectorRegistry->getTrigger($connectorName, $triggerName);

        if (!$trigger) {
            throw new \InvalidArgumentException(
                "Trigger not found: {$connectorName}.{$triggerName}"
            );
        }

        $subscription = TriggerSubscription::create([
            'id' => Str::uuid()->toString(),
            'flow_id' => $flowId,
            'connector_name' => $connectorName,
            'trigger_name' => $triggerName,
            'connection_id' => $connectionId,
            'config' => $config,
            'status' => 'active',
            'webhook_id' => null,
            'webhook_secret' => Str::random(32),
        ]);

        // Register webhook if trigger type is webhook
        if ($trigger->getType() === 'webhook') {
            $this->registerWebhook($subscription, $trigger);
        }

        // Start polling if trigger type is polling
        if ($trigger->getType() === 'polling') {
            $this->startPolling($subscription);
        }

        return $subscription;
    }

    /**
     * Unsubscribe from a trigger.
     */
    public function unsubscribe(string $subscriptionId): bool
    {
        $subscription = TriggerSubscription::find($subscriptionId);

        if (!$subscription) {
            return false;
        }

        $trigger = $this->connectorRegistry->getTrigger(
            $subscription->connector_name,
            $subscription->trigger_name
        );

        // Unregister webhook
        if ($trigger && $trigger->getType() === 'webhook' && $subscription->webhook_id) {
            $this->unregisterWebhook($subscription, $trigger);
        }

        $subscription->delete();

        return true;
    }

    /**
     * Pause a subscription.
     */
    public function pause(string $subscriptionId): bool
    {
        return TriggerSubscription::where('id', $subscriptionId)
            ->update(['status' => 'paused']) > 0;
    }

    /**
     * Resume a subscription.
     */
    public function resume(string $subscriptionId): bool
    {
        return TriggerSubscription::where('id', $subscriptionId)
            ->update(['status' => 'active']) > 0;
    }

    /**
     * Get subscriptions for a flow.
     */
    public function getSubscriptions(string $flowId): Collection
    {
        return TriggerSubscription::where('flow_id', $flowId)->get();
    }

    // =========================================================================
    // WEBHOOK HANDLING
    // =========================================================================

    /**
     * Register webhook with external service.
     */
    protected function registerWebhook(TriggerSubscription $subscription, TriggerContract $trigger): void
    {
        $credentials = $this->credentialVault->retrieve($subscription->connection_id);
        $webhookUrl = $this->getWebhookUrl($subscription);

        $result = $trigger->registerWebhook($credentials, $webhookUrl, $subscription->config);

        $subscription->update([
            'webhook_id' => $result['webhook_id'] ?? null,
            'webhook_registered_at' => now(),
        ]);
    }

    /**
     * Unregister webhook from external service.
     */
    protected function unregisterWebhook(TriggerSubscription $subscription, TriggerContract $trigger): void
    {
        if (!$subscription->webhook_id) {
            return;
        }

        $credentials = $this->credentialVault->retrieve($subscription->connection_id);

        try {
            $trigger->unregisterWebhook($credentials, $subscription->webhook_id);
        } catch (\Exception $e) {
            // Log but don't fail
        }
    }

    /**
     * Get webhook URL for a subscription.
     */
    public function getWebhookUrl(TriggerSubscription $subscription): string
    {
        return route('integration.webhook', [
            'subscriptionId' => $subscription->id,
        ]);
    }

    /**
     * Handle incoming webhook.
     */
    public function handleWebhook(string $subscriptionId, array $payload, array $headers): void
    {
        $subscription = TriggerSubscription::findOrFail($subscriptionId);

        if ($subscription->status !== 'active') {
            return;
        }

        $trigger = $this->connectorRegistry->getTrigger(
            $subscription->connector_name,
            $subscription->trigger_name
        );

        if (!$trigger) {
            throw new \RuntimeException('Trigger not found');
        }

        // Verify webhook signature
        $rawPayload = json_encode($payload);
        $credentials = $this->credentialVault->retrieve($subscription->connection_id);

        if (!$trigger->verifyWebhook($rawPayload, $headers, $credentials)) {
            throw new \App\Exceptions\Integration\WebhookVerificationException(
                'Webhook signature verification failed'
            );
        }

        // Process webhook
        $processedData = $trigger->processWebhook($payload, $headers, $subscription->config);

        if ($processedData === null) {
            return; // Trigger decided to ignore this event
        }

        // Apply filters
        if (!$trigger->applyFilters($processedData, $subscription->config['filters'] ?? [])) {
            return; // Filtered out
        }

        // Create trigger event
        $this->createTriggerEvent($subscription, $processedData);
    }

    // =========================================================================
    // POLLING
    // =========================================================================

    /**
     * Start polling for a subscription.
     */
    protected function startPolling(TriggerSubscription $subscription): void
    {
        // Schedule polling job
        $this->scheduleNextPoll($subscription);
    }

    /**
     * Execute poll for a subscription.
     */
    public function poll(string $subscriptionId): void
    {
        $subscription = TriggerSubscription::find($subscriptionId);

        if (!$subscription || $subscription->status !== 'active') {
            return;
        }

        $trigger = $this->connectorRegistry->getTrigger(
            $subscription->connector_name,
            $subscription->trigger_name
        );

        if (!$trigger || $trigger->getType() !== 'polling') {
            return;
        }

        $credentials = $this->credentialVault->retrieve($subscription->connection_id);
        $state = $subscription->polling_state ?? [];

        // Execute poll
        $result = $trigger->poll($credentials, $subscription->config, $state);

        // Update state
        $subscription->update([
            'polling_state' => $result['state'],
            'last_polled_at' => now(),
        ]);

        // Process items
        foreach ($result['items'] as $item) {
            $dedupeKey = $trigger->getDeduplicationKey($item);

            // Check for duplicates
            $exists = TriggerEvent::where('subscription_id', $subscriptionId)
                ->where('deduplication_key', $dedupeKey)
                ->exists();

            if ($exists) {
                continue;
            }

            // Apply filters
            if (!$trigger->applyFilters($item, $subscription->config['filters'] ?? [])) {
                continue;
            }

            // Create trigger event
            $this->createTriggerEvent($subscription, $item, $dedupeKey);
        }

        // Schedule next poll
        $this->scheduleNextPoll($subscription, $trigger->getPollingInterval());
    }

    /**
     * Schedule next poll.
     */
    protected function scheduleNextPoll(TriggerSubscription $subscription, ?int $interval = null): void
    {
        $interval = $interval ?? 300; // Default 5 minutes

        Queue::later(
            now()->addSeconds($interval),
            new \App\Jobs\Integration\PollTriggerJob($subscription->id)
        );
    }

    // =========================================================================
    // EVENTS
    // =========================================================================

    /**
     * Create a trigger event.
     */
    protected function createTriggerEvent(
        TriggerSubscription $subscription,
        array $data,
        ?string $deduplicationKey = null
    ): TriggerEvent {
        $event = TriggerEvent::create([
            'id' => Str::uuid()->toString(),
            'subscription_id' => $subscription->id,
            'flow_id' => $subscription->flow_id,
            'data' => $data,
            'deduplication_key' => $deduplicationKey ?? md5(json_encode($data)),
            'status' => 'pending',
        ]);

        // Dispatch flow execution
        Queue::push(new \App\Jobs\Integration\ExecuteFlowJob(
            $subscription->flow_id,
            $event->id,
            $data
        ));

        // Fire hook
        do_action('trigger_event_created', $event);

        return $event;
    }

    /**
     * Get recent events for a subscription.
     */
    public function getEvents(string $subscriptionId, int $limit = 50): Collection
    {
        return TriggerEvent::where('subscription_id', $subscriptionId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    // =========================================================================
    // TESTING
    // =========================================================================

    /**
     * Test a trigger manually.
     */
    public function test(
        string $connectorName,
        string $triggerName,
        string $connectionId,
        array $config = []
    ): array {
        $trigger = $this->connectorRegistry->getTrigger($connectorName, $triggerName);

        if (!$trigger) {
            throw new \InvalidArgumentException(
                "Trigger not found: {$connectorName}.{$triggerName}"
            );
        }

        if (!$trigger->canTest()) {
            return [
                'success' => false,
                'error' => 'This trigger does not support manual testing',
            ];
        }

        // For polling triggers, do one poll
        if ($trigger->getType() === 'polling') {
            $credentials = $this->credentialVault->retrieve($connectionId);
            $result = $trigger->poll($credentials, $config, []);

            return [
                'success' => true,
                'items' => $result['items'],
                'sample' => $result['items'][0] ?? $trigger->getSampleOutput(),
            ];
        }

        // For webhook triggers, return sample
        return [
            'success' => true,
            'items' => [],
            'sample' => $trigger->getSampleOutput(),
        ];
    }
}
