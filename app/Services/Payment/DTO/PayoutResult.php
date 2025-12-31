<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

use App\Enums\PayoutStatus;

/**
 * Payout Result DTO
 */
readonly class PayoutResult
{
    public function __construct(
        public bool $success,
        public string $payoutId,
        public PayoutStatus $status,
        public int $amount,
        public string $currency,
        public ?string $accountId = null,
        public ?string $arrivalDate = null,
        public ?string $failureCode = null,
        public ?string $failureMessage = null,
        public array $metadata = [],
        public array $rawResponse = [],
    ) {}

    public static function success(
        string $payoutId,
        int $amount,
        string $currency,
        array $options = []
    ): self {
        return new self(
            success: true,
            payoutId: $payoutId,
            status: PayoutStatus::Processing,
            amount: $amount,
            currency: $currency,
            accountId: $options['account_id'] ?? null,
            arrivalDate: $options['arrival_date'] ?? null,
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public static function failed(
        string $payoutId,
        int $amount,
        string $currency,
        string $failureCode,
        string $failureMessage
    ): self {
        return new self(
            success: false,
            payoutId: $payoutId,
            status: PayoutStatus::Failed,
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
            'payout_id' => $this->payoutId,
            'status' => $this->status->value,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'account_id' => $this->accountId,
            'arrival_date' => $this->arrivalDate,
            'failure_code' => $this->failureCode,
            'failure_message' => $this->failureMessage,
        ];
    }
}
