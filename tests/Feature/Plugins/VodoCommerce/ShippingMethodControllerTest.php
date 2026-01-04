<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\ShippingRate;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\Store;

class ShippingMethodControllerTest extends TestCase
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

    public function test_can_list_shipping_methods(): void
    {
        ShippingMethod::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/shipping/methods');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'description', 'calculation_type', 'is_active', 'rates'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_methods_by_active_status(): void
    {
        ShippingMethod::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        ShippingMethod::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/shipping/methods?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_methods_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        ShippingMethod::factory()->create(['store_id' => $this->store->id, 'name' => 'Store Method']);
        ShippingMethod::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Store Method']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/shipping/methods');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Store Method');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_shipping_method(): void
    {
        $methodData = [
            'name' => 'Standard Shipping',
            'code' => 'standard',
            'description' => 'Standard delivery in 5-7 business days',
            'calculation_type' => 'flat_rate',
            'base_cost' => 9.99,
            'min_delivery_days' => 5,
            'max_delivery_days' => 7,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods', $methodData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Standard Shipping')
            ->assertJsonPath('data.code', 'standard');

        $this->assertDatabaseHas('commerce_shipping_methods', [
            'name' => 'Standard Shipping',
            'code' => 'standard',
            'calculation_type' => 'flat_rate',
        ]);
    }

    public function test_creating_method_requires_required_fields(): void
    {
        $methodData = [
            'description' => 'Test Method',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods', $methodData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'code', 'calculation_type']);
    }

    public function test_can_create_method_with_order_amount_restrictions(): void
    {
        $methodData = [
            'name' => 'Free Shipping',
            'code' => 'free',
            'description' => 'Free shipping on orders over $100',
            'calculation_type' => 'flat_rate',
            'base_cost' => 0.00,
            'min_order_amount' => 100.00,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods', $methodData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_shipping_methods', [
            'code' => 'free',
            'min_order_amount' => 100.00,
        ]);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/shipping/methods/{$method->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $method->id)
            ->assertJsonPath('data.name', $method->name);
    }

    public function test_cannot_show_method_from_other_store(): void
    {
        $otherStore = Store::factory()->create();
        $method = ShippingMethod::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/shipping/methods/{$method->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['store_id' => $this->store->id]);

        $updateData = [
            'name' => 'Updated Method Name',
            'description' => 'Updated description',
            'base_cost' => 19.99,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/shipping/methods/{$method->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Method Name');

        $this->assertDatabaseHas('commerce_shipping_methods', [
            'id' => $method->id,
            'name' => 'Updated Method Name',
            'base_cost' => 19.99,
        ]);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/shipping/methods/{$method->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_shipping_methods', [
            'id' => $method->id,
        ]);
    }

    // =========================================================================
    // Activation Tests
    // =========================================================================

    public function test_can_activate_shipping_method(): void
    {
        $method = ShippingMethod::factory()->inactive()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/shipping/methods/{$method->id}/activate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('commerce_shipping_methods', [
            'id' => $method->id,
            'is_active' => true,
        ]);
    }

    public function test_can_deactivate_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['store_id' => $this->store->id, 'is_active' => true]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/shipping/methods/{$method->id}/deactivate");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('commerce_shipping_methods', [
            'id' => $method->id,
            'is_active' => false,
        ]);
    }

    // =========================================================================
    // Rate Management Tests
    // =========================================================================

    public function test_can_add_rate_to_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['store_id' => $this->store->id]);
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);

        $rateData = [
            'shipping_method_id' => $method->id,
            'shipping_zone_id' => $zone->id,
            'rate' => 15.99,
            'min_weight' => 0,
            'max_weight' => 10,
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/shipping/methods/{$method->id}/rates", $rateData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.rate', '15.99');

        $this->assertDatabaseHas('commerce_shipping_rates', [
            'shipping_method_id' => $method->id,
            'shipping_zone_id' => $zone->id,
            'rate' => 15.99,
        ]);
    }

    public function test_can_remove_rate_from_shipping_method(): void
    {
        $method = ShippingMethod::factory()->create(['store_id' => $this->store->id]);
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
        $rate = ShippingRate::factory()->create([
            'shipping_method_id' => $method->id,
            'shipping_zone_id' => $zone->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/shipping/methods/{$method->id}/rates/{$rate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_shipping_rates', [
            'id' => $rate->id,
        ]);
    }

    // =========================================================================
    // Shipping Calculation Tests
    // =========================================================================

    public function test_can_calculate_shipping_options(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        $method = ShippingMethod::factory()->standard()->create(['store_id' => $this->store->id]);
        ShippingRate::factory()->create([
            'shipping_method_id' => $method->id,
            'shipping_zone_id' => $zone->id,
            'rate' => 9.99,
        ]);

        $requestData = [
            'address' => [
                'country_code' => 'US',
                'state_code' => 'CA',
            ],
            'cart_data' => [
                'subtotal' => 50.00,
                'total_weight' => 5.0,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    'shipping_options' => [
                        '*' => ['method_id', 'method_name', 'cost', 'delivery_time'],
                    ],
                    'count',
                ],
            ]);
    }

    public function test_calculate_returns_free_shipping_when_threshold_met(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        $method = ShippingMethod::factory()->free()->create([
            'store_id' => $this->store->id,
            'min_order_amount' => 100.00,
        ]);

        ShippingRate::factory()->create([
            'shipping_method_id' => $method->id,
            'shipping_zone_id' => $zone->id,
            'rate' => 0.00,
            'is_free_shipping' => true,
            'free_shipping_threshold' => 100.00,
        ]);

        $requestData = [
            'address' => ['country_code' => 'US', 'state_code' => 'CA'],
            'cart_data' => ['subtotal' => 150.00, 'total_weight' => 5.0],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonPath('data.shipping_options.0.cost', 0);
    }

    public function test_calculate_with_weight_based_rates(): void
    {
        $zone = ShippingZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        $method = ShippingMethod::factory()->create([
            'store_id' => $this->store->id,
            'calculation_type' => 'weight_based',
        ]);

        ShippingRate::factory()->create([
            'shipping_method_id' => $method->id,
            'shipping_zone_id' => $zone->id,
            'rate' => 10.00,
            'weight_rate' => 2.50,
            'min_weight' => 0,
            'max_weight' => 20,
        ]);

        $requestData = [
            'address' => ['country_code' => 'US', 'state_code' => 'CA'],
            'cart_data' => ['subtotal' => 50.00, 'total_weight' => 8.0],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods/calculate', $requestData);

        $response->assertStatus(200);
        // Cost should be 10.00 + (8.0 * 2.50) = 30.00
        $this->assertEquals(30.00, $response->json('data.shipping_options.0.cost'));
    }

    public function test_calculate_returns_empty_when_no_matching_zone(): void
    {
        $requestData = [
            'address' => ['country_code' => 'ZZ', 'state_code' => 'XX'],
            'cart_data' => ['subtotal' => 50.00, 'total_weight' => 5.0],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/shipping/methods/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonPath('data.count', 0);
    }
}
