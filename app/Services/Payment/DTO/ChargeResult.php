<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

use App\Enums\PaymentStatus;

/**
 * Charge Result DTO
 *
 * Represents the result of a charge operation.
 */
readonly class ChargeResult
{
    public function __construct(
        public bool $success,
        public string $chargeId,
        public PaymentStatus $status,
        public int $amount,
        public string $currency,
        public ?string $paymentMethodId = null,
        public ?string $customerId = null,
        public ?int $fee = null,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
        public ?string $receiptUrl = null,
        public array $metadata = [],
        public array $rawResponse = [],
    ) {}

    public static function success(
        string $chargeId,
        int $amount,
        string $currency,
        array $options = []
    ): self {
        return new self(
            success: true,
            chargeId: $chargeId,
            status: PaymentStatus::Succeeded,
            amount: $amount,
            currency: $currency,
            paymentMethodId: $options['payment_method_id'] ?? null,
            customerId: $options['customer_id'] ?? null,
            fee: $options['fee'] ?? null,
            receiptUrl: $options['receipt_url'] ?? null,
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public static function pending(
        string $chargeId,
        int $amount,
        string $currency,
        array $options = []
    ): self {
        return new self(
            success: true,
            chargeId: $chargeId,
            status: PaymentStatus::Pending,
            amount: $amount,
            currency: $currency,
            paymentMethodId: $options['payment_method_id'] ?? null,
            customerId: $options['customer_id'] ?? null,
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public static function failed(
        string $chargeId,
        int $amount,
        string $currency,
        string $failureCode,
        string $failureMessage,
        array $options = []
    ): self {
        return new self(
            success: false,
            chargeId: $chargeId,
            status: PaymentStatus::Failed,
            amount: $amount,
            currency: $currency,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'charge_id' => $this->chargeId,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'payment_method_id' => $this->paymentMethodId,
            'customer_id' => $this->customerId,
            'fee' => $this->fee,
            'failure_code' => $this->failureCode,
            'failure_message' => $this->failureMessage,
            'receipt_url' => $this->receiptUrl,
            'metadata' => $this->metadata,
        ];
    }
}
