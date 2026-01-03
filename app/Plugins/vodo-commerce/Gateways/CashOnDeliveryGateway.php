<?php

declare(strict_types=1);

namespace VodoCommerce\Gateways;

use VodoCommerce\Contracts\CheckoutSession;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Contracts\PaymentResult;
use VodoCommerce\Contracts\PaymentStatus;
use VodoCommerce\Contracts\RefundResult;
use VodoCommerce\Contracts\WebhookResult;
use VodoCommerce\Models\Order;

/**
 * Cash On Delivery Payment Gateway
 * 
 * A simple payment gateway for orders that will be paid upon delivery.
 */
class CashOnDeliveryGateway implements PaymentGatewayContract
{
    public function __construct(
        protected array $config = []
    ) {}

    public function getName(): string
    {
        return 'Cash On Delivery';
    }

    public function getSlug(): string
    {
        return 'cod';
    }

    public function getIdentifier(): string
    {
        return 'cod';
    }

    public function getIcon(): ?string
    {
        return 'cash';
    }

    public function isAvailable(): bool
    {
        return $this->config['enabled'] ?? true;
    }

    public function supportsCurrency(string $currency): bool
    {
        // COD supports all currencies
        return true;
    }

    public function getSupportedMethods(): array
    {
        return ['cash'];
    }

    public function supports(): array
    {
        return [
            'currencies' => ['*'], // All currencies
            'methods' => ['cash'],
            'features' => ['offline'],
        ];
    }

    public function createCheckoutSession(Order $order, array $options = []): CheckoutSession
    {
        // COD doesn't need a checkout session - mark as successful immediately
        return new CheckoutSession(
            id: 'cod_' . $order->id,
            url: '', // No redirect needed
            status: 'complete',
            expiresAt: null,
            metadata: [
                'payment_method' => 'cod',
                'note' => 'Payment will be collected upon delivery',
            ]
        );
    }

    public function processPayment(Order $order, array $paymentData): PaymentResult
    {
        // COD payments are marked as pending until delivery
        return new PaymentResult(
            success: true,
            status: 'pending',
            reference: 'COD-' . $order->id . '-' . time(),
            errorCode: null,
            errorMessage: null,
            metadata: [
                'payment_method' => 'cod',
                'note' => 'Payment pending - collect on delivery',
            ]
        );
    }

    public function handleWebhook(array $payload, array $headers): WebhookResult
    {
        // COD doesn't have webhooks
        return new WebhookResult(
            handled: false,
            event: 'none',
            orderId: null,
            action: null,
            data: []
        );
    }

    public function verifyWebhookSignature(string $payload, string $signature): bool
    {
        // COD doesn't have webhooks
        return false;
    }

    public function refund(string $paymentReference, int $amount, ?string $reason = null): RefundResult
    {
        // COD refunds are handled manually
        return new RefundResult(
            success: true,
            status: 'completed',
            refundId: 'COD-REFUND-' . time(),
            amount: $amount,
            errorMessage: null
        );
    }

    public function getPaymentStatus(string $paymentReference): PaymentStatus
    {
        // For COD, status depends on order status (would need to look up)
        return new PaymentStatus(
            status: PaymentStatus::STATUS_PENDING,
            amount: null,
            amountRefunded: null,
            currency: null,
            metadata: ['payment_method' => 'cod']
        );
    }

    public function getConfigurationFields(): array
    {
        return [
            [
                'name' => 'enabled',
                'label' => 'Enable Cash On Delivery',
                'type' => 'boolean',
                'default' => true,
            ],
            [
                'name' => 'instructions',
                'label' => 'Customer Instructions',
                'type' => 'textarea',
                'default' => 'Pay with cash upon delivery.',
            ],
            [
                'name' => 'min_order',
                'label' => 'Minimum Order Amount',
                'type' => 'number',
                'default' => 0,
            ],
            [
                'name' => 'max_order',
                'label' => 'Maximum Order Amount',
                'type' => 'number',
                'default' => 0,
                'help' => '0 means no limit',
            ],
        ];
    }
}

