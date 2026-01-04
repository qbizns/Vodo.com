<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerGroup;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxExemption;
use VodoCommerce\Models\TaxRate;
use VodoCommerce\Models\TaxZone;

class TaxRateControllerTest extends TestCase
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

    public function test_can_list_tax_rates(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        TaxRate::factory()->count(3)->create(['tax_zone_id' => $zone->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tax/rates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'code', 'rate', 'type', 'compound', 'is_active'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_rates_by_active_status(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        TaxRate::factory()->count(2)->create(['tax_zone_id' => $zone->id, 'is_active' => true]);
        TaxRate::factory()->count(1)->create(['tax_zone_id' => $zone->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tax/rates?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_rates_by_zone(): void
    {
        $zone1 = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone2 = TaxZone::factory()->create(['store_id' => $this->store->id]);

        TaxRate::factory()->count(2)->create(['tax_zone_id' => $zone1->id]);
        TaxRate::factory()->count(1)->create(['tax_zone_id' => $zone2->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/tax/rates?zone_id={$zone1->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_tax_rate(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $rateData = [
            'tax_zone_id' => $zone->id,
            'name' => 'California Sales Tax',
            'code' => 'CA_SALES',
            'rate' => 7.25,
            'type' => 'percentage',
            'compound' => false,
            'priority' => 1,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/rates', $rateData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'California Sales Tax')
            ->assertJsonPath('data.rate', '7.25');

        $this->assertDatabaseHas('commerce_tax_rates', [
            'name' => 'California Sales Tax',
            'code' => 'CA_SALES',
            'rate' => 7.25,
        ]);
    }

    public function test_creating_rate_requires_required_fields(): void
    {
        $rateData = [
            'name' => 'Test Rate',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/rates', $rateData);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tax_zone_id', 'code', 'rate', 'type']);
    }

    public function test_can_create_compound_tax_rate(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $rateData = [
            'tax_zone_id' => $zone->id,
            'name' => 'Provincial Sales Tax',
            'code' => 'PST',
            'rate' => 7.00,
            'type' => 'percentage',
            'compound' => true,
            'priority' => 2,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/rates', $rateData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_tax_rates', [
            'code' => 'PST',
            'compound' => true,
        ]);
    }

    public function test_can_create_fixed_rate_tax(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);

        $rateData = [
            'tax_zone_id' => $zone->id,
            'name' => 'Fixed Environmental Fee',
            'code' => 'ENV_FEE',
            'rate' => 5.00,
            'type' => 'fixed',
            'compound' => false,
            'priority' => 1,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/rates', $rateData);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_tax_rates', [
            'code' => 'ENV_FEE',
            'type' => 'fixed',
        ]);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_tax_rate(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $rate = TaxRate::factory()->create(['tax_zone_id' => $zone->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/tax/rates/{$rate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $rate->id)
            ->assertJsonPath('data.name', $rate->name);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_tax_rate(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $rate = TaxRate::factory()->create(['tax_zone_id' => $zone->id]);

        $updateData = [
            'name' => 'Updated Tax Name',
            'rate' => 8.75,
        ];

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/tax/rates/{$rate->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Tax Name')
            ->assertJsonPath('data.rate', '8.75');

        $this->assertDatabaseHas('commerce_tax_rates', [
            'id' => $rate->id,
            'name' => 'Updated Tax Name',
            'rate' => 8.75,
        ]);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_tax_rate(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $rate = TaxRate::factory()->create(['tax_zone_id' => $zone->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/tax/rates/{$rate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_tax_rates', [
            'id' => $rate->id,
        ]);
    }

    // =========================================================================
    // Tax Exemption Tests
    // =========================================================================

    public function test_can_list_tax_exemptions(): void
    {
        TaxExemption::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tax/exemptions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'type', 'entity_id', 'certificate_number', 'is_active'],
                ],
                'pagination',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_create_customer_tax_exemption(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $exemptionData = [
            'name' => 'VIP Customer Exemption',
            'description' => 'Tax exemption for VIP customers',
            'type' => 'customer',
            'entity_id' => $customer->id,
            'certificate_number' => 'CERT-12345',
            'valid_from' => now()->toDateString(),
            'valid_until' => now()->addYear()->toDateString(),
            'country_code' => 'US',
            'state_code' => 'CA',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/exemptions', $exemptionData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', 'customer');

        $this->assertDatabaseHas('commerce_tax_exemptions', [
            'type' => 'customer',
            'entity_id' => $customer->id,
            'certificate_number' => 'CERT-12345',
        ]);
    }

    public function test_can_create_customer_group_tax_exemption(): void
    {
        $group = CustomerGroup::factory()->create(['store_id' => $this->store->id]);

        $exemptionData = [
            'name' => 'Wholesale Exemption',
            'type' => 'customer_group',
            'entity_id' => $group->id,
            'certificate_number' => 'CERT-WHOLESALE',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/exemptions', $exemptionData);

        $response->assertStatus(201)
            ->assertJsonPath('data.type', 'customer_group');

        $this->assertDatabaseHas('commerce_tax_exemptions', [
            'type' => 'customer_group',
            'entity_id' => $group->id,
        ]);
    }

    public function test_can_delete_tax_exemption(): void
    {
        $exemption = TaxExemption::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/tax/exemptions/{$exemption->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_tax_exemptions', [
            'id' => $exemption->id,
        ]);
    }

    // =========================================================================
    // Tax Calculation Tests
    // =========================================================================

    public function test_can_calculate_tax(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'name' => 'CA Sales Tax',
            'code' => 'CA_SALES',
            'rate' => 7.25,
            'type' => 'percentage',
            'compound' => false,
        ]);

        $requestData = [
            'address' => [
                'country_code' => 'US',
                'state_code' => 'CA',
            ],
            'cart_data' => [
                'subtotal' => 100.00,
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    'total_tax',
                    'tax_breakdown' => [
                        '*' => ['rate_id', 'rate_name', 'rate_code', 'tax_amount'],
                    ],
                    'zone_id',
                ],
            ]);

        // Tax should be 7.25% of $100 = $7.25
        $this->assertEquals(7.25, $response->json('data.total_tax'));
    }

    public function test_calculate_tax_with_compound_rates(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'CA', 'state_code' => 'BC']);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'name' => 'GST',
            'code' => 'GST',
            'rate' => 5.00,
            'type' => 'percentage',
            'compound' => false,
            'priority' => 1,
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'name' => 'PST',
            'code' => 'PST',
            'rate' => 7.00,
            'type' => 'percentage',
            'compound' => true,
            'priority' => 2,
        ]);

        $requestData = [
            'address' => ['country_code' => 'CA', 'state_code' => 'BC'],
            'cart_data' => ['subtotal' => 100.00],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/calculate', $requestData);

        $response->assertStatus(200);

        // GST: 100 * 5% = 5.00
        // PST (compound): (100 + 5) * 7% = 7.35
        // Total: 5.00 + 7.35 = 12.35
        $this->assertEquals(12.35, $response->json('data.total_tax'));
    }

    public function test_calculate_tax_with_fixed_rate(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'name' => 'Environmental Fee',
            'code' => 'ENV_FEE',
            'rate' => 5.00,
            'type' => 'fixed',
            'compound' => false,
        ]);

        $requestData = [
            'address' => ['country_code' => 'US', 'state_code' => 'CA'],
            'cart_data' => ['subtotal' => 100.00],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/calculate', $requestData);

        $response->assertStatus(200);

        // Fixed rate of $5.00
        $this->assertEquals(5.00, $response->json('data.total_tax'));
    }

    public function test_calculate_returns_zero_with_valid_exemption(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        TaxExemption::factory()->customer()->create([
            'store_id' => $this->store->id,
            'entity_id' => $customer->id,
            'valid_from' => now()->subDay(),
            'valid_until' => now()->addDay(),
            'country_code' => 'US',
            'state_code' => 'CA',
            'is_active' => true,
        ]);

        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'rate' => 7.25,
            'type' => 'percentage',
        ]);

        $requestData = [
            'address' => ['country_code' => 'US', 'state_code' => 'CA'],
            'cart_data' => ['subtotal' => 100.00],
            'customer_id' => $customer->id,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_tax', 0)
            ->assertJsonPath('data.exemption_applied', true);
    }

    public function test_calculate_returns_zero_when_no_matching_zone(): void
    {
        $requestData = [
            'address' => ['country_code' => 'ZZ', 'state_code' => 'XX'],
            'cart_data' => ['subtotal' => 100.00],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/calculate', $requestData);

        $response->assertStatus(200)
            ->assertJsonPath('data.total_tax', 0);
    }

    public function test_tax_calculation_respects_rate_priority(): void
    {
        $zone = TaxZone::factory()->create(['store_id' => $this->store->id]);
        $zone->locations()->create(['country_code' => 'US', 'state_code' => 'CA']);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'name' => 'State Tax',
            'code' => 'STATE',
            'rate' => 6.00,
            'type' => 'percentage',
            'compound' => false,
            'priority' => 1,
        ]);

        TaxRate::factory()->create([
            'tax_zone_id' => $zone->id,
            'name' => 'Local Tax',
            'code' => 'LOCAL',
            'rate' => 2.00,
            'type' => 'percentage',
            'compound' => false,
            'priority' => 2,
        ]);

        $requestData = [
            'address' => ['country_code' => 'US', 'state_code' => 'CA'],
            'cart_data' => ['subtotal' => 100.00],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tax/calculate', $requestData);

        $response->assertStatus(200);

        // Both rates should be applied: 6% + 2% = 8%
        $this->assertEquals(8.00, $response->json('data.total_tax'));

        // Verify the breakdown has both rates
        $breakdown = $response->json('data.tax_breakdown');
        $this->assertCount(2, $breakdown);
    }
}
