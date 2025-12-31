<?php

declare(strict_types=1);

namespace Tests\Feature\Enterprise;

use App\Models\Enterprise\WebhookEndpoint;
use App\Models\Enterprise\WebhookDelivery;
use App\Services\Enterprise\WebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WebhookServiceTest extends TestCase
{
    use RefreshDatabase;

    protected WebhookService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(WebhookService::class);
    }

    public function test_create_endpoint(): void
    {
        $endpoint = $this->service->createEndpoint(1, [
            'url' => 'https://example.com/webhook',
            'events' => ['order.created', 'order.updated'],
        ]);

        $this->assertDatabaseHas('webhook_endpoints', [
            'id' => $endpoint->id,
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'status' => 'active',
        ]);

        $this->assertNotNull($endpoint->secret);
        $this->assertEquals(64, strlen($endpoint->secret));
    }

    public function test_dispatch_queues_deliveries(): void
    {
        WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['order.created'],
            'status' => 'active',
        ]);

        $count = $this->service->dispatch(1, 'order.created', ['id' => 123]);

        $this->assertEquals(1, $count);
        $this->assertDatabaseHas('webhook_deliveries', [
            'event' => 'order.created',
            'status' => 'pending',
        ]);
    }

    public function test_dispatch_skips_unsubscribed_events(): void
    {
        WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['order.created'],
            'status' => 'active',
        ]);

        $count = $this->service->dispatch(1, 'payment.completed', ['id' => 123]);

        $this->assertEquals(0, $count);
    }

    public function test_wildcard_subscription(): void
    {
        WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['order.*'],
            'status' => 'active',
        ]);

        $count1 = $this->service->dispatch(1, 'order.created', []);
        $count2 = $this->service->dispatch(1, 'order.updated', []);
        $count3 = $this->service->dispatch(1, 'payment.completed', []);

        $this->assertEquals(1, $count1);
        $this->assertEquals(1, $count2);
        $this->assertEquals(0, $count3);
    }

    public function test_deliver_success(): void
    {
        Http::fake([
            'example.com/*' => Http::response(['success' => true], 200),
        ]);

        $endpoint = WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['test'],
            'status' => 'active',
        ]);

        $delivery = WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => ['data' => 'test'],
            'status' => 'pending',
        ]);

        $result = $this->service->deliver($delivery);

        $this->assertTrue($result);

        $delivery->refresh();
        $this->assertEquals('delivered', $delivery->status);
        $this->assertEquals(200, $delivery->http_status);
        $this->assertNotNull($delivery->delivered_at);
    }

    public function test_deliver_failure_triggers_retry(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Server Error', 500),
        ]);

        $endpoint = WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['test'],
            'status' => 'active',
            'retry_count' => 3,
        ]);

        $delivery = WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => ['data' => 'test'],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        $result = $this->service->deliver($delivery);

        $this->assertFalse($result);

        $delivery->refresh();
        $this->assertEquals('retrying', $delivery->status);
        $this->assertEquals(1, $delivery->attempts);
        $this->assertNotNull($delivery->next_retry_at);
    }

    public function test_deliver_marks_failed_after_max_retries(): void
    {
        Http::fake([
            'example.com/*' => Http::response('Server Error', 500),
        ]);

        $endpoint = WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['test'],
            'status' => 'active',
            'retry_count' => 3,
        ]);

        $delivery = WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => ['data' => 'test'],
            'status' => 'retrying',
            'attempts' => 3, // Already at max
        ]);

        $result = $this->service->deliver($delivery);

        $this->assertFalse($result);

        $delivery->refresh();
        $this->assertEquals('failed', $delivery->status);
    }

    public function test_endpoint_stats(): void
    {
        $endpoint = WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['test'],
            'status' => 'active',
        ]);

        WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => [],
            'status' => 'delivered',
            'response_time_ms' => 150,
        ]);

        WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => [],
            'status' => 'delivered',
            'response_time_ms' => 250,
        ]);

        WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'event' => 'test',
            'payload' => [],
            'status' => 'failed',
        ]);

        $stats = $this->service->getEndpointStats($endpoint);

        $this->assertEquals(3, $stats['total_deliveries']);
        $this->assertEquals(2, $stats['delivered']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(66.67, $stats['success_rate']);
        $this->assertEquals(200, $stats['avg_response_time_ms']);
    }

    public function test_verify_signature(): void
    {
        $payload = '{"event":"test"}';
        $secret = 'test_secret_key';

        $signature = 'sha256=' . hash_hmac('sha256', $payload, $secret);

        $this->assertTrue($this->service->verifySignature($payload, $signature, $secret));
        $this->assertFalse($this->service->verifySignature($payload, 'invalid', $secret));
    }

    public function test_get_available_events(): void
    {
        $events = $this->service->getAvailableEvents();

        $this->assertArrayHasKey('order.created', $events);
        $this->assertArrayHasKey('payment.completed', $events);
        $this->assertArrayHasKey('plugin.installed', $events);
    }

    public function test_endpoint_auto_disables_after_failures(): void
    {
        $endpoint = WebhookEndpoint::create([
            'tenant_id' => 1,
            'url' => 'https://example.com/webhook',
            'events' => ['test'],
            'status' => 'active',
            'consecutive_failures' => 9,
        ]);

        $endpoint->recordFailure();

        $endpoint->refresh();
        $this->assertEquals('disabled', $endpoint->status);
        $this->assertEquals(10, $endpoint->consecutive_failures);
    }
}
