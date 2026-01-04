<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxExemption;

/**
 * @extends Factory<TaxExemption>
 */
class TaxExemptionFactory extends Factory
{
    protected $model = TaxExemption::class;

    public function definition(): array
    {
        return [
            'store_id' => Store::factory(),
            'name' => fake()->sentence(3),
            'description' => fake()->optional()->sentence(),
            'type' => fake()->randomElement(['customer', 'product', 'category', 'customer_group']),
            'entity_id' => fake()->numberBetween(1, 100),
            'certificate_number' => fake()->optional()->bothify('CERT-####-????'),
            'valid_from' => fake()->optional()->dateTimeBetween('-1 year', 'now'),
            'valid_until' => fake()->optional()->dateTimeBetween('now', '+2 years'),
            'country_code' => fake()->optional()->countryCode(),
            'state_code' => fake()->optional()->stateAbbr(),
            'is_active' => true,
        ];
    }

    public function customer(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer',
            'name' => 'Customer Tax Exemption',
        ]);
    }

    public function product(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'product',
            'name' => 'Product Tax Exemption',
        ]);
    }

    public function category(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'category',
            'name' => 'Category Tax Exemption',
        ]);
    }

    public function customerGroup(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'customer_group',
            'name' => 'Customer Group Tax Exemption',
        ]);
    }

    public function withCertificate(): static
    {
        return $this->state(fn (array $attributes) => [
            'certificate_number' => 'CERT-' . fake()->numerify('####') . '-' . fake()->bothify('????'),
        ]);
    }

    public function permanent(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => null,
            'valid_until' => null,
        ]);
    }

    public function temporary(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subDays(30),
            'valid_until' => now()->addDays(60),
        ]);
    }

    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'valid_from' => now()->subMonths(6),
            'valid_until' => now()->subDays(1),
        ]);
    }

    public function locationSpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => fake()->countryCode(),
            'state_code' => fake()->stateAbbr(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
