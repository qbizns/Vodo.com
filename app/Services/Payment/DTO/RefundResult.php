<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

/**
 * Refund Result DTO
 */
readonly class RefundResult
{
    public function __construct(
        public bool $success,
        public string $refundId,
        public string $chargeId,
        public string $status,
        public int $amount,
        public string $currency,
        public ?string $reason = null,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
        public array $metadata = [],
        public array $rawResponse = [],
    ) {}

    public static function success(
        string $refundId,
        string $chargeId,
        int $amount,
        string $currency,
        array $options = []
    ): self {
        return new self(
            success: true,
            refundId: $refundId,
            chargeId: $chargeId,
            status: 'succeeded',
            amount: $amount,
            currency: $currency,
            reason: $options['reason'] ?? null,
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public static function failed(
        string $refundId,
        string $chargeId,
        int $amount,
        string $currency,
        string $failureCode,
        string $failureMessage
    ): self {
        return new self(
            success: false,
            refundId: $refundId,
            chargeId: $chargeId,
            status: 'failed',
            amount: $amount,
            currency: $currency,
            failureCode: $failureCode,
            failureMessage: $failureMessage,
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'refund_id' => $this->refundId,
            'charge_id' => $this->chargeId,
            'status' => $this->status,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'reason' => $this->reason,
            'failure_code' => $this->failureCode,
            'failure_message' => $this->failureMessage,
        ];
    }
}
