<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxZone;
use VodoCommerce\Models\TaxZoneLocation;

class TaxZoneControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
    }

    // =========================================================================
    // Index Tests
    // =========================================================================

    public function test_can_list_tax_zones(): void
    {
        TaxZone::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tax/zones');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'description', 'priority', 'is_active', 'locations', 'rates'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_zones_by_active_status(): void
    {
        TaxZone::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        TaxZone::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tax/zones?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_zones_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        TaxZone::factory()->create(['store_id' => $this->store->id, 'name' => 'Store Zone']);
        TaxZone::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Store Zone']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tax/zones');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Store Zone');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_tax_zone(): void
    {
        $zoneData = [
            'name' => 'California',
            'description' => 'California state tax',
            'priority' => 10,
            'is_active' => true,
            'locations' => [
                ['country_code' => 'US', 'state_code' => 'CA'],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/zones', $zoneData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'California');

        $this->assertDatabaseHas('commerce_tax_zones', [
            'name' => 'California',
            'priority' => 10,
        ]);

        $this->assertDatabaseCount('commerce_tax_zone_locations', 1);
    }

    public function test_creating_zone_requires_name(): void
    {
        $zoneData = [
            'description' => 'Test Zone',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/zones', $zoneData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_zone_with_multiple_locations(): void
    {
        $zoneData = [
            'name' => 'European Union',
            'description' => 'EU VAT zone',
            'priority' => 5,
            'locations' => [
                ['country_code' => 'GB', 'state_code' => null],
                ['country_code' => 'DE', 'state_code' => null],
                ['country_code' => 'FR', 'state_code' => null],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/zones', $zoneData);

        $response->assertStatus(201);

        $this->assertDatabaseCount('commerce_tax_zone_locations', 3);
    }

    public function test_can_create_zone_with_postal_code_pattern(): void
    {
        $zoneData = [
            'name' => 'San Francisco Tax Zone',
            'description' => 'Specific SF postal codes',
            'priority' => 1,
            'locations' => [
                [
                    'country_code' => 'US',
                    'state_code' => 'CA',
                    'city' => 'San Francisco',
                    'postal_code_pattern' => '^941',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/zones', $zoneData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_tax_zone_locations', [
            'country_code' => 'US',
            'state_code' => 'CA',
            'city' => 'San Francisco',
            'postal_code_pattern' => '^941',
        ]);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_tax_zone(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/tax/zones/{$zone->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $zone->id)
            ->assertJsonPath('data.name', $zone->name);
    }

    public function test_cannot_show_zone_from_other_store(): void
    {
        $otherStore = Store::factory()->create();
        $zone = TaxZone::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/tax/zones/{$zone->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_tax_zone(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $updateData = [
            'name' => 'Updated Zone Name',
            'description' => 'Updated description',
            'priority' => 99,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/tax/zones/{$zone->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Zone Name')
            ->assertJsonPath('data.priority', 99);

        $this->assertDatabaseHas('commerce_tax_zones', [
            'id' => $zone->id,
            'name' => 'Updated Zone Name',
        ]);
    }

    public function test_can_update_zone_locations(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $updateData = [
            'name' => $zone->name,
            'locations' => [
                ['country_code' => 'GB', 'state_code' => null],
                ['country_code' => 'FR', 'state_code' => null],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/tax/zones/{$zone->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseCount('commerce_tax_zone_locations', 2);
        $this->assertDatabaseHas('commerce_tax_zone_locations', [
            'zone_id' => $zone->id,
            'country_code' => 'GB',
        ]);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_tax_zone(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/tax/zones/{$zone->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_tax_zones', [
            'id' => $zone->id,
        ]);
    }

    // =========================================================================
    // Activation Tests
    // =========================================================================

    public function test_can_activate_tax_zone(): void
    {
        $zone = TaxZone::factory()->inactive()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/tax/zones/{$zone->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('commerce_tax_zones', [
            'id' => $zone->id,
            'is_active' => true,
        ]);
    }

    public function test_can_deactivate_tax_zone(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id, 'is_active' => true]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/tax/zones/{$zone->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('commerce_tax_zones', [
            'id' => $zone->id,
            'is_active' => false,
        ]);
    }

    // =========================================================================
    // Zone Matching Tests
    // =========================================================================

    public function test_zone_matches_address(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create([
            'country_code' => 'US',
            'state_code' => 'CA',
        ]);

        $address = [
            'country_code' => 'US',
            'state_code' => 'CA',
        ];

        $this->assertTrue($zone->fresh()->matchesAddress($address));
    }

    public function test_zone_with_postal_pattern_matches_correctly(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create([
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code_pattern' => '^94',
        ]);

        $matchingAddress = [
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code' => '94102',
        ];

        $nonMatchingAddress = [
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code' => '90210',
        ];

        $this->assertTrue($zone->fresh()->matchesAddress($matchingAddress));
        $this->assertFalse($zone->fresh()->matchesAddress($nonMatchingAddress));
    }

    public function test_zone_with_city_matches_correctly(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create([
            'country_code' => 'US',
            'state_code' => 'CA',
            'city' => 'San Francisco',
        ]);

        $matchingAddress = [
            'country_code' => 'US',
            'state_code' => 'CA',
            'city' => 'San Francisco',
        ];

        $nonMatchingAddress = [
            'country_code' => 'US',
            'state_code' => 'CA',
            'city' => 'Los Angeles',
        ];

        $this->assertTrue($zone->fresh()->matchesAddress($matchingAddress));
        $this->assertFalse($zone->fresh()->matchesAddress($nonMatchingAddress));
    }

    public function test_higher_priority_zone_matches_first(): void
    {
        $generalZone = TaxZone::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'General US',
            'priority' => 10,
        ]);
        $generalZone->locations()->create(['country_code' => 'US']);

        $specificZone = TaxZone::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'California',
            'priority' => 5, // Lower number = higher priority
        ]);
        $specificZone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        $address = ['country_code' => 'US', 'state_code' => 'CA'];

        // Both zones match, but we should get the higher priority one
        $zones = TaxZone::where('store_id', $this->store->id)
            ->active()
            ->ordered()
            ->get();

        $matchingZone = $zones->first(fn($zone) => $zone->matchesAddress($address));
        $this->assertEquals('California', $matchingZone->name);
    }
}
