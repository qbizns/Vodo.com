<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;
use StripeGateway\Services\StripePaymentGateway;
use StripeGateway\StripeGatewayPlugin;
use VodoCommerce\Models\Store;

class StripeGatewayTest extends TestCase
{
    protected StripePaymentGateway $gateway;
    protected StripeGatewayPlugin $plugin;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the plugin with test settings
        $this->plugin = $this->createMock(StripeGatewayPlugin::class);
        $this->plugin->method('isConfiguredForStore')->willReturn(true);
        $this->plugin->method('getStoreSettings')->willReturn([
            'publishable_key' => 'pk_test_123',
            'secret_key' => 'sk_test_456',
            'webhook_secret' => 'whsec_789',
            'payment_mode' => 'payment',
            'capture_method' => 'automatic',
        ]);

        $this->gateway = new StripePaymentGateway($this->plugin);

        // Set store context
        Store::setCurrentStoreId(1);
    }

    protected function tearDown(): void
    {
        Store::setCurrentStoreId(null);
        parent::tearDown();
    }

    public function test_gateway_has_correct_identifier(): void
    {
        $this->assertEquals('stripe', $this->gateway->getIdentifier());
    }

    public function test_gateway_has_correct_name(): void
    {
        $this->assertEquals('Stripe', $this->gateway->getName());
    }

    public function test_gateway_supports_expected_features(): void
    {
        $supports = $this->gateway->supports();

        $this->assertContains('checkout', $supports);
        $this->assertContains('refund', $supports);
        $this->assertContains('webhook', $supports);
    }

    public function test_gateway_is_enabled_when_configured(): void
    {
        $this->assertTrue($this->gateway->isEnabled());
    }

    public function test_create_checkout_session_makes_correct_api_call(): void
    {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_abc123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_abc123',
                'expires_at' => time() + 1800,
            ], 200),
        ]);

        $session = $this->gateway->createCheckoutSession(
            orderId: 'order-123',
            amount: 99.99,
            currency: 'USD',
            items: [
                ['name' => 'Test Product', 'quantity' => 1, 'unit_price' => 99.99],
            ],
            customerEmail: 'test@example.com',
            metadata: ['order_number' => 'ORD-123']
        );

        $this->assertEquals('cs_test_abc123', $session->sessionId);
        $this->assertStringContainsString('checkout.stripe.com', $session->redirectUrl);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.stripe.com/v1/checkout/sessions'
                && $request->method() === 'POST';
        });
    }

    public function test_handles_checkout_completed_webhook(): void
    {
        $payload = [
            'id' => 'evt_123',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'id' => 'cs_test_abc123',
                    'payment_intent' => 'pi_123',
                    'payment_status' => 'paid',
                    'metadata' => [
                        'order_id' => '456',
                        'store_id' => '1',
                    ],
                ],
            ],
        ];

        $timestamp = time();
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', "{$timestamp}.{$payloadString}", 'whsec_789');
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $result = $this->gateway->handleWebhook($payload, [
            'stripe-signature' => $signatureHeader,
        ]);

        $this->assertTrue($result->processed);
        $this->assertEquals('456', $result->orderId);
        $this->assertEquals('paid', $result->paymentStatus);
        $this->assertEquals('pi_123', $result->transactionId);
    }

    public function test_handles_payment_failed_webhook(): void
    {
        $payload = [
            'id' => 'evt_124',
            'type' => 'payment_intent.payment_failed',
            'data' => [
                'object' => [
                    'id' => 'pi_124',
                    'metadata' => [
                        'order_id' => '789',
                        'store_id' => '1',
                    ],
                    'last_payment_error' => [
                        'message' => 'Your card was declined.',
                    ],
                ],
            ],
        ];

        $timestamp = time();
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', "{$timestamp}.{$payloadString}", 'whsec_789');
        $signatureHeader = "t={$timestamp},v1={$signature}";

        $result = $this->gateway->handleWebhook($payload, [
            'stripe-signature' => $signatureHeader,
        ]);

        $this->assertTrue($result->processed);
        $this->assertEquals('789', $result->orderId);
        $this->assertEquals('failed', $result->paymentStatus);
        $this->assertStringContainsString('declined', $result->message);
    }

    public function test_rejects_invalid_webhook_signature(): void
    {
        $payload = [
            'id' => 'evt_125',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'metadata' => ['store_id' => '1'],
                ],
            ],
        ];

        $result = $this->gateway->handleWebhook($payload, [
            'stripe-signature' => 't=123,v1=invalidsignature',
        ]);

        $this->assertFalse($result->processed);
        $this->assertStringContainsString('Invalid signature', $result->message);
    }

    public function test_rejects_expired_webhook_timestamp(): void
    {
        $payload = [
            'id' => 'evt_126',
            'type' => 'checkout.session.completed',
            'data' => [
                'object' => [
                    'metadata' => ['store_id' => '1'],
                ],
            ],
        ];

        $oldTimestamp = time() - 400; // 6+ minutes ago
        $payloadString = json_encode($payload);
        $signature = hash_hmac('sha256', "{$oldTimestamp}.{$payloadString}", 'whsec_789');
        $signatureHeader = "t={$oldTimestamp},v1={$signature}";

        $result = $this->gateway->handleWebhook($payload, [
            'stripe-signature' => $signatureHeader,
        ]);

        $this->assertFalse($result->processed);
    }

    public function test_refund_makes_correct_api_call(): void
    {
        Http::fake([
            'api.stripe.com/v1/refunds' => Http::response([
                'id' => 're_123',
                'amount' => 5000,
                'currency' => 'usd',
                'status' => 'succeeded',
            ], 200),
        ]);

        $result = $this->gateway->refund(
            transactionId: 'pi_123',
            amount: 50.00,
            reason: 'Customer request'
        );

        $this->assertEquals('re_123', $result->refundId);
        $this->assertEquals(50.0, $result->amount);
        $this->assertEquals('succeeded', $result->status);

        Http::assertSent(function ($request) {
            return str_contains($request->url(), 'refunds');
        });
    }

    public function test_handles_api_error_gracefully(): void
    {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'error' => [
                    'type' => 'invalid_request_error',
                    'message' => 'Invalid API key provided',
                ],
            ], 401),
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Invalid API key provided');

        $this->gateway->createCheckoutSession(
            orderId: 'order-123',
            amount: 99.99,
            currency: 'USD',
            items: [],
            customerEmail: 'test@example.com',
            metadata: []
        );
    }

    public function test_formats_line_items_correctly(): void
    {
        Http::fake([
            'api.stripe.com/v1/checkout/sessions' => Http::response([
                'id' => 'cs_test_123',
                'url' => 'https://checkout.stripe.com/pay/cs_test_123',
            ], 200),
        ]);

        $this->gateway->createCheckoutSession(
            orderId: 'order-123',
            amount: 149.97,
            currency: 'USD',
            items: [
                ['name' => 'Product A', 'quantity' => 2, 'unit_price' => 49.99],
                ['name' => 'Product B', 'quantity' => 1, 'unit_price' => 49.99],
            ],
            customerEmail: 'test@example.com',
            metadata: []
        );

        Http::assertSent(function ($request) {
            $body = $request->body();
            // Check that line items are properly formatted
            return str_contains($body, 'line_items')
                && str_contains($body, 'Product+A')
                && str_contains($body, 'Product+B');
        });
    }
}
