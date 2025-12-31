<?php

declare(strict_types=1);

namespace App\Services\Payment\DTO;

/**
 * Connected Account Result DTO
 */
readonly class ConnectedAccountResult
{
    public function __construct(
        public bool $success,
        public string $accountId,
        public string $status,
        public bool $chargesEnabled,
        public bool $payoutsEnabled,
        public ?string $email = null,
        public ?string $country = null,
        public ?string $defaultCurrency = null,
        public array $requirements = [],
        public array $metadata = [],
        public array $rawResponse = [],
    ) {}

    public static function success(
        string $accountId,
        string $status,
        bool $chargesEnabled,
        bool $payoutsEnabled,
        array $options = []
    ): self {
        return new self(
            success: true,
            accountId: $accountId,
            status: $status,
            chargesEnabled: $chargesEnabled,
            payoutsEnabled: $payoutsEnabled,
            email: $options['email'] ?? null,
            country: $options['country'] ?? null,
            defaultCurrency: $options['default_currency'] ?? null,
            requirements: $options['requirements'] ?? [],
            metadata: $options['metadata'] ?? [],
            rawResponse: $options['raw_response'] ?? [],
        );
    }

    public function isReady(): bool
    {
        return $this->chargesEnabled && $this->payoutsEnabled;
    }

    public function toArray(): array
    {
        return [
            'success' => $this->success,
            'account_id' => $this->accountId,
            'status' => $this->status,
            'charges_enabled' => $this->chargesEnabled,
            'payouts_enabled' => $this->payoutsEnabled,
            'email' => $this->email,
            'country' => $this->country,
            'default_currency' => $this->defaultCurrency,
            'requirements' => $this->requirements,
        ];
    }
}
