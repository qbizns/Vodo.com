<?php

declare(strict_types=1);

namespace Database\Factories\VodoCommerce;

use Illuminate\Database\Eloquent\Factories\Factory;
use VodoCommerce\Models\TaxZone;
use VodoCommerce\Models\TaxZoneLocation;

/**
 * @extends Factory<TaxZoneLocation>
 */
class TaxZoneLocationFactory extends Factory
{
    protected $model = TaxZoneLocation::class;

    public function definition(): array
    {
        return [
            'zone_id' => TaxZone::factory(),
            'country_code' => fake()->countryCode(),
            'state_code' => fake()->optional()->stateAbbr(),
            'city' => fake()->optional()->city(),
            'postal_code_pattern' => fake()->optional()->passthrough('[0-9]{5}'),
        ];
    }

    public function usa(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'US',
            'state_code' => fake()->stateAbbr(),
        ]);
    }

    public function canada(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'CA',
            'state_code' => fake()->randomElement(['ON', 'QC', 'BC', 'AB']),
        ]);
    }

    public function uk(): static
    {
        return $this->state(fn (array $attributes) => [
            'country_code' => 'GB',
            'postal_code_pattern' => '[A-Z]{1,2}[0-9]{1,2}[A-Z]? [0-9][A-Z]{2}',
        ]);
    }

    public function withPostalPattern(): static
    {
        return $this->state(fn (array $attributes) => [
            'postal_code_pattern' => fake()->randomElement([
                '[0-9]{5}',
                '[0-9]{5}-[0-9]{4}',
                '[A-Z][0-9][A-Z] [0-9][A-Z][0-9]',
            ]),
        ]);
    }
}
