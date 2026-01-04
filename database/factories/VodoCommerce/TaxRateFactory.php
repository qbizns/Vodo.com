<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\TaxRate;
use VodoCommerce\Models\TaxZone;

/**
 * @extends Factory<TaxRate>
 */
class TaxRateFactory extends Factory
{
    protected $model = TaxRate::class;

    public function definition(): array
    {
        $rates = [
            ['name' => 'Sales Tax', 'rate' => 8.5, 'code' => 'SALES'],
            ['name' => 'VAT', 'rate' => 20.0, 'code' => 'VAT'],
            ['name' => 'GST', 'rate' => 5.0, 'code' => 'GST'],
            ['name' => 'PST', 'rate' => 7.0, 'code' => 'PST'],
            ['name' => 'State Tax', 'rate' => 6.5, 'code' => 'STATE'],
        ];

        $rate = fake()->randomElement($rates);

        return [
            'tax_zone_id' => TaxZone::factory(),
            'name' => $rate['name'],
            'code' => $rate['code'],
            'rate' => $rate['rate'],
            'type' => 'percentage',
            'compound' => false,
            'shipping_taxable' => true,
            'priority' => fake()->numberBetween(0, 10),
            'is_active' => true,
            'category_id' => null,
        ];
    }

    public function vat(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'VAT',
            'code' => 'VAT',
            'rate' => 20.0,
            'type' => 'percentage',
        ]);
    }

    public function gst(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'GST',
            'code' => 'GST',
            'rate' => 5.0,
            'type' => 'percentage',
        ]);
    }

    public function salesTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sales Tax',
            'code' => 'SALES',
            'rate' => fake()->randomFloat(2, 5, 10),
            'type' => 'percentage',
        ]);
    }

    public function compound(): static
    {
        return $this->state(fn (array $attributes) => [
            'compound' => true,
            'priority' => fake()->numberBetween(5, 10),
        ]);
    }

    public function fixed(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'fixed',
            'rate' => fake()->randomFloat(2, 1, 10),
        ]);
    }

    public function noShippingTax(): static
    {
        return $this->state(fn (array $attributes) => [
            'shipping_taxable' => false,
        ]);
    }

    public function categorySpecific(): static
    {
        return $this->state(fn (array $attributes) => [
            'category_id' => fake()->numberBetween(1, 10),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
