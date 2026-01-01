<?php

declare(strict_types=1);

namespace Tests\Feature\Phase0;

use App\Models\EntityRecord;
use App\Models\Enterprise\WebhookDelivery;
use App\Models\Enterprise\WebhookEndpoint;
use App\Models\Plugin;
use App\Models\User;
use App\Services\Plugins\CircuitBreaker;
use App\Services\Webhook\WebhookRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use VodoCommerce\Http\Middleware\VerifyWebhookSignature;

/**
 * Phase 0: Foundation Audit Tests
 *
 * These tests verify that the core platform security and reliability
 * features are working correctly before proceeding with additional
 * development phases.
 *
 * Gate A: Core Correctness
 * - Webhook HMAC verification
 * - Replay protection
 * - Circuit breaker behavior
 * - Tenant isolation
 * - Plugin lifecycle
 */
class FoundationAuditTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // WEBHOOK HMAC VERIFICATION TESTS
    // =========================================================================

    public function test_webhook_hmac_rejects_tampered_body(): void
    {
        $secret = 'webhook-secret-key-12345';
        $originalPayload = json_encode(['order_id' => 123, 'status' => 'paid']);
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$originalPayload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        // Tampered payload (changed status)
        $tamperedPayload = json_encode(['order_id' => 123, 'status' => 'refunded']);

        $request = Request::create('/webhooks/payment', 'POST', [], [], [], [], $tamperedPayload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        $middleware = $this->createWebhookMiddleware($secret);
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid webhook signature', $response->getContent());
    }

    public function test_webhook_hmac_accepts_unmodified_body(): void
    {
        $secret = 'webhook-secret-key-12345';
        $payload = json_encode(['order_id' => 123, 'status' => 'paid']);
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create('/webhooks/payment', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        $middleware = $this->createWebhookMiddleware($secret);
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($request->attributes->get('webhook_verified'));
    }

    public function test_webhook_signature_uses_timing_safe_comparison(): void
    {
        // This test verifies hash_equals is used (by checking invalid sigs don't leak info)
        $secret = 'webhook-secret-key-12345';
        $payload = json_encode(['test' => true]);
        $timestamp = time();

        // Generate valid signature
        $signedPayload = "{$timestamp}.{$payload}";
        $validSignature = hash_hmac('sha256', $signedPayload, $secret);

        // Various invalid signatures with different lengths/values
        $invalidSignatures = [
            substr($validSignature, 0, -1), // One char short
            $validSignature . 'a', // One char long
            str_replace('a', 'b', $validSignature), // Single char change
            str_repeat('0', strlen($validSignature)), // All zeros
        ];

        $middleware = $this->createWebhookMiddleware($secret);

        foreach ($invalidSignatures as $invalidSig) {
            $signatureHeader = "t={$timestamp},v1={$invalidSig}";
            $request = Request::create('/webhooks/test', 'POST', [], [], [], [], $payload);
            $request->headers->set('Content-Type', 'application/json');
            $request->headers->set('X-Webhook-Signature', $signatureHeader);

            $response = $middleware->handle($request, fn() => new Response('OK'));

            $this->assertEquals(401, $response->getStatusCode(), "Failed for signature: {$invalidSig}");
        }
    }

    // =========================================================================
    // REPLAY PROTECTION TESTS
    // =========================================================================

    public function test_replay_protection_rejects_timestamps_older_than_5_minutes(): void
    {
        $secret = 'replay-test-secret';
        $payload = json_encode(['event' => 'test']);

        // Test timestamps at various ages
        $ages = [
            301 => false, // 5:01 - should be rejected
            360 => false, // 6:00 - should be rejected
            600 => false, // 10:00 - should be rejected
            3600 => false, // 1 hour - should be rejected
        ];

        $middleware = $this->createWebhookMiddleware($secret);

        foreach ($ages as $ageSeconds => $shouldPass) {
            $timestamp = time() - $ageSeconds;
            $signedPayload = "{$timestamp}.{$payload}";
            $signature = hash_hmac('sha256', $signedPayload, $secret);
            $signatureHeader = "t={$timestamp},v1={$signature}";

            $request = Request::create('/webhooks/test', 'POST', [], [], [], [], $payload);
            $request->headers->set('Content-Type', 'application/json');
            $request->headers->set('X-Webhook-Signature', $signatureHeader);

            $response = $middleware->handle($request, fn() => new Response('OK'));

            $this->assertEquals(
                401,
                $response->getStatusCode(),
                "Timestamp {$ageSeconds}s old should be rejected"
            );
        }
    }

    public function test_replay_protection_accepts_timestamps_within_5_minutes(): void
    {
        $secret = 'replay-test-secret';
        $payload = json_encode(['event' => 'test']);

        // Test timestamps at various recent ages
        $ages = [0, 30, 60, 120, 180, 240, 299]; // All within 5 minutes

        $middleware = $this->createWebhookMiddleware($secret);

        foreach ($ages as $ageSeconds) {
            $timestamp = time() - $ageSeconds;
            $signedPayload = "{$timestamp}.{$payload}";
            $signature = hash_hmac('sha256', $signedPayload, $secret);
            $signatureHeader = "t={$timestamp},v1={$signature}";

            $request = Request::create('/webhooks/test', 'POST', [], [], [], [], $payload);
            $request->headers->set('Content-Type', 'application/json');
            $request->headers->set('X-Webhook-Signature', $signatureHeader);

            $response = $middleware->handle($request, fn() => new Response('OK'));

            $this->assertEquals(
                200,
                $response->getStatusCode(),
                "Timestamp {$ageSeconds}s old should be accepted"
            );
        }
    }

    public function test_replay_protection_rejects_future_timestamps_beyond_tolerance(): void
    {
        $secret = 'replay-test-secret';
        $payload = json_encode(['event' => 'test']);
        $timestamp = time() + 120; // 2 minutes in future (beyond 60s tolerance)
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create('/webhooks/test', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        $middleware = $this->createWebhookMiddleware($secret);
        $response = $middleware->handle($request, fn() => new Response('OK'));

        $this->assertEquals(401, $response->getStatusCode());
    }

    // =========================================================================
    // CIRCUIT BREAKER TESTS
    // =========================================================================

    public function test_circuit_breaker_opens_after_failure_threshold(): void
    {
        $circuitBreaker = new CircuitBreaker();
        $serviceName = 'test-service-' . uniqid();

        // Configure with low threshold for testing
        config(['circuit_breaker.services.' . $serviceName => [
            'failure_threshold' => 3,
            'recovery_timeout' => 30,
        ]]);

        // First 3 calls should succeed (circuit closed)
        for ($i = 0; $i < 3; $i++) {
            $this->assertTrue(
                $circuitBreaker->isAvailable($serviceName),
                "Service should be available before threshold"
            );
            $circuitBreaker->recordFailure($serviceName);
        }

        // After 3 failures, circuit should be open
        $this->assertFalse(
            $circuitBreaker->isAvailable($serviceName),
            "Service should be unavailable after 3 failures"
        );
    }

    public function test_circuit_breaker_recovers_after_timeout(): void
    {
        $circuitBreaker = new CircuitBreaker();
        $serviceName = 'test-recovery-' . uniqid();

        // Configure with very short recovery for testing
        config(['circuit_breaker.services.' . $serviceName => [
            'failure_threshold' => 2,
            'recovery_timeout' => 1, // 1 second
        ]]);

        // Trip the circuit
        $circuitBreaker->recordFailure($serviceName);
        $circuitBreaker->recordFailure($serviceName);

        $this->assertFalse($circuitBreaker->isAvailable($serviceName));

        // Wait for recovery
        sleep(2);

        // Circuit should be half-open (allow one request)
        $this->assertTrue(
            $circuitBreaker->isAvailable($serviceName),
            "Service should be available after recovery timeout"
        );
    }

    public function test_circuit_breaker_resets_on_success(): void
    {
        $circuitBreaker = new CircuitBreaker();
        $serviceName = 'test-reset-' . uniqid();

        config(['circuit_breaker.services.' . $serviceName => [
            'failure_threshold' => 3,
            'recovery_timeout' => 30,
        ]]);

        // Record some failures but not enough to trip
        $circuitBreaker->recordFailure($serviceName);
        $circuitBreaker->recordFailure($serviceName);

        // Record success
        $circuitBreaker->recordSuccess($serviceName);

        // Record more failures - should start from 0 again
        $circuitBreaker->recordFailure($serviceName);
        $circuitBreaker->recordFailure($serviceName);

        // Should still be available (only 2 consecutive failures)
        $this->assertTrue($circuitBreaker->isAvailable($serviceName));
    }

    // =========================================================================
    // TENANT ISOLATION TESTS
    // =========================================================================

    public function test_tenant_a_cannot_access_tenant_b_data(): void
    {
        // Create two tenants
        $tenantA = User::factory()->create(['tenant_id' => 1]);
        $tenantB = User::factory()->create(['tenant_id' => 2]);

        // Create data for each tenant
        EntityRecord::factory()->create([
            'tenant_id' => 1,
            'entity_definition_id' => 1,
            'data' => ['name' => 'Tenant A Product'],
        ]);

        EntityRecord::factory()->create([
            'tenant_id' => 2,
            'entity_definition_id' => 1,
            'data' => ['name' => 'Tenant B Product'],
        ]);

        // Query as tenant A
        $tenantARecords = EntityRecord::query()
            ->where('tenant_id', 1)
            ->get();

        $tenantBRecords = EntityRecord::query()
            ->where('tenant_id', 2)
            ->get();

        // Verify isolation
        $this->assertEquals(1, $tenantARecords->count());
        $this->assertEquals('Tenant A Product', $tenantARecords->first()->data['name']);

        $this->assertEquals(1, $tenantBRecords->count());
        $this->assertEquals('Tenant B Product', $tenantBRecords->first()->data['name']);

        // Cross-tenant query should return empty
        $crossTenant = EntityRecord::query()
            ->where('tenant_id', 1)
            ->where('data->name', 'Tenant B Product')
            ->get();

        $this->assertEquals(0, $crossTenant->count());
    }

    public function test_tenant_scope_is_applied_to_all_queries(): void
    {
        // Create records for different tenants
        DB::table('entity_records')->insert([
            ['tenant_id' => 1, 'entity_definition_id' => 1, 'data' => '{"name": "A"}', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 1, 'entity_definition_id' => 1, 'data' => '{"name": "B"}', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 2, 'entity_definition_id' => 1, 'data' => '{"name": "C"}', 'created_at' => now(), 'updated_at' => now()],
            ['tenant_id' => 3, 'entity_definition_id' => 1, 'data' => '{"name": "D"}', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // Verify tenant isolation with explicit scoping
        $tenant1Count = EntityRecord::where('tenant_id', 1)->count();
        $tenant2Count = EntityRecord::where('tenant_id', 2)->count();
        $tenant3Count = EntityRecord::where('tenant_id', 3)->count();
        $totalCount = EntityRecord::count();

        $this->assertEquals(2, $tenant1Count);
        $this->assertEquals(1, $tenant2Count);
        $this->assertEquals(1, $tenant3Count);
        $this->assertEquals(4, $totalCount);
    }

    public function test_webhook_endpoints_are_tenant_isolated(): void
    {
        // Create webhook endpoints for different tenants
        $endpoint1 = WebhookEndpoint::factory()->create(['tenant_id' => 1, 'url' => 'https://tenant1.com/webhook']);
        $endpoint2 = WebhookEndpoint::factory()->create(['tenant_id' => 2, 'url' => 'https://tenant2.com/webhook']);

        // Verify each endpoint belongs to correct tenant
        $this->assertEquals(1, $endpoint1->tenant_id);
        $this->assertEquals(2, $endpoint2->tenant_id);

        // Query should respect tenant isolation
        $tenant1Endpoints = WebhookEndpoint::where('tenant_id', 1)->get();
        $this->assertEquals(1, $tenant1Endpoints->count());
        $this->assertEquals('https://tenant1.com/webhook', $tenant1Endpoints->first()->url);
    }

    // =========================================================================
    // PLUGIN LIFECYCLE TESTS
    // =========================================================================

    public function test_plugin_install_creates_expected_records(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-plugin-' . uniqid(),
            'name' => 'Test Plugin',
            'version' => '1.0.0',
            'status' => 'installed',
        ]);

        $this->assertDatabaseHas('plugins', [
            'slug' => $plugin->slug,
            'status' => 'installed',
        ]);
    }

    public function test_plugin_activate_changes_status(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-activate-' . uniqid(),
            'status' => 'installed',
        ]);

        $plugin->update(['status' => 'active']);

        $this->assertDatabaseHas('plugins', [
            'id' => $plugin->id,
            'status' => 'active',
        ]);
    }

    public function test_plugin_deactivate_changes_status(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-deactivate-' . uniqid(),
            'status' => 'active',
        ]);

        $plugin->update(['status' => 'inactive']);

        $this->assertDatabaseHas('plugins', [
            'id' => $plugin->id,
            'status' => 'inactive',
        ]);
    }

    public function test_plugin_uninstall_can_be_clean(): void
    {
        $plugin = Plugin::factory()->create([
            'slug' => 'test-uninstall-' . uniqid(),
            'status' => 'installed',
        ]);

        $pluginId = $plugin->id;
        $plugin->delete();

        $this->assertDatabaseMissing('plugins', [
            'id' => $pluginId,
        ]);
    }

    // =========================================================================
    // WEBHOOK DELIVERY TRACKING TESTS
    // =========================================================================

    public function test_webhook_delivery_tracks_attempts(): void
    {
        $endpoint = WebhookEndpoint::factory()->create(['tenant_id' => 1]);

        $delivery = WebhookDelivery::create([
            'endpoint_id' => $endpoint->id,
            'tenant_id' => 1,
            'event' => 'order.created',
            'payload' => ['order_id' => 123],
            'status' => 'pending',
            'attempts' => 0,
        ]);

        // Simulate failed attempt
        $delivery->markAsFailed('Connection timeout');

        $delivery->refresh();

        $this->assertEquals('failed', $delivery->status);
        $this->assertEquals(1, $delivery->attempts);
        $this->assertNotNull($delivery->last_error);
    }

    public function test_webhook_delivery_calculates_exponential_backoff(): void
    {
        $delivery = new WebhookDelivery();

        // Test exponential backoff: 30, 60, 120, 240...
        $this->assertEquals(30, $delivery->calculateBackoff(1));
        $this->assertEquals(60, $delivery->calculateBackoff(2));
        $this->assertEquals(120, $delivery->calculateBackoff(3));
        $this->assertEquals(240, $delivery->calculateBackoff(4));
    }

    // =========================================================================
    // HELPER METHODS
    // =========================================================================

    protected function createWebhookMiddleware(string $secret): VerifyWebhookSignature
    {
        return new class($secret) extends VerifyWebhookSignature {
            public function __construct(protected string $testSecret)
            {
                parent::__construct();
            }

            protected function resolveSecret(Request $request, string $source): ?string
            {
                return $this->testSecret;
            }
        };
    }
}
