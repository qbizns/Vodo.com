<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use App\Models\Enterprise\WebhookDelivery;
use App\Models\Enterprise\WebhookEndpoint;
use App\Services\Enterprise\WebhookService;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Events\CommerceEventRegistry;
use VodoCommerce\Events\CommerceEvents;
use VodoCommerce\Models\Store;

/**
 * CommerceWebhookBridge - Bridges commerce events to the platform's webhook delivery system.
 *
 * Provides:
 * - Automatic dispatch of commerce events to WebhookService
 * - Delivery tracking and statistics per store
 * - Event filtering and wildcard subscription support
 * - Retry handling via platform's WebhookDelivery
 */
class CommerceWebhookBridge
{
    public function __construct(
        protected WebhookService $webhookService
    ) {
    }

    /**
     * Register commerce event listeners with the HookManager.
     *
     * This should be called during plugin boot.
     */
    public function registerEventListeners(): void
    {
        // Register all commerce action events
        foreach (CommerceEvents::getAllActions() as $event) {
            add_action($event, [$this, 'handleEvent'], 10, 10);
        }

        Log::debug('Commerce webhook bridge: Registered event listeners', [
            'event_count' => count(CommerceEvents::getAllActions()),
        ]);
    }

    /**
     * Handle a commerce event and dispatch to webhooks.
     *
     * @param mixed ...$args Event arguments (first is typically the main entity)
     */
    public function handleEvent(...$args): void
    {
        // Get the current action name
        $event = current_action();

        if (!$event) {
            return;
        }

        try {
            $payload = $this->buildPayload($event, $args);
            $storeId = $this->extractStoreId($args);

            if (!$storeId) {
                Log::warning('Commerce webhook: Could not determine store ID', ['event' => $event]);
                return;
            }

            $this->dispatch($storeId, $event, $payload);
        } catch (\Throwable $e) {
            Log::error('Commerce webhook dispatch failed', [
                'event' => $event,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Dispatch an event to subscribed webhooks.
     */
    public function dispatch(int $storeId, string $event, array $payload): int
    {
        $store = Store::find($storeId);
        if (!$store || !$store->tenant_id) {
            return 0;
        }

        // Enrich payload with store context
        $payload = array_merge($payload, [
            'store_id' => $storeId,
            'store_slug' => $store->slug ?? null,
            'occurred_at' => now()->toIso8601String(),
        ]);

        $count = $this->webhookService->dispatch($store->tenant_id, $event, $payload);

        if ($count > 0) {
            Log::info('Commerce webhook dispatched', [
                'event' => $event,
                'store_id' => $storeId,
                'endpoint_count' => $count,
            ]);
        }

        return $count;
    }

    /**
     * Get available commerce webhook events for subscription UI.
     *
     * @return array<string, array>
     */
    public function getAvailableEvents(): array
    {
        CommerceEventRegistry::initialize();

        $events = [];
        $categories = [];

        foreach (CommerceEventRegistry::all() as $eventData) {
            $event = $eventData['event'];
            $category = $eventData['category'];

            // Add wildcard for category if not already
            if (!isset($categories[$category])) {
                $categories[$category] = true;
                $events["commerce.{$category}.*"] = [
                    'event' => "commerce.{$category}.*",
                    'description' => "All {$category} events",
                    'category' => $category,
                ];
            }

            $events[$event] = [
                'event' => $event,
                'description' => $eventData['description'],
                'category' => $category,
            ];
        }

        // Add global wildcard
        $events = array_merge([
            'commerce.*' => [
                'event' => 'commerce.*',
                'description' => 'All commerce events',
                'category' => 'all',
            ],
        ], $events);

        return $events;
    }

    /**
     * Get delivery statistics for a store's webhooks.
     */
    public function getStoreStats(int $storeId, int $days = 30): array
    {
        $store = Store::find($storeId);
        if (!$store || !$store->tenant_id) {
            return [];
        }

        $endpoints = WebhookEndpoint::byTenant($store->tenant_id)
            ->get()
            ->filter(fn($e) => $this->subscribesToCommerceEvents($e));

        $stats = [
            'endpoints' => $endpoints->count(),
            'active_endpoints' => $endpoints->where('status', 'active')->count(),
            'events_dispatched' => 0,
            'deliveries' => [
                'total' => 0,
                'delivered' => 0,
                'failed' => 0,
                'pending' => 0,
            ],
            'by_endpoint' => [],
        ];

        foreach ($endpoints as $endpoint) {
            $endpointStats = $this->webhookService->getEndpointStats($endpoint);
            $stats['events_dispatched'] += $endpointStats['total_deliveries'];
            $stats['deliveries']['total'] += $endpointStats['total_deliveries'];
            $stats['deliveries']['delivered'] += $endpointStats['delivered'];
            $stats['deliveries']['failed'] += $endpointStats['failed'];
            $stats['deliveries']['pending'] += $endpointStats['pending'] + $endpointStats['retrying'];

            $stats['by_endpoint'][$endpoint->id] = [
                'url' => $endpoint->url,
                'status' => $endpoint->status,
                'success_rate' => $endpointStats['success_rate'],
                'last_success_at' => $endpointStats['last_success_at'],
                'consecutive_failures' => $endpointStats['consecutive_failures'],
            ];
        }

        return $stats;
    }

    /**
     * Create a webhook endpoint for a store.
     */
    public function createEndpoint(int $storeId, array $data): WebhookEndpoint
    {
        $store = Store::findOrFail($storeId);

        return $this->webhookService->createEndpoint($store->tenant_id, array_merge($data, [
            'metadata' => array_merge($data['metadata'] ?? [], [
                'store_id' => $storeId,
                'source' => 'commerce',
            ]),
        ]));
    }

    /**
     * Test a webhook endpoint.
     */
    public function testEndpoint(WebhookEndpoint $endpoint): array
    {
        return $this->webhookService->test($endpoint);
    }

    /**
     * Check if an endpoint subscribes to any commerce events.
     */
    protected function subscribesToCommerceEvents(WebhookEndpoint $endpoint): bool
    {
        $events = $endpoint->events ?? [];

        foreach ($events as $event) {
            if (str_starts_with($event, 'commerce.')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Build the webhook payload from event arguments.
     */
    protected function buildPayload(string $event, array $args): array
    {
        $payload = [];

        // Most events pass the main entity as the first argument
        if (!empty($args[0])) {
            $entity = $args[0];

            if (is_object($entity)) {
                // Convert model to array
                if (method_exists($entity, 'toArray')) {
                    $payload = $entity->toArray();
                } elseif ($entity instanceof \stdClass) {
                    $payload = (array) $entity;
                } else {
                    // Try to get public properties
                    $payload = get_object_vars($entity);
                }
            } elseif (is_array($entity)) {
                $payload = $entity;
            }
        }

        // Add remaining arguments with generic keys
        for ($i = 1; $i < count($args); $i++) {
            $arg = $args[$i];
            if (is_scalar($arg)) {
                $payload["arg_{$i}"] = $arg;
            } elseif (is_array($arg)) {
                $payload = array_merge($payload, $arg);
            }
        }

        // Remove sensitive fields
        unset(
            $payload['password'],
            $payload['secret'],
            $payload['api_key'],
            $payload['access_token'],
            $payload['card_number'],
            $payload['cvv'],
            $payload['token']
        );

        return $payload;
    }

    /**
     * Extract store ID from event arguments.
     */
    protected function extractStoreId(array $args): ?int
    {
        // Check first argument for store_id
        if (!empty($args[0])) {
            $entity = $args[0];

            if (is_object($entity)) {
                if (property_exists($entity, 'store_id') || (method_exists($entity, 'getAttribute') && $entity->getAttribute('store_id'))) {
                    return $entity->store_id;
                }

                // For Store model itself
                if ($entity instanceof Store) {
                    return $entity->id;
                }
            } elseif (is_array($entity) && isset($entity['store_id'])) {
                return (int) $entity['store_id'];
            }
        }

        // Check second argument (often Store is passed as second arg)
        if (!empty($args[1])) {
            if ($args[1] instanceof Store) {
                return $args[1]->id;
            }
            if (is_array($args[1]) && isset($args[1]['store_id'])) {
                return (int) $args[1]['store_id'];
            }
        }

        // Check for current store context
        $currentStoreId = Store::getCurrentStoreId();
        if ($currentStoreId) {
            return $currentStoreId;
        }

        return null;
    }
}
