<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Support\Collection;

/**
 * Contract for the Webhook Registry.
 *
 * Manages webhook endpoints, events, and delivery.
 */
interface WebhookRegistryContract
{
    /**
     * Register a webhook event type.
     *
     * @param string $event Event name (e.g., 'contact.created')
     * @param array $config Event configuration
     * @param string|null $pluginSlug Owner plugin
     * @return self
     */
    public function registerEvent(string $event, array $config = [], ?string $pluginSlug = null): self;

    /**
     * Get all registered events.
     *
     * @return Collection
     */
    public function getEvents(): Collection;

    /**
     * Create a webhook subscription.
     *
     * @param string $url Webhook URL
     * @param array $events Events to subscribe to
     * @param array $options Additional options (secret, headers, etc.)
     * @return array Webhook subscription
     */
    public function subscribe(string $url, array $events, array $options = []): array;

    /**
     * Update a webhook subscription.
     *
     * @param string $id Subscription ID
     * @param array $data Update data
     * @return array Updated subscription
     */
    public function update(string $id, array $data): array;

    /**
     * Delete a webhook subscription.
     *
     * @param string $id Subscription ID
     * @return bool
     */
    public function unsubscribe(string $id): bool;

    /**
     * Get all subscriptions.
     *
     * @param string|null $event Filter by event
     * @return Collection
     */
    public function getSubscriptions(?string $event = null): Collection;

    /**
     * Dispatch a webhook event.
     *
     * @param string $event Event name
     * @param array $payload Event payload
     * @return int Number of webhooks dispatched
     */
    public function dispatch(string $event, array $payload): int;

    /**
     * Get webhook delivery logs.
     *
     * @param string|null $subscriptionId Filter by subscription
     * @param int $limit Limit results
     * @return Collection
     */
    public function getLogs(?string $subscriptionId = null, int $limit = 50): Collection;

    /**
     * Retry a failed webhook delivery.
     *
     * @param string $logId Delivery log ID
     * @return bool
     */
    public function retry(string $logId): bool;

    /**
     * Test a webhook endpoint.
     *
     * @param string $url URL to test
     * @param array $payload Test payload
     * @return array Test result
     */
    public function test(string $url, array $payload = []): array;
}
