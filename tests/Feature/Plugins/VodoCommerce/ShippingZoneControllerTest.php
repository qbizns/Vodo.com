<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\ShippingZoneLocation;
use VodoCommerce\Models\Store;

class ShippingZoneControllerTest extends TestCase
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

    public function test_can_list_shipping_zones(): void
    {
        ShippingZone::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/shipping/zones');

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
        ShippingZone::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        ShippingZone::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/shipping/zones?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_zones_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        ShippingZone::factory()->create(['store_id' => $this->store->id, 'name' => 'Store Zone']);
        ShippingZone::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Store Zone']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/shipping/zones');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Store Zone');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_shipping_zone(): void
    {
        $zoneData = [
            'name' => 'North America',
            'description' => 'United States and Canada',
            'priority' => 10,
            'is_active' => true,
            'locations' => [
                ['country_code' => 'US', 'state_code' => null],
                ['country_code' => 'CA', 'state_code' => null],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/zones', $zoneData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'North America');

        $this->assertDatabaseHas('commerce_shipping_zones', [
            'name' => 'North America',
            'priority' => 10,
        ]);

        $this->assertDatabaseCount('commerce_shipping_zone_locations', 2);
    }

    public function test_creating_zone_requires_name(): void
    {
        $zoneData = [
            'description' => 'Test Zone',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/zones', $zoneData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_create_zone_with_postal_code_pattern(): void
    {
        $zoneData = [
            'name' => 'California Express',
            'description' => 'California postal codes starting with 9',
            'priority' => 5,
            'locations' => [
                [
                    'country_code' => 'US',
                    'state_code' => 'CA',
                    'postal_code_pattern' => '^9[0-6]',
                ],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/zones', $zoneData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_shipping_zone_locations', [
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code_pattern' => '^9[0-6]',
        ]);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_shipping_zone(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/shipping/zones/{$zone->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $zone->id)
            ->assertJsonPath('data.name', $zone->name);
    }

    public function test_cannot_show_zone_from_other_store(): void
    {
        $otherStore = Store::factory()->create();
        $zone = ShippingZone::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/shipping/zones/{$zone->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_shipping_zone(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

        $updateData = [
            'name' => 'Updated Zone Name',
            'description' => 'Updated description',
            'priority' => 99,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/shipping/zones/{$zone->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Zone Name')
            ->assertJsonPath('data.priority', 99);

        $this->assertDatabaseHas('commerce_shipping_zones', [
            'id' => $zone->id,
            'name' => 'Updated Zone Name',
        ]);
    }

    public function test_can_update_zone_locations(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

        $updateData = [
            'name' => $zone->name,
            'locations' => [
                ['country_code' => 'GB', 'state_code' => null],
                ['country_code' => 'FR', 'state_code' => null],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/shipping/zones/{$zone->id}", $updateData);

        $response->assertStatus(200);

        $this->assertDatabaseCount('commerce_shipping_zone_locations', 2);
        $this->assertDatabaseHas('commerce_shipping_zone_locations', [
            'zone_id' => $zone->id,
            'country_code' => 'GB',
        ]);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_shipping_zone(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/shipping/zones/{$zone->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_shipping_zones', [
            'id' => $zone->id,
        ]);
    }

    // =========================================================================
    // Activation Tests
    // =========================================================================

    public function test_can_activate_shipping_zone(): void
    {
        $zone = ShippingZone::factory()->inactive()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/shipping/zones/{$zone->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('commerce_shipping_zones', [
            'id' => $zone->id,
            'is_active' => true,
        ]);
    }

    public function test_can_deactivate_shipping_zone(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id, 'is_active' => true]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/shipping/zones/{$zone->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('commerce_shipping_zones', [
            'id' => $zone->id,
            'is_active' => false,
        ]);
    }

    // =========================================================================
    // Zone Matching Tests
    // =========================================================================

    public function test_zone_matches_address(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
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
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create([
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code_pattern' => '^9[0-6]',
        ]);

        $matchingAddress = [
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code' => '90210',
        ];

        $nonMatchingAddress = [
            'country_code' => 'US',
            'state_code' => 'CA',
            'postal_code' => '97000',
        ];

        $this->assertTrue($zone->fresh()->matchesAddress($matchingAddress));
        $this->assertFalse($zone->fresh()->matchesAddress($nonMatchingAddress));
    }
}
