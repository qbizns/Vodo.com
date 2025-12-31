<?php

declare(strict_types=1);

namespace App\Services\Payment\Contracts;

use App\Services\Payment\DTO\ChargeResult;
use App\Services\Payment\DTO\RefundResult;
use App\Services\Payment\DTO\CustomerResult;
use App\Services\Payment\DTO\PaymentMethodResult;

/**
 * Payment Gateway Interface
 *
 * Defines the contract for all payment gateway implementations.
 */
interface PaymentGatewayInterface
{
    /**
     * Get the gateway identifier.
     */
    public function getIdentifier(): string;

    /**
     * Get the gateway display name.
     */
    public function getName(): string;

    /**
     * Check if the gateway supports the given currency.
     */
    public function supportsCurrency(string $currency): bool;

    /**
     * Get supported currencies.
     *
     * @return array<string>
     */
    public function getSupportedCurrencies(): array;

    /**
     * Create or retrieve a customer.
     */
    public function createCustomer(array $data): CustomerResult;

    /**
     * Update a customer.
     */
    public function updateCustomer(string $customerId, array $data): CustomerResult;

    /**
     * Delete a customer.
     */
    public function deleteCustomer(string $customerId): bool;

    /**
     * Attach a payment method to a customer.
     */
    public function attachPaymentMethod(string $customerId, string $paymentMethodId): PaymentMethodResult;

    /**
     * Detach a payment method from a customer.
     */
    public function detachPaymentMethod(string $paymentMethodId): bool;

    /**
     * Get a payment method.
     */
    public function getPaymentMethod(string $paymentMethodId): ?PaymentMethodResult;

    /**
     * List customer payment methods.
     *
     * @return array<PaymentMethodResult>
     */
    public function listPaymentMethods(string $customerId): array;

    /**
     * Charge a payment method.
     */
    public function charge(array $data): ChargeResult;

    /**
     * Capture a previously authorized charge.
     */
    public function capture(string $chargeId, ?int $amount = null): ChargeResult;

    /**
     * Refund a charge.
     */
    public function refund(string $chargeId, ?int $amount = null, ?string $reason = null): RefundResult;

    /**
     * Get charge details.
     */
    public function getCharge(string $chargeId): ?ChargeResult;

    /**
     * Create a payment intent (for 3D Secure / SCA).
     */
    public function createPaymentIntent(array $data): array;

    /**
     * Confirm a payment intent.
     */
    public function confirmPaymentIntent(string $intentId, array $data = []): array;

    /**
     * Verify a webhook signature.
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool;

    /**
     * Parse a webhook event.
     */
    public function parseWebhookEvent(string $payload): array;
}
