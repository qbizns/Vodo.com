<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\PromotionRule;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\PromotionEngine;

class PromotionEngineTest extends TestCase
{
    use RefreshDatabase;

    protected PromotionEngine $engine;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->engine = new PromotionEngine();
        $this->store = Store::factory()->create();
    }

    // =========================================================================
    // Buy X Get Y Tests
    // =========================================================================

    public function test_calculates_buy_x_get_y_discount(): void
    {
        $discount = Discount::factory()->buyXGetY()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'buy_quantity' => 2,
                'get_quantity' => 1,
                'get_discount_percent' => 100, // Free
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 3, 'price' => 50.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 150.00);

        $this->assertEquals(50.00, $result['amount']); // 1 free item
        $this->assertEquals('buy_x_get_y', $result['details']['type']);
        $this->assertEquals(1, $result['details']['sets_qualified']);
    }

    public function test_buy_x_get_y_discounts_cheapest_items(): void
    {
        $discount = Discount::factory()->buyXGetY()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'buy_quantity' => 1,
                'get_quantity' => 1,
                'get_discount_percent' => 50, // 50% off
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 100.00],
            ['product_id' => 2, 'quantity' => 1, 'price' => 50.00], // Cheaper, should get discount
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 150.00);

        $this->assertEquals(25.00, $result['amount']); // 50% off $50 item
    }

    public function test_buy_x_get_y_respects_max_applications(): void
    {
        $discount = Discount::factory()->buyXGetY()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'buy_quantity' => 1,
                'get_quantity' => 1,
                'get_discount_percent' => 100,
                'max_applications' => 1,
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 10, 'price' => 10.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 100.00);

        $this->assertEquals(10.00, $result['amount']); // Only 1 application
        $this->assertEquals(1, $result['details']['sets_qualified']);
    }

    // =========================================================================
    // Tiered Discount Tests
    // =========================================================================

    public function test_calculates_tiered_discount(): void
    {
        $discount = Discount::factory()->tiered()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'tiers' => [
                    ['threshold' => 50, 'discount_percent' => 5],
                    ['threshold' => 100, 'discount_percent' => 10],
                    ['threshold' => 200, 'discount_percent' => 15],
                ],
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 2, 'price' => 60.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 120.00);

        $this->assertEquals(12.00, $result['amount']); // 10% of 120
        $this->assertEquals('tiered', $result['details']['type']);
        $this->assertEquals(100, $result['details']['tier_reached']['threshold']);
    }

    public function test_tiered_discount_selects_highest_qualifying_tier(): void
    {
        $discount = Discount::factory()->tiered()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'tiers' => [
                    ['threshold' => 50, 'discount_percent' => 5],
                    ['threshold' => 100, 'discount_percent' => 10],
                    ['threshold' => 200, 'discount_percent' => 15],
                ],
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 250.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 250.00);

        $this->assertEquals(37.50, $result['amount']); // 15% of 250
        $this->assertEquals(200, $result['details']['tier_reached']['threshold']);
    }

    public function test_tiered_discount_returns_zero_if_no_tier_reached(): void
    {
        $discount = Discount::factory()->tiered()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'tiers' => [
                    ['threshold' => 100, 'discount_percent' => 10],
                ],
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 50.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 50.00);

        $this->assertEquals(0, $result['amount']);
        $this->assertNull($result['details']['tier_reached']);
    }

    // =========================================================================
    // Bundle Discount Tests
    // =========================================================================

    public function test_calculates_bundle_discount(): void
    {
        $discount = Discount::factory()->bundle()->create([
            'store_id' => $this->store->id,
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 20,
            'target_config' => [
                'required_products' => [1, 2, 3],
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 50.00],
            ['product_id' => 2, 'quantity' => 1, 'price' => 30.00],
            ['product_id' => 3, 'quantity' => 1, 'price' => 20.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 100.00);

        $this->assertEquals(20.00, $result['amount']); // 20% of 100
        $this->assertTrue($result['details']['bundle_complete']);
    }

    public function test_bundle_discount_requires_all_products(): void
    {
        $discount = Discount::factory()->bundle()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'required_products' => [1, 2, 3],
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 50.00],
            ['product_id' => 2, 'quantity' => 1, 'price' => 30.00],
            // Missing product 3
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 80.00);

        $this->assertEquals(0, $result['amount']);
        $this->assertFalse($result['details']['bundle_complete']);
    }

    // =========================================================================
    // Free Gift Tests
    // =========================================================================

    public function test_free_gift_qualifies_when_minimum_met(): void
    {
        $discount = Discount::factory()->freeGift()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'free_product_ids' => [99],
                'minimum_purchase' => 100,
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 150.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 150.00);

        $this->assertEquals(0, $result['amount']); // Free gift doesn't reduce cart total
        $this->assertTrue($result['details']['qualified']);
        $this->assertEquals([99], $result['details']['free_product_ids']);
    }

    public function test_free_gift_does_not_qualify_below_minimum(): void
    {
        $discount = Discount::factory()->freeGift()->create([
            'store_id' => $this->store->id,
            'target_config' => [
                'free_product_ids' => [99],
                'minimum_purchase' => 100,
            ],
        ]);

        $cartItems = [
            ['product_id' => 1, 'quantity' => 1, 'price' => 50.00],
        ];

        $result = $this->engine->calculateAdvancedDiscount($discount, $cartItems, 50.00);

        $this->assertEquals(0, $result['amount']);
        $this->assertFalse($result['details']['qualified']);
    }

    // =========================================================================
    // Automatic Discount Finding Tests
    // =========================================================================

    public function test_finds_automatic_discounts(): void
    {
        Discount::factory()->automatic()->create([
            'store_id' => $this->store->id,
            'code' => 'AUTO1',
            'is_active' => true,
        ]);

        Discount::factory()->automatic()->create([
            'store_id' => $this->store->id,
            'code' => 'AUTO2',
            'is_active' => true,
        ]);

        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'MANUAL',
            'is_automatic' => false,
        ]);

        $context = [
            'cart' => ['subtotal' => 100, 'quantity' => 5, 'items' => []],
        ];

        $automaticDiscounts = $this->engine->findAutomaticDiscounts($this->store->id, $context);

        $this->assertCount(2, $automaticDiscounts);
    }

    public function test_automatic_discounts_respect_date_range(): void
    {
        Discount::factory()->automatic()->expired()->create([
            'store_id' => $this->store->id,
            'code' => 'EXPIRED_AUTO',
        ]);

        $context = [
            'cart' => ['subtotal' => 100, 'quantity' => 5, 'items' => []],
        ];

        $automaticDiscounts = $this->engine->findAutomaticDiscounts($this->store->id, $context);

        $this->assertCount(0, $automaticDiscounts);
    }

    // =========================================================================
    // Stacking Logic Tests
    // =========================================================================

    public function test_applies_stacking_logic_with_priority(): void
    {
        $discount1 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'FIRST',
            'type' => Discount::TYPE_FIXED,
            'value' => 10,
            'is_stackable' => true,
            'priority' => 1,
        ]);

        $discount2 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'SECOND',
            'type' => Discount::TYPE_FIXED,
            'value' => 5,
            'is_stackable' => true,
            'priority' => 2,
        ]);

        $discounts = collect([$discount1, $discount2]);
        $cartItems = [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]];

        $result = $this->engine->applyStackingLogic($discounts, $cartItems, 100.00);

        $this->assertEquals(15.00, $result['total_discount']);
        $this->assertCount(2, $result['applied_discounts']);
    }

    public function test_stacking_logic_stops_at_stop_further_rules(): void
    {
        $discount1 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'STOP',
            'type' => Discount::TYPE_FIXED,
            'value' => 10,
            'stop_further_rules' => true,
            'priority' => 1,
        ]);

        $discount2 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'AFTER',
            'type' => Discount::TYPE_FIXED,
            'value' => 5,
            'priority' => 2,
        ]);

        $discounts = collect([$discount1, $discount2]);
        $cartItems = [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]];

        $result = $this->engine->applyStackingLogic($discounts, $cartItems, 100.00);

        $this->assertEquals(10.00, $result['total_discount']);
        $this->assertCount(1, $result['applied_discounts']);
        $this->assertEquals('STOP', $result['applied_discounts'][0]['code']);
    }

    public function test_non_stackable_discount_prevents_multiple_discounts(): void
    {
        $discount1 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'FIRST',
            'type' => Discount::TYPE_FIXED,
            'value' => 10,
            'is_stackable' => false,
            'priority' => 1,
        ]);

        $discount2 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'SECOND',
            'type' => Discount::TYPE_FIXED,
            'value' => 20,
            'is_stackable' => false,
            'priority' => 2,
        ]);

        $discounts = collect([$discount1, $discount2]);
        $cartItems = [['product_id' => 1, 'quantity' => 1, 'price' => 100.00]];

        $result = $this->engine->applyStackingLogic($discounts, $cartItems, 100.00);

        $this->assertEquals(10.00, $result['total_discount']);
        $this->assertCount(1, $result['applied_discounts']);
    }

    // =========================================================================
    // Promotion Rule Evaluation Tests
    // =========================================================================

    public function test_evaluates_promotion_rules(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $rule = PromotionRule::factory()->cartSubtotal('greater_than', '100')->create([
            'store_id' => $this->store->id,
            'discount_id' => $discount->id,
        ]);

        $discount->setRelation('rules', collect([$rule]));

        $context = [
            'cart' => ['subtotal' => 150, 'quantity' => 5, 'items' => []],
        ];

        $result = $this->engine->evaluatePromotionRules($discount, $context);

        $this->assertTrue($result);
    }

    public function test_all_rules_must_pass(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $rule1 = PromotionRule::factory()->cartSubtotal('greater_than', '100')->create([
            'store_id' => $this->store->id,
            'discount_id' => $discount->id,
        ]);

        $rule2 = PromotionRule::factory()->cartQuantity('greater_than_or_equal', '10')->create([
            'store_id' => $this->store->id,
            'discount_id' => $discount->id,
        ]);

        $discount->setRelation('rules', collect([$rule1, $rule2]));

        $context = [
            'cart' => ['subtotal' => 150, 'quantity' => 5, 'items' => []],
        ];

        $result = $this->engine->evaluatePromotionRules($discount, $context);

        $this->assertFalse($result); // Rule 2 fails (5 < 10)
    }
}
