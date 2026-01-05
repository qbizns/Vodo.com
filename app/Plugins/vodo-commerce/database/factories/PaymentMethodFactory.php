<?php

declare(strict_types=1);

namespace VodoCommerce\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Store;

/**
 * @extends Factory<PaymentMethod>
 */
class PaymentMethodFactory extends Factory
{
    protected $model = PaymentMethod::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->company() . ' ' . fake()->randomElement(['Payment', 'Gateway', 'Pay']),
            'slug' => fake()->unique()->slug(3),
            'type' => fake()->randomElement([
                PaymentMethod::TYPE_ONLINE,
                PaymentMethod::TYPE_OFFLINE,
                PaymentMethod::TYPE_WALLET,
            ]),
            'provider' => fake()->randomElement([
                PaymentMethod::PROVIDER_STRIPE,
                PaymentMethod::PROVIDER_PAYPAL,
                PaymentMethod::PROVIDER_SQUARE,
                PaymentMethod::PROVIDER_MOYASAR,
                PaymentMethod::PROVIDER_CUSTOM,
            ]),
            'logo' => fake()->imageUrl(200, 100, 'payment', true),
            'description' => fake()->sentence(),
            'configuration' => [],
            'supported_currencies' => ['USD', 'EUR', 'GBP', 'SAR'],
            'supported_countries' => ['US', 'GB', 'SA'],
            'supported_payment_types' => ['card', 'bank_transfer'],
            'fees' => [
                'fixed' => fake()->randomFloat(2, 0, 1),
                'percentage' => fake()->randomFloat(2, 0, 5),
                'min' => 0,
                'max' => null,
            ],
            'minimum_amount' => null,
            'maximum_amount' => null,
            'supported_banks' => [],
            'is_active' => true,
            'is_default' => false,
            'display_order' => fake()->numberBetween(0, 100),
            'requires_shipping_address' => fake()->boolean(),
            'requires_billing_address' => fake()->boolean(),
            'webhook_url' => null,
            'webhook_secret' => null,
            'meta' => null,
        ];
    }

    /**
     * Indicate that the payment method is active
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Indicate that the payment method is inactive
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the payment method is default
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'is_active' => true,
        ]);
    }

    /**
     * Configure as Stripe payment method
     */
    public function stripe(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Stripe',
            'provider' => PaymentMethod::PROVIDER_STRIPE,
            'type' => PaymentMethod::TYPE_ONLINE,
            'configuration' => [
                'publishable_key' => 'pk_test_' . fake()->md5(),
                'secret_key' => 'sk_test_' . fake()->md5(),
            ],
            'supported_payment_types' => ['card', 'apple_pay', 'google_pay'],
            'fees' => [
                'fixed' => 0.30,
                'percentage' => 2.9,
            ],
        ]);
    }

    /**
     * Configure as PayPal payment method
     */
    public function paypal(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'PayPal',
            'provider' => PaymentMethod::PROVIDER_PAYPAL,
            'type' => PaymentMethod::TYPE_WALLET,
            'configuration' => [
                'client_id' => fake()->uuid(),
                'client_secret' => fake()->md5(),
            ],
            'supported_payment_types' => ['paypal', 'venmo'],
            'fees' => [
                'fixed' => 0.30,
                'percentage' => 2.9,
            ],
        ]);
    }

    /**
     * Configure as offline payment method (e.g., cash on delivery)
     */
    public function offline(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => fake()->randomElement(['Cash on Delivery', 'Bank Transfer', 'Check']),
            'type' => PaymentMethod::TYPE_OFFLINE,
            'provider' => PaymentMethod::PROVIDER_CUSTOM,
            'configuration' => [],
            'fees' => [
                'fixed' => 0,
                'percentage' => 0,
            ],
        ]);
    }

    /**
     * Configure with amount limits
     */
    public function withLimits(float $min = 10.0, float $max = 10000.0): static
    {
        return $this->state(fn (array $attributes) => [
            'minimum_amount' => $min,
            'maximum_amount' => $max,
        ]);
    }

    /**
     * Configure with bank support
     */
    public function withBanks(array $banks = ['Bank of America', 'Chase', 'Wells Fargo']): static
    {
        return $this->state(fn (array $attributes) => [
            'supported_banks' => $banks,
            'supported_payment_types' => ['bank_transfer'],
        ]);
    }
}
