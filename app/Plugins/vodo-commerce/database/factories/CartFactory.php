<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<Cart>
 */
class CartFactory extends Factory
{
    protected $model = Cart::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'customer_id' => null,
            'session_id' => fake()->uuid(),
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP', 'SAR']),
            'subtotal' => 0,
            'discount_total' => 0,
            'shipping_total' => 0,
            'tax_total' => 0,
            'total' => 0,
            'discount_codes' => [],
            'shipping_method' => null,
            'billing_address' => null,
            'shipping_address' => null,
            'notes' => null,
            'meta' => null,
            'expires_at' => now()->addDays(7),
        ];
    }

    /**
     * Indicate that the cart has a customer.
     */
    public function withCustomer(?Customer $customer = null): static
    {
        return $this->state(fn (array $attributes) => [
            'customer_id' => $customer?->id ?? Customer::factory(),
        ]);
    }

    /**
     * Indicate that the cart is expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => now()->subDays(1),
        ]);
    }

    /**
     * Indicate that the cart never expires.
     */
    public function neverExpires(): static
    {
        return $this->state(fn (array $attributes) => [
            'expires_at' => null,
        ]);
    }

    /**
     * Set specific totals for the cart.
     */
    public function withTotals(
        float $subtotal = 100.00,
        float $discountTotal = 0.00,
        float $shippingTotal = 10.00,
        float $taxTotal = 9.00
    ): static {
        return $this->state(fn (array $attributes) => [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'shipping_total' => $shippingTotal,
            'tax_total' => $taxTotal,
            'total' => $subtotal - $discountTotal + $shippingTotal + $taxTotal,
        ]);
    }

    /**
     * Add shipping address to the cart.
     */
    public function withShippingAddress(?array $address = null): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_address' => $address ?? [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'phone' => fake()->phoneNumber(),
                'company' => fake()->optional()->company(),
                'address1' => fake()->streetAddress(),
                'address2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->postcode(),
                'country' => 'US',
            ],
        ]);
    }

    /**
     * Add billing address to the cart.
     */
    public function withBillingAddress(?array $address = null): static
    {
        return $this->state(fn (array $attributes) => [
            'billing_address' => $address ?? [
                'first_name' => fake()->firstName(),
                'last_name' => fake()->lastName(),
                'email' => fake()->safeEmail(),
                'phone' => fake()->phoneNumber(),
                'company' => fake()->optional()->company(),
                'address1' => fake()->streetAddress(),
                'address2' => fake()->optional()->secondaryAddress(),
                'city' => fake()->city(),
                'state' => fake()->stateAbbr(),
                'postal_code' => fake()->postcode(),
                'country' => 'US',
            ],
        ]);
    }

    /**
     * Add both shipping and billing addresses.
     */
    public function withAddresses(): static
    {
        return $this->withShippingAddress()->withBillingAddress();
    }

    /**
     * Add discount codes to the cart.
     */
    public function withDiscounts(array $codes = ['SAVE10', 'FREESHIP']): static
    {
        return $this->state(fn (array $attributes) => [
            'discount_codes' => $codes,
        ]);
    }

    /**
     * Set shipping method for the cart.
     */
    public function withShipping(string $method = 'standard', float $cost = 10.00): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_method' => $method,
            'shipping_total' => $cost,
        ]);
    }

    /**
     * Configure as an abandoned cart (old and with items).
     */
    public function abandoned(): static
    {
        return $this->state(fn (array $attributes) => [
            'updated_at' => now()->subHours(25),
        ]);
    }

    /**
     * Set cart currency.
     */
    public function currency(string $currency): static
    {
        return $this->state(fn (array $attributes) => [
            'currency' => strtoupper($currency),
        ]);
    }

    /**
     * Add notes to the cart.
     */
    public function withNotes(string $notes = 'Please handle with care'): static
    {
        return $this->state(fn (array $attributes) => [
            'notes' => $notes,
        ]);
    }
}
