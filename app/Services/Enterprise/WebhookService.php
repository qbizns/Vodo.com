<?php

declare(strict_types=1);

namespace App\Services\Enterprise;

use App\Models\Enterprise\WebhookEndpoint;
use App\Models\Enterprise\WebhookDelivery;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Webhook Service
 *
 * Manages webhook endpoints and delivery with retry logic.
 */
class WebhookService
{
    /**
     * Dispatch an event to all subscribed webhooks.
     */
    public function dispatch(int $tenantId, string $event, array $payload): int
    {
        $endpoints = WebhookEndpoint::byTenant($tenantId)
            ->active()
            ->get()
            ->filter(fn($e) => $e->subscribesTo($event));

        $count = 0;

        foreach ($endpoints as $endpoint) {
            $this->queueDelivery($endpoint, $event, $payload);
            $count++;
        }

        return $count;
    }

    /**
     * Queue a webhook delivery.
     */
    public function queueDelivery(WebhookEndpoint $endpoint, string $event, array $payload): WebhookDelivery
    {
        $delivery = WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => $event,
            'payload' => $this->buildPayload($endpoint, $event, $payload),
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // Dispatch job for async delivery
        // In production, this would be: dispatch(new DeliverWebhook($delivery));
        // For now, we'll process synchronously or via scheduler

        return $delivery;
    }

    /**
     * Deliver a webhook.
     */
    public function deliver(WebhookDelivery $delivery): bool
    {
        $endpoint = $delivery->endpoint;

        if (!$endpoint->isActive()) {
            $delivery->markAsFailed('Endpoint is not active');
            return false;
        }

        $payload = $delivery->payload;
        $signature = $this->generateSignature($payload, $endpoint->secret);

        $headers = array_merge(
            $endpoint->headers ?? [],
            [
                'Content-Type' => 'application/json',
                'X-Webhook-Signature' => $signature,
                'X-Webhook-Event' => $delivery->event,
                'X-Webhook-Delivery' => $delivery->uuid,
                'X-Webhook-Version' => $endpoint->version,
            ]
        );

        $startTime = microtime(true);

        try {
            $response = Http::timeout($endpoint->timeout_seconds)
                ->withHeaders($headers)
                ->post($endpoint->url, $payload);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->markAsDelivered(
                    $response->status(),
                    $response->body(),
                    $responseTime
                );

                Log::info('Webhook delivered', [
                    'delivery_id' => $delivery->id,
                    'endpoint_id' => $endpoint->id,
                    'event' => $delivery->event,
                    'status' => $response->status(),
                    'time_ms' => $responseTime,
                ]);

                return true;
            }

            $delivery->markAsFailed(
                "HTTP {$response->status()}: " . Str::limit($response->body(), 500),
                $response->status()
            );

            return false;
        } catch (\Throwable $e) {
            $delivery->markAsFailed($e->getMessage());

            Log::warning('Webhook delivery failed', [
                'delivery_id' => $delivery->id,
                'endpoint_id' => $endpoint->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Process pending and retry deliveries.
     */
    public function processPending(): array
    {
        $results = ['delivered' => 0, 'failed' => 0, 'retrying' => 0];

        // Process pending deliveries
        $pending = WebhookDelivery::pending()
            ->with('endpoint')
            ->limit(100)
            ->get();

        foreach ($pending as $delivery) {
            if ($this->deliver($delivery)) {
                $results['delivered']++;
            } else {
                $delivery->refresh();
                if ($delivery->isRetrying()) {
                    $results['retrying']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        // Process retries
        $retries = WebhookDelivery::retrying()
            ->with('endpoint')
            ->limit(100)
            ->get();

        foreach ($retries as $delivery) {
            if ($this->deliver($delivery)) {
                $results['delivered']++;
            } else {
                $delivery->refresh();
                if ($delivery->isRetrying()) {
                    $results['retrying']++;
                } else {
                    $results['failed']++;
                }
            }
        }

        return $results;
    }

    /**
     * Create a webhook endpoint.
     */
    public function createEndpoint(int $tenantId, array $data): WebhookEndpoint
    {
        return WebhookEndpoint::create([
            'tenant_id' => $tenantId,
            'url' => $data['url'],
            'events' => $data['events'],
            'status' => 'active',
            'version' => $data['version'] ?? 'v1',
            'timeout_seconds' => $data['timeout_seconds'] ?? 30,
            'retry_count' => $data['retry_count'] ?? 3,
            'headers' => $data['headers'] ?? null,
            'metadata' => $data['metadata'] ?? null,
        ]);
    }

    /**
     * Test a webhook endpoint.
     */
    public function test(WebhookEndpoint $endpoint): array
    {
        $payload = $this->buildPayload($endpoint, 'webhook.test', [
            'message' => 'This is a test webhook delivery',
            'timestamp' => now()->toIso8601String(),
        ]);

        $signature = $this->generateSignature($payload, $endpoint->secret);

        try {
            $startTime = microtime(true);

            $response = Http::timeout(10)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $signature,
                    'X-Webhook-Event' => 'webhook.test',
                    'X-Webhook-Version' => $endpoint->version,
                ])
                ->post($endpoint->url, $payload);

            $responseTime = (int) ((microtime(true) - $startTime) * 1000);

            return [
                'success' => $response->successful(),
                'status' => $response->status(),
                'response_time_ms' => $responseTime,
                'body' => Str::limit($response->body(), 1000),
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get delivery statistics for an endpoint.
     */
    public function getEndpointStats(WebhookEndpoint $endpoint): array
    {
        $deliveries = $endpoint->deliveries();

        $total = $deliveries->count();
        $delivered = $deliveries->where('status', 'delivered')->count();
        $failed = $deliveries->where('status', 'failed')->count();
        $avgResponseTime = $deliveries->where('status', 'delivered')->avg('response_time_ms');

        return [
            'total_deliveries' => $total,
            'delivered' => $delivered,
            'failed' => $failed,
            'pending' => $deliveries->where('status', 'pending')->count(),
            'retrying' => $deliveries->where('status', 'retrying')->count(),
            'success_rate' => $total > 0 ? round(($delivered / $total) * 100, 2) : 0,
            'avg_response_time_ms' => $avgResponseTime ? round($avgResponseTime, 2) : null,
            'consecutive_failures' => $endpoint->consecutive_failures,
            'last_success_at' => $endpoint->last_success_at?->toIso8601String(),
            'last_failure_at' => $endpoint->last_failure_at?->toIso8601String(),
        ];
    }

    /**
     * Get available webhook events.
     */
    public function getAvailableEvents(): array
    {
        return [
            'order.*' => 'All order events',
            'order.created' => 'Order created',
            'order.updated' => 'Order updated',
            'order.completed' => 'Order completed',
            'order.cancelled' => 'Order cancelled',
            'payment.*' => 'All payment events',
            'payment.completed' => 'Payment completed',
            'payment.failed' => 'Payment failed',
            'payment.refunded' => 'Payment refunded',
            'invoice.*' => 'All invoice events',
            'invoice.created' => 'Invoice created',
            'invoice.paid' => 'Invoice paid',
            'plugin.*' => 'All plugin events',
            'plugin.installed' => 'Plugin installed',
            'plugin.uninstalled' => 'Plugin uninstalled',
            'plugin.updated' => 'Plugin updated',
            'user.*' => 'All user events',
            'user.created' => 'User created',
            'user.updated' => 'User updated',
            'entity.*' => 'All entity events',
            'entity.created' => 'Entity created',
            'entity.updated' => 'Entity updated',
            'entity.deleted' => 'Entity deleted',
        ];
    }

    /**
     * Cleanup old deliveries.
     */
    public function cleanup(int $retentionDays = 30): int
    {
        return WebhookDelivery::where('created_at', '<', now()->subDays($retentionDays))
            ->whereIn('status', ['delivered', 'failed'])
            ->delete();
    }

    /**
     * Build the webhook payload.
     */
    protected function buildPayload(WebhookEndpoint $endpoint, string $event, array $data): array
    {
        return [
            'id' => Str::uuid()->toString(),
            'event' => $event,
            'created_at' => now()->toIso8601String(),
            'api_version' => $endpoint->version,
            'data' => $data,
        ];
    }

    /**
     * Generate HMAC signature.
     */
    protected function generateSignature(array $payload, string $secret): string
    {
        $payloadString = json_encode($payload);
        return 'sha256=' . hash_hmac('sha256', $payloadString, $secret);
    }

    /**
     * Verify a webhook signature.
     */
    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}
