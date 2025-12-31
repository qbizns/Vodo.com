<?php

declare(strict_types=1);

namespace VodoCommerce\Contracts;

use VodoCommerce\Models\Order;

/**
 * Payment Gateway Contract
 *
 * Defines the interface that payment gateway plugins must implement
 * to integrate with the commerce plugin.
 */
interface PaymentGatewayContract
{
    /**
     * Get the gateway name.
     */
    public function getName(): string;

    /**
     * Get the gateway slug/identifier.
     */
    public function getSlug(): string;

    /**
     * Get the gateway icon.
     */
    public function getIcon(): ?string;

    /**
     * Check if the gateway is configured and available.
     */
    public function isAvailable(): bool;

    /**
     * Check if the gateway supports the given currency.
     */
    public function supportsCurrency(string $currency): bool;

    /**
     * Get supported payment methods (card, bank, wallet, etc.).
     */
    public function getSupportedMethods(): array;

    /**
     * Create a checkout session for an order.
     *
     * @param Order $order The order to pay for
     * @param array $options Additional options (return_url, cancel_url, etc.)
     * @return CheckoutSession
     */
    public function createCheckoutSession(Order $order, array $options = []): CheckoutSession;

    /**
     * Process a payment directly (for stored cards, wallets, etc.).
     *
     * @param Order $order The order to pay for
     * @param array $paymentData Payment data (token, card details, etc.)
     * @return PaymentResult
     */
    public function processPayment(Order $order, array $paymentData): PaymentResult;

    /**
     * Handle incoming webhook from the payment provider.
     *
     * @param array $payload Webhook payload
     * @param array $headers Webhook headers
     * @return WebhookResult
     */
    public function handleWebhook(array $payload, array $headers): WebhookResult;

    /**
     * Verify a webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Refund a payment.
     *
     * @param string $paymentReference Original payment reference
     * @param int $amount Amount to refund in cents
     * @param string|null $reason Refund reason
     * @return RefundResult
     */
    public function refund(string $paymentReference, int $amount, ?string $reason = null): RefundResult;

    /**
     * Get payment status.
     *
     * @param string $paymentReference Payment reference
     * @return PaymentStatus
     */
    public function getPaymentStatus(string $paymentReference): PaymentStatus;

    /**
     * Get configuration fields for the admin.
     */
    public function getConfigurationFields(): array;
}

/**
 * Checkout Session - Represents a payment checkout session.
 */
class CheckoutSession
{
    public function __construct(
        public readonly string $id,
        public readonly string $url,
        public readonly string $status,
        public readonly ?int $expiresAt = null,
        public readonly array $metadata = []
    ) {}

    public function isRedirect(): bool
    {
        return !empty($this->url);
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'url' => $this->url,
            'status' => $this->status,
            'expires_at' => $this->expiresAt,
            'metadata' => $this->metadata,
        ];
    }
}

/**
 * Payment Result - Represents the result of a payment attempt.
 */
class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $reference = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $errorMessage = null,
        public readonly array $metadata = []
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'reference' => $this->reference,
            'error_code' => $this->errorCode,
            'error_message' => $this->errorMessage,
            'metadata' => $this->metadata,
        ];
    }
}

/**
 * Webhook Result - Represents the result of webhook processing.
 */
class WebhookResult
{
    public function __construct(
        public readonly bool $handled,
        public readonly string $event,
        public readonly ?string $orderId = null,
        public readonly ?string $action = null,
        public readonly array $data = []
    ) {}

    public function toArray(): array
    {
        return [
            'handled' => $this->handled,
            'event' => $this->event,
            'order_id' => $this->orderId,
            'action' => $this->action,
            'data' => $this->data,
        ];
    }
}

/**
 * Refund Result - Represents the result of a refund attempt.
 */
class RefundResult
{
    public function __construct(
        public readonly bool $success,
        public readonly string $status,
        public readonly ?string $refundId = null,
        public readonly ?int $amount = null,
        public readonly ?string $errorMessage = null
    ) {}

    public function isSuccessful(): bool
    {
        return $this->success;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'status' => $this->status,
            'refund_id' => $this->refundId,
            'amount' => $this->amount,
            'error_message' => $this->errorMessage,
        ];
    }
}

/**
 * Payment Status - Represents the status of a payment.
 */
class PaymentStatus
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCEEDED = 'succeeded';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_PARTIALLY_REFUNDED = 'partially_refunded';

    public function __construct(
        public readonly string $status,
        public readonly ?int $amount = null,
        public readonly ?int $amountRefunded = null,
        public readonly ?string $currency = null,
        public readonly array $metadata = []
    ) {}

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_SUCCEEDED;
    }

    public function isRefunded(): bool
    {
        return in_array($this->status, [self::STATUS_REFUNDED, self::STATUS_PARTIALLY_REFUNDED]);
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'amount' => $this->amount,
            'amount_refunded' => $this->amountRefunded,
            'currency' => $this->currency,
            'metadata' => $this->metadata,
        ];
    }
}
