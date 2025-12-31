<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

/**
 * Payment Method Result DTO
 */
readonly class PaymentMethodResult
{
    public function __construct(
        public bool $success,
        public string $paymentMethodId,
        public string $type,
        public ?string $brand = null,
        public ?string $lastFour = null,
        public ?string $expMonth = null,
        public ?string $expYear = null,
        public ?string $holderName = null,
        public ?string $customerId = null,
        public array $metadata = [],
        public array $rawResponse = [],
    ) {}

    public static function fromCard(
        string $paymentMethodId,
        string $brand,
        string $lastFour,
        string $expMonth,
        string $expYear,
        array $options = []
    ): self {
        return new self(
            success: true,
            paymentMethodId: $paymentMethodId,
            type: 'card',
            brand: $brand,
            lastFour: $lastFour,
            expMonth: $expMonth,
            expYear: $expYear,
            holderName: $options['holder_name'] ?? null,
            customerId: $options['customer_id'] ?? null,
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'payment_method_id' => $this->paymentMethodId,
            'type' => $this->type,
            'brand' => $this->brand,
            'last_four' => $this->lastFour,
            'exp_month' => $this->expMonth,
            'exp_year' => $this->expYear,
            'holder_name' => $this->holderName,
            'customer_id' => $this->customerId,
        ];
    }
}
