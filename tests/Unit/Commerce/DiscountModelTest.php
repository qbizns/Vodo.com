<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Store;

class DiscountModelTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
    }

    // =========================================================================
    // isAdvancedPromotion() Tests
    // =========================================================================

    public function test_identifies_advanced_promotion_types(): void
    {
        $buyXGetY = Discount::factory()->buyXGetY()->create(['store_id' => $this->store->id]);
        $tiered = Discount::factory()->tiered()->create(['store_id' => $this->store->id]);
        $bundle = Discount::factory()->bundle()->create(['store_id' => $this->store->id]);
        $freeGift = Discount::factory()->freeGift()->create(['store_id' => $this->store->id]);
        $standard = Discount::factory()->create(['store_id' => $this->store->id]);

        $this->assertTrue($buyXGetY->isAdvancedPromotion());
        $this->assertTrue($tiered->isAdvancedPromotion());
        $this->assertTrue($bundle->isAdvancedPromotion());
        $this->assertTrue($freeGift->isAdvancedPromotion());
        $this->assertFalse($standard->isAdvancedPromotion());
    }

    // =========================================================================
    // appliesToProducts() Tests
    // =========================================================================

    public function test_applies_to_all_products_by_default(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'applies_to' => Discount::APPLIES_TO_ALL,
        ]);

        $this->assertTrue($discount->appliesToProducts([1, 2, 3]));
    }

    public function test_applies_to_specific_products(): void
    {
        $discount = Discount::factory()->specificProducts([1, 2, 3])->create([
            'store_id' => $this->store->id,
        ]);

        $this->assertTrue($discount->appliesToProducts([1]));
        $this->assertTrue($discount->appliesToProducts([2, 3]));
        $this->assertFalse($discount->appliesToProducts([4, 5]));
    }

    public function test_excludes_specific_products(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'applies_to' => Discount::APPLIES_TO_SPECIFIC_PRODUCTS,
            'included_product_ids' => [1, 2, 3, 4, 5],
            'excluded_product_ids' => [3, 4],
        ]);

        $this->assertTrue($discount->appliesToProducts([1, 2]));
        $this->assertFalse($discount->appliesToProducts([3]));
        $this->assertFalse($discount->appliesToProducts([4]));
    }

    // =========================================================================
    // isCustomerEligible() Tests
    // =========================================================================

    public function test_eligible_for_all_customers(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'customer_eligibility' => Discount::ELIGIBILITY_ALL,
        ]);

        $this->assertTrue($discount->isCustomerEligible(1, [1, 2]));
        $this->assertTrue($discount->isCustomerEligible(null, []));
    }

    public function test_eligible_for_new_customers_only(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'customer_eligibility' => Discount::ELIGIBILITY_NEW_CUSTOMERS,
        ]);

        // Guest customer (no ID) should be eligible
        $this->assertTrue($discount->isCustomerEligible(null, []));

        // Existing customer needs requiresFirstOrder check (tested separately)
        // This test just checks the eligibility enum
        $this->assertFalse($discount->isCustomerEligible(1, []));
    }

    public function test_eligible_for_specific_customer_groups(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'customer_eligibility' => Discount::ELIGIBILITY_SPECIFIC_GROUPS,
            'allowed_customer_group_ids' => [1, 2, 3],
        ]);

        $this->assertTrue($discount->isCustomerEligible(1, [2, 5])); // Has group 2
        $this->assertFalse($discount->isCustomerEligible(1, [4, 5])); // No matching groups
    }

    public function test_eligible_for_specific_customers(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'customer_eligibility' => Discount::ELIGIBILITY_SPECIFIC_CUSTOMERS,
            'allowed_customer_ids' => [1, 2, 3],
        ]);

        $this->assertTrue($discount->isCustomerEligible(2, []));
        $this->assertFalse($discount->isCustomerEligible(5, []));
        $this->assertFalse($discount->isCustomerEligible(null, [])); // Guest not allowed
    }

    // =========================================================================
    // requiresFirstOrder() Tests
    // =========================================================================

    public function test_first_order_requirement(): void
    {
        $discount = Discount::factory()->firstOrderOnly()->create([
            'store_id' => $this->store->id,
        ]);

        // New customer with no orders
        $newCustomer = Customer::factory()->create([
            'store_id' => $this->store->id,
            'total_orders' => 0,
        ]);

        // Existing customer with orders
        $existingCustomer = Customer::factory()->withOrders(5)->create([
            'store_id' => $this->store->id,
        ]);

        $this->assertTrue($discount->requiresFirstOrder($newCustomer->id));
        $this->assertFalse($discount->requiresFirstOrder($existingCustomer->id));
    }

    public function test_guest_customers_eligible_for_first_order(): void
    {
        $discount = Discount::factory()->firstOrderOnly()->create([
            'store_id' => $this->store->id,
        ]);

        $this->assertTrue($discount->requiresFirstOrder(null)); // Guest = first order
    }

    // =========================================================================
    // Scopes Tests
    // =========================================================================

    public function test_automatic_scope(): void
    {
        Discount::factory()->automatic()->create(['store_id' => $this->store->id]);
        Discount::factory()->automatic()->create(['store_id' => $this->store->id]);
        Discount::factory()->create(['store_id' => $this->store->id, 'is_automatic' => false]);

        $automaticDiscounts = Discount::automatic()->get();

        $this->assertCount(2, $automaticDiscounts);
    }

    public function test_by_priority_scope(): void
    {
        Discount::factory()->create(['store_id' => $this->store->id, 'priority' => 3]);
        Discount::factory()->create(['store_id' => $this->store->id, 'priority' => 1]);
        Discount::factory()->create(['store_id' => $this->store->id, 'priority' => 2]);

        $discounts = Discount::byPriority()->get();

        $this->assertEquals(1, $discounts->first()->priority);
        $this->assertEquals(3, $discounts->last()->priority);
    }

    // =========================================================================
    // Relationships Tests
    // =========================================================================

    public function test_has_usages_relationship(): void
    {
        $discount = Discount::factory()->create(['store_id' => $this->store->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $discount->usages());
    }

    public function test_has_rules_relationship(): void
    {
        $discount = Discount::factory()->create(['store_id' => $this->store->id]);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $discount->rules());
    }

    // =========================================================================
    // Backward Compatibility Tests
    // =========================================================================

    public function test_existing_methods_still_work(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'is_active' => true,
            'starts_at' => now()->subDays(1),
            'expires_at' => now()->addDays(1),
            'usage_limit' => 100,
            'current_usage' => 10,
        ]);

        // Test existing methods
        $this->assertTrue($discount->isValid());
        $this->assertTrue($discount->isApplicable(100, null));

        $discountAmount = $discount->calculateDiscount(100);
        $this->assertGreaterThan(0, $discountAmount);
    }

    public function test_standard_discount_without_phase4_2_fields(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'STANDARD',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
            // No Phase 4.2 fields set
        ]);

        $this->assertFalse($discount->isAdvancedPromotion());
        $this->assertEquals(Discount::APPLIES_TO_ALL, $discount->applies_to);
        $this->assertEquals(Discount::ELIGIBILITY_ALL, $discount->customer_eligibility);
        $this->assertFalse($discount->first_order_only);
        $this->assertFalse($discount->is_stackable);
        $this->assertFalse($discount->is_automatic);
    }

    // =========================================================================
    // Constants Tests
    // =========================================================================

    public function test_promotion_type_constants_defined(): void
    {
        $this->assertEquals('buy_x_get_y', Discount::PROMOTION_BUY_X_GET_Y);
        $this->assertEquals('bundle', Discount::PROMOTION_BUNDLE);
        $this->assertEquals('tiered', Discount::PROMOTION_TIERED);
        $this->assertEquals('free_gift', Discount::PROMOTION_FREE_GIFT);
    }

    public function test_applies_to_constants_defined(): void
    {
        $this->assertEquals('all', Discount::APPLIES_TO_ALL);
        $this->assertEquals('specific_products', Discount::APPLIES_TO_SPECIFIC_PRODUCTS);
        $this->assertEquals('specific_categories', Discount::APPLIES_TO_SPECIFIC_CATEGORIES);
        $this->assertEquals('specific_brands', Discount::APPLIES_TO_SPECIFIC_BRANDS);
    }

    public function test_eligibility_constants_defined(): void
    {
        $this->assertEquals('all', Discount::ELIGIBILITY_ALL);
        $this->assertEquals('new_customers_only', Discount::ELIGIBILITY_NEW_CUSTOMERS);
        $this->assertEquals('specific_groups', Discount::ELIGIBILITY_SPECIFIC_GROUPS);
        $this->assertEquals('specific_customers', Discount::ELIGIBILITY_SPECIFIC_CUSTOMERS);
    }
}
