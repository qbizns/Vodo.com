<?php

declare(strict_types=1);

namespace StripeGateway\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use StripeGateway\StripeGatewayPlugin;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Models\Store;

/**
 * Stripe Payment Gateway Implementation
 *
 * Implements the PaymentGatewayContract using Stripe Checkout Sessions.
 */
class StripePaymentGateway implements PaymentGatewayContract
{
    protected const API_BASE = 'https://api.stripe.com/v1';

    public function __construct(
        protected StripeGatewayPlugin $plugin
    ) {
    }

    public function getIdentifier(): string
    {
        return 'stripe';
    }

    public function getName(): string
    {
        return 'Stripe';
    }

    public function getIcon(): ?string
    {
        return '/plugins/stripe-gateway/assets/stripe-icon.svg';
    }

    public function isEnabled(): bool
    {
        $storeId = Store::getCurrentStoreId();
        if (!$storeId) {
            return false;
        }

        return $this->plugin->isConfiguredForStore($storeId);
    }

    public function supports(): array
    {
        return [
            'checkout',
            'refund',
            'partial_refund',
            'subscription',
            'webhook',
        ];
    }

    /**
     * Create a Stripe Checkout Session.
     */
    public function createCheckoutSession(
        string $orderId,
        float $amount,
        string $currency,
        array $items,
        string $customerEmail,
        array $metadata
    ): object {
        $storeId = Store::getCurrentStoreId();
        $settings = $this->getSettings($storeId);

        $lineItems = $this->formatLineItems($items, $currency);

        $successUrl = route('commerce.checkout.success', ['order' => $orderId]) . '?session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('commerce.checkout.show');

        $sessionData = [
            'payment_method_types' => ['card'],
            'mode' => $settings['payment_mode'] ?? 'payment',
            'customer_email' => $customerEmail,
            'line_items' => $lineItems,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => array_merge($metadata, [
                'order_id' => $orderId,
                'store_id' => $storeId,
            ]),
        ];

        // Add payment intent data for manual capture
        if (($settings['capture_method'] ?? 'automatic') === 'manual') {
            $sessionData['payment_intent_data'] = [
                'capture_method' => 'manual',
            ];
        }

        $response = $this->stripeRequest('POST', '/checkout/sessions', $sessionData, $settings['secret_key']);

        Log::info('Stripe checkout session created', [
            'session_id' => $response['id'],
            'order_id' => $orderId,
            'amount' => $amount,
        ]);

        return (object) [
            'sessionId' => $response['id'],
            'redirectUrl' => $response['url'],
            'clientSecret' => $response['client_secret'] ?? null,
            'expiresAt' => isset($response['expires_at'])
                ? \Carbon\Carbon::createFromTimestamp($response['expires_at'])
                : null,
        ];
    }

    /**
     * Handle incoming webhook from Stripe.
     */
    public function handleWebhook(array $payload, array $headers): object
    {
        $storeId = $this->extractStoreIdFromWebhook($payload);
        if (!$storeId) {
            return (object) [
                'processed' => false,
                'message' => 'Could not determine store ID',
            ];
        }

        $settings = $this->getSettings($storeId);

        // Verify webhook signature
        $signature = $headers['stripe-signature'] ?? $headers['Stripe-Signature'] ?? '';
        if (!$this->verifyWebhookSignature($payload, $signature, $settings['webhook_secret'] ?? '')) {
            Log::warning('Stripe webhook signature verification failed');
            return (object) [
                'processed' => false,
                'message' => 'Invalid signature',
            ];
        }

        $eventType = $payload['type'] ?? '';
        $eventData = $payload['data']['object'] ?? [];

        Log::info('Stripe webhook received', [
            'type' => $eventType,
            'id' => $payload['id'] ?? null,
        ]);

        return match ($eventType) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($eventData),
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($eventData),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($eventData),
            'charge.refunded' => $this->handleChargeRefunded($eventData),
            default => (object) [
                'processed' => true,
                'message' => 'Event type not handled: ' . $eventType,
            ],
        };
    }

    /**
     * Process a refund.
     */
    public function refund(string $transactionId, float $amount, ?string $reason = null): object
    {
        $storeId = Store::getCurrentStoreId();
        $settings = $this->getSettings($storeId);

        $refundData = [
            'payment_intent' => $transactionId,
            'amount' => (int) ($amount * 100), // Convert to cents
        ];

        if ($reason) {
            $refundData['reason'] = $this->mapRefundReason($reason);
        }

        $response = $this->stripeRequest('POST', '/refunds', $refundData, $settings['secret_key']);

        Log::info('Stripe refund processed', [
            'refund_id' => $response['id'],
            'payment_intent' => $transactionId,
            'amount' => $amount,
        ]);

        return (object) [
            'refundId' => $response['id'],
            'amount' => $response['amount'] / 100,
            'status' => $response['status'],
            'currency' => $response['currency'],
        ];
    }

    /**
     * Handle checkout.session.completed event.
     */
    protected function handleCheckoutCompleted(array $session): object
    {
        $orderId = $session['metadata']['order_id'] ?? null;
        $paymentIntentId = $session['payment_intent'] ?? null;

        if (!$orderId) {
            return (object) [
                'processed' => false,
                'message' => 'Missing order_id in metadata',
            ];
        }

        // Payment is successful
        return (object) [
            'processed' => true,
            'orderId' => $orderId,
            'paymentStatus' => $session['payment_status'] === 'paid' ? 'paid' : 'pending',
            'transactionId' => $paymentIntentId,
            'message' => 'Payment completed',
        ];
    }

    /**
     * Handle payment_intent.succeeded event.
     */
    protected function handlePaymentSucceeded(array $paymentIntent): object
    {
        $orderId = $paymentIntent['metadata']['order_id'] ?? null;

        return (object) [
            'processed' => true,
            'orderId' => $orderId,
            'paymentStatus' => 'paid',
            'transactionId' => $paymentIntent['id'],
            'message' => 'Payment succeeded',
        ];
    }

    /**
     * Handle payment_intent.payment_failed event.
     */
    protected function handlePaymentFailed(array $paymentIntent): object
    {
        $orderId = $paymentIntent['metadata']['order_id'] ?? null;
        $error = $paymentIntent['last_payment_error']['message'] ?? 'Payment failed';

        return (object) [
            'processed' => true,
            'orderId' => $orderId,
            'paymentStatus' => 'failed',
            'transactionId' => $paymentIntent['id'],
            'message' => $error,
        ];
    }

    /**
     * Handle charge.refunded event.
     */
    protected function handleChargeRefunded(array $charge): object
    {
        $paymentIntentId = $charge['payment_intent'] ?? null;
        $amountRefunded = ($charge['amount_refunded'] ?? 0) / 100;

        return (object) [
            'processed' => true,
            'paymentStatus' => $charge['refunded'] ? 'refunded' : 'partially_refunded',
            'transactionId' => $paymentIntentId,
            'refundAmount' => $amountRefunded,
            'message' => 'Refund processed',
        ];
    }

    /**
     * Make a request to the Stripe API.
     */
    protected function stripeRequest(string $method, string $endpoint, array $data, string $secretKey): array
    {
        $response = Http::withBasicAuth($secretKey, '')
            ->timeout(30)
            ->asForm()
            ->send($method, self::API_BASE . $endpoint, [
                'form_params' => $this->flattenArray($data),
            ]);

        if ($response->failed()) {
            $error = $response->json('error') ?? [];
            Log::error('Stripe API error', [
                'endpoint' => $endpoint,
                'error' => $error,
                'status' => $response->status(),
            ]);

            throw new \RuntimeException(
                $error['message'] ?? 'Stripe API request failed',
                $response->status()
            );
        }

        return $response->json();
    }

    /**
     * Format items for Stripe line_items.
     */
    protected function formatLineItems(array $items, string $currency): array
    {
        $lineItems = [];

        foreach ($items as $item) {
            $lineItems[] = [
                'price_data' => [
                    'currency' => strtolower($currency),
                    'product_data' => [
                        'name' => $item['name'],
                    ],
                    'unit_amount' => (int) (($item['unit_price'] ?? $item['total'] / $item['quantity']) * 100),
                ],
                'quantity' => $item['quantity'],
            ];
        }

        return $lineItems;
    }

    /**
     * Verify Stripe webhook signature.
     */
    protected function verifyWebhookSignature(array $payload, string $signatureHeader, string $webhookSecret): bool
    {
        if (empty($webhookSecret) || empty($signatureHeader)) {
            return false;
        }

        $payloadString = json_encode($payload);

        // Parse signature header
        $parts = [];
        foreach (explode(',', $signatureHeader) as $part) {
            $kv = explode('=', $part, 2);
            if (count($kv) === 2) {
                $parts[$kv[0]] = $kv[1];
            }
        }

        $timestamp = $parts['t'] ?? null;
        $signature = $parts['v1'] ?? null;

        if (!$timestamp || !$signature) {
            return false;
        }

        // Verify timestamp is recent (within 5 minutes)
        if (abs(time() - (int) $timestamp) > 300) {
            return false;
        }

        // Compute expected signature
        $signedPayload = "{$timestamp}.{$payloadString}";
        $expectedSignature = hash_hmac('sha256', $signedPayload, $webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Extract store ID from webhook payload.
     */
    protected function extractStoreIdFromWebhook(array $payload): ?int
    {
        // Check metadata first
        $metadata = $payload['data']['object']['metadata'] ?? [];
        if (isset($metadata['store_id'])) {
            return (int) $metadata['store_id'];
        }

        return null;
    }

    /**
     * Map refund reason to Stripe enum.
     */
    protected function mapRefundReason(string $reason): string
    {
        return match (strtolower($reason)) {
            'duplicate' => 'duplicate',
            'fraudulent' => 'fraudulent',
            default => 'requested_by_customer',
        };
    }

    /**
     * Get settings for a store.
     */
    protected function getSettings(?int $storeId): array
    {
        if (!$storeId) {
            return [];
        }
        return $this->plugin->getStoreSettings($storeId);
    }

    /**
     * Flatten nested array for form encoding.
     */
    protected function flattenArray(array $array, string $prefix = ''): array
    {
        $result = [];

        foreach ($array as $key => $value) {
            $newKey = $prefix ? "{$prefix}[{$key}]" : $key;

            if (is_array($value)) {
                $result = array_merge($result, $this->flattenArray($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }

        return $result;
    }
}
