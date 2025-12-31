<?php

declare(strict_types=1);

namespace VodoCommerce\Contracts;

/**
 * Tax Provider Contract
 *
 * Defines the interface that tax calculation plugins must implement
 * to integrate with the commerce plugin.
 */
interface TaxProviderContract
{
    /**
     * Get the provider name.
     */
    public function getName(): string;

    /**
     * Get the provider slug/identifier.
     */
    public function getSlug(): string;

    /**
     * Check if the provider is available.
     */
    public function isAvailable(): bool;

    /**
     * Calculate tax for a cart/order.
     *
     * @param TaxableItem[] $items Items to calculate tax for
     * @param TaxAddress $shippingAddress Shipping address
     * @param TaxAddress|null $billingAddress Billing address (optional)
     * @param string $currency Currency code
     * @return TaxCalculation
     */
    public function calculateTax(
        array $items,
        TaxAddress $shippingAddress,
        ?TaxAddress $billingAddress = null,
        string $currency = 'USD'
    ): TaxCalculation;

    /**
     * Get tax rates for a location.
     *
     * @param TaxAddress $address Address to get rates for
     * @return TaxRate[]
     */
    public function getTaxRates(TaxAddress $address): array;

    /**
     * Validate a tax exemption certificate.
     *
     * @param string $certificateNumber Certificate number
     * @param string $country Country code
     * @return bool
     */
    public function validateExemption(string $certificateNumber, string $country): bool;

    /**
     * Get configuration fields for the admin.
     */
    public function getConfigurationFields(): array;
}

/**
 * Taxable Item - Represents an item that can be taxed.
 */
class TaxableItem
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $amount,
        public readonly int $quantity = 1,
        public readonly ?string $taxCode = null,
        public readonly ?string $productType = null,
        public readonly bool $isTaxable = true
    ) {}

    public function getTotal(): int
    {
        return $this->amount * $this->quantity;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'quantity' => $this->quantity,
            'tax_code' => $this->taxCode,
            'product_type' => $this->productType,
            'is_taxable' => $this->isTaxable,
            'total' => $this->getTotal(),
        ];
    }
}

/**
 * Tax Address - Represents an address for tax calculation.
 */
class TaxAddress
{
    public function __construct(
        public readonly string $country,
        public readonly ?string $state = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $address = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            country: $data['country'] ?? '',
            state: $data['state'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['postal_code'] ?? $data['postalCode'] ?? null,
            address: $data['address'] ?? $data['address1'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'country' => $this->country,
            'state' => $this->state,
            'city' => $this->city,
            'postal_code' => $this->postalCode,
            'address' => $this->address,
        ];
    }
}

/**
 * Tax Rate - Represents a tax rate.
 */
class TaxRate
{
    public function __construct(
        public readonly string $name,
        public readonly float $rate,
        public readonly string $type,
        public readonly ?string $jurisdiction = null,
        public readonly ?string $taxCode = null
    ) {}

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'rate' => $this->rate,
            'type' => $this->type,
            'jurisdiction' => $this->jurisdiction,
            'tax_code' => $this->taxCode,
        ];
    }
}

/**
 * Tax Calculation - Represents the result of tax calculation.
 */
class TaxCalculation
{
    public function __construct(
        public readonly int $taxAmount,
        public readonly int $subtotal,
        public readonly int $total,
        public readonly array $breakdown = [],
        public readonly ?string $taxExemptionReason = null
    ) {}

    public function isExempt(): bool
    {
        return $this->taxExemptionReason !== null;
    }

    public function getTaxRate(): float
    {
        if ($this->subtotal === 0) {
            return 0;
        }

        return round(($this->taxAmount / $this->subtotal) * 100, 2);
    }

    public function toArray(): array
    {
        return [
            'tax_amount' => $this->taxAmount,
            'subtotal' => $this->subtotal,
            'total' => $this->total,
            'tax_rate' => $this->getTaxRate(),
            'breakdown' => $this->breakdown,
            'tax_exemption_reason' => $this->taxExemptionReason,
        ];
    }
}
