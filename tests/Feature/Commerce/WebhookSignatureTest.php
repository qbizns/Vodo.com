<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;
use VodoCommerce\Http\Middleware\VerifyWebhookSignature;

class WebhookSignatureTest extends TestCase
{
    protected VerifyWebhookSignature $middleware;

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyWebhookSignature();
    }

    public function test_rejects_request_without_signature_header(): void
    {
        $request = Request::create('/webhooks/payment/stripe', 'POST', [], [], [], [], json_encode(['event' => 'payment.completed']));
        $request->headers->set('Content-Type', 'application/json');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'gateway');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Missing webhook signature', $response->getContent());
    }

    public function test_rejects_invalid_signature_format(): void
    {
        $request = Request::create('/webhooks/payment/stripe', 'POST', [], [], [], [], json_encode(['event' => 'payment.completed']));
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', 'invalid-signature-format');

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'gateway');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid signature format', $response->getContent());
    }

    public function test_rejects_expired_timestamp(): void
    {
        $secret = 'test-webhook-secret';
        $payload = json_encode(['event' => 'payment.completed']);
        $timestamp = time() - 400; // 6+ minutes ago (past 5 min window)
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create('/webhooks/payment/stripe', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        // Mock the secret retrieval
        app()->instance('webhook_secret_resolver', fn() => $secret);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'gateway');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('expired', strtolower($response->getContent()));
    }

    public function test_rejects_invalid_signature(): void
    {
        $payload = json_encode(['event' => 'payment.completed']);
        $timestamp = time();
        $signatureHeader = "t={$timestamp},v1=invalidsignature123456";

        $request = Request::create('/webhooks/payment/stripe', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        $response = $this->middleware->handle($request, fn() => new Response('OK'), 'gateway');

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertStringContainsString('Invalid webhook signature', $response->getContent());
    }

    public function test_accepts_valid_signature(): void
    {
        $secret = 'test-webhook-secret';
        $payload = json_encode(['event' => 'payment.completed']);
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create('/webhooks/payment/stripe', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        // Create a mock that returns our secret
        $middleware = new class extends VerifyWebhookSignature {
            protected function resolveSecret(Request $request, string $source): ?string
            {
                return 'test-webhook-secret';
            }
        };

        $response = $middleware->handle($request, fn() => new Response('OK'), 'gateway');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($request->attributes->get('webhook_verified'));
    }

    public function test_sets_webhook_verified_attribute_on_success(): void
    {
        $secret = 'my-secret-key';
        $payload = json_encode(['order_id' => 123]);
        $timestamp = time();
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create('/webhooks/test', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        $middleware = new class extends VerifyWebhookSignature {
            protected function resolveSecret(Request $request, string $source): ?string
            {
                return 'my-secret-key';
            }
        };

        $middleware->handle($request, fn() => new Response('OK'), 'store');

        $this->assertTrue($request->attributes->get('webhook_verified'));
        $this->assertEquals($timestamp, $request->attributes->get('webhook_timestamp'));
    }

    public function test_handles_future_timestamp_within_tolerance(): void
    {
        $secret = 'test-secret';
        $payload = json_encode(['test' => true]);
        $timestamp = time() + 30; // 30 seconds in future (within tolerance)
        $signedPayload = "{$timestamp}.{$payload}";
        $signature = hash_hmac('sha256', $signedPayload, $secret);
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $request = Request::create('/webhooks/test', 'POST', [], [], [], [], $payload);
        $request->headers->set('Content-Type', 'application/json');
        $request->headers->set('X-Webhook-Signature', $signatureHeader);

        $middleware = new class extends VerifyWebhookSignature {
            protected function resolveSecret(Request $request, string $source): ?string
            {
                return 'test-secret';
            }
        };

        $response = $middleware->handle($request, fn() => new Response('OK'), 'gateway');

        $this->assertEquals(200, $response->getStatusCode());
    }
}
