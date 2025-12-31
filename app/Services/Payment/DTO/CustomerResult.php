<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

/**
 * Customer Result DTO
 */
readonly class CustomerResult
{
    public function __construct(
        public bool $success,
        public string $customerId,
        public ?string $email = null,
        public ?string $name = null,
        public ?string $phone = null,
        public array $metadata = [],
        public array $rawResponse = [],
    ) {}

    public static function success(
        string $customerId,
        array $options = []
    ): self {
        return new self(
            success: true,
            customerId: $customerId,
            email: $options['email'] ?? null,
            name: $options['name'] ?? null,
            phone: $options['phone'] ?? null,
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'customer_id' => $this->customerId,
            'email' => $this->email,
            'name' => $this->name,
            'phone' => $this->phone,
            'metadata' => $this->metadata,
        ];
    }
}
