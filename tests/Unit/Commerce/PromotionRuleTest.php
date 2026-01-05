<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\PromotionRule;
use VodoCommerce\Models\Store;

class PromotionRuleTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
    }

    // =========================================================================
    // Cart Subtotal Rule Tests
    // =========================================================================

    public function test_cart_subtotal_greater_than(): void
    {
        $rule = PromotionRule::factory()->cartSubtotal(PromotionRule::OPERATOR_GREATER_THAN, '100')->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['cart' => ['subtotal' => 150]];

        $this->assertTrue($rule->evaluate($context));
    }

    public function test_cart_subtotal_less_than(): void
    {
        $rule = PromotionRule::factory()->cartSubtotal(PromotionRule::OPERATOR_LESS_THAN, '100')->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['cart' => ['subtotal' => 50]];

        $this->assertTrue($rule->evaluate($context));
    }

    public function test_cart_subtotal_between(): void
    {
        $rule = PromotionRule::factory()->create([
            'store_id' => $this->store->id,
            'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
            'operator' => PromotionRule::OPERATOR_BETWEEN,
            'value' => '100,200',
        ]);

        $context = ['cart' => ['subtotal' => 150]];

        $this->assertTrue($rule->evaluate($context));

        $context = ['cart' => ['subtotal' => 250]];

        $this->assertFalse($rule->evaluate($context));
    }

    // =========================================================================
    // Cart Quantity Rule Tests
    // =========================================================================

    public function test_cart_quantity_greater_than_or_equal(): void
    {
        $rule = PromotionRule::factory()->cartQuantity(PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, '5')->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['cart' => ['quantity' => 5]];

        $this->assertTrue($rule->evaluate($context));

        $context = ['cart' => ['quantity' => 10]];

        $this->assertTrue($rule->evaluate($context));

        $context = ['cart' => ['quantity' => 3]];

        $this->assertFalse($rule->evaluate($context));
    }

    // =========================================================================
    // Product Quantity Rule Tests
    // =========================================================================

    public function test_product_quantity_rule(): void
    {
        $rule = PromotionRule::factory()->productQuantity(1, PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, '2')->create([
            'store_id' => $this->store->id,
        ]);

        $context = [
            'cart' => [
                'items' => [
                    ['product_id' => 1, 'quantity' => 3],
                    ['product_id' => 2, 'quantity' => 1],
                ],
            ],
        ];

        $this->assertTrue($rule->evaluate($context));
    }

    public function test_product_quantity_rule_sums_across_items(): void
    {
        $rule = PromotionRule::factory()->productQuantity(1, PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, '5')->create([
            'store_id' => $this->store->id,
        ]);

        $context = [
            'cart' => [
                'items' => [
                    ['product_id' => 1, 'quantity' => 3],
                    ['product_id' => 1, 'quantity' => 2],
                ],
            ],
        ];

        $this->assertTrue($rule->evaluate($context));
    }

    // =========================================================================
    // Category Quantity Rule Tests
    // =========================================================================

    public function test_category_quantity_rule(): void
    {
        $rule = PromotionRule::factory()->categoryQuantity(10, PromotionRule::OPERATOR_GREATER_THAN_OR_EQUAL, '3')->create([
            'store_id' => $this->store->id,
        ]);

        $context = [
            'cart' => [
                'items' => [
                    ['product_id' => 1, 'quantity' => 2, 'category_ids' => [10, 20]],
                    ['product_id' => 2, 'quantity' => 2, 'category_ids' => [10]],
                ],
            ],
        ];

        $this->assertTrue($rule->evaluate($context));
    }

    // =========================================================================
    // Customer Group Rule Tests
    // =========================================================================

    public function test_customer_group_in_operator(): void
    {
        $rule = PromotionRule::factory()->customerGroup([1, 2, 3])->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['customer' => ['group_ids' => [2, 5]]];

        $this->assertTrue($rule->evaluate($context));

        $context = ['customer' => ['group_ids' => [4, 5]]];

        $this->assertFalse($rule->evaluate($context));
    }

    // =========================================================================
    // Shipping Country Rule Tests
    // =========================================================================

    public function test_shipping_country_equals(): void
    {
        $rule = PromotionRule::factory()->shippingCountry('US')->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['shipping' => ['country' => 'US']];

        $this->assertTrue($rule->evaluate($context));

        $context = ['shipping' => ['country' => 'CA']];

        $this->assertFalse($rule->evaluate($context));
    }

    public function test_shipping_country_in_multiple(): void
    {
        $rule = PromotionRule::factory()->shippingCountry(['US', 'CA', 'MX'])->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['shipping' => ['country' => 'CA']];

        $this->assertTrue($rule->evaluate($context));

        $context = ['shipping' => ['country' => 'UK']];

        $this->assertFalse($rule->evaluate($context));
    }

    // =========================================================================
    // Day of Week Rule Tests
    // =========================================================================

    public function test_day_of_week_rule(): void
    {
        $rule = PromotionRule::factory()->dayOfWeek([1, 2, 3])->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['datetime' => ['day_of_week' => 2]];

        $this->assertTrue($rule->evaluate($context));

        $context = ['datetime' => ['day_of_week' => 5]];

        $this->assertFalse($rule->evaluate($context));
    }

    // =========================================================================
    // Operator Tests
    // =========================================================================

    public function test_equals_operator(): void
    {
        $rule = PromotionRule::factory()->create([
            'store_id' => $this->store->id,
            'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
            'operator' => PromotionRule::OPERATOR_EQUALS,
            'value' => '100',
        ]);

        $this->assertTrue($rule->evaluate(['cart' => ['subtotal' => 100]]));
        $this->assertFalse($rule->evaluate(['cart' => ['subtotal' => 101]]));
    }

    public function test_not_equals_operator(): void
    {
        $rule = PromotionRule::factory()->create([
            'store_id' => $this->store->id,
            'rule_type' => PromotionRule::RULE_CART_SUBTOTAL,
            'operator' => PromotionRule::OPERATOR_NOT_EQUALS,
            'value' => '100',
        ]);

        $this->assertTrue($rule->evaluate(['cart' => ['subtotal' => 50]]));
        $this->assertFalse($rule->evaluate(['cart' => ['subtotal' => 100]]));
    }

    public function test_contains_operator_with_string(): void
    {
        $rule = PromotionRule::factory()->create([
            'store_id' => $this->store->id,
            'rule_type' => PromotionRule::RULE_SHIPPING_COUNTRY,
            'operator' => PromotionRule::OPERATOR_CONTAINS,
            'value' => 'US',
        ]);

        $this->assertTrue($rule->evaluate(['shipping' => ['country' => 'USA']]));
        $this->assertFalse($rule->evaluate(['shipping' => ['country' => 'Canada']]));
    }

    public function test_in_operator_with_array(): void
    {
        $rule = PromotionRule::factory()->create([
            'store_id' => $this->store->id,
            'rule_type' => PromotionRule::RULE_SHIPPING_COUNTRY,
            'operator' => PromotionRule::OPERATOR_IN,
            'value' => 'US,CA,MX',
        ]);

        $this->assertTrue($rule->evaluate(['shipping' => ['country' => 'CA']]));
        $this->assertFalse($rule->evaluate(['shipping' => ['country' => 'UK']]));
    }

    public function test_not_in_operator(): void
    {
        $rule = PromotionRule::factory()->create([
            'store_id' => $this->store->id,
            'rule_type' => PromotionRule::RULE_SHIPPING_COUNTRY,
            'operator' => PromotionRule::OPERATOR_NOT_IN,
            'value' => 'US,CA',
        ]);

        $this->assertTrue($rule->evaluate(['shipping' => ['country' => 'UK']]));
        $this->assertFalse($rule->evaluate(['shipping' => ['country' => 'US']]));
    }

    // =========================================================================
    // Edge Cases
    // =========================================================================

    public function test_returns_false_when_context_value_missing(): void
    {
        $rule = PromotionRule::factory()->cartSubtotal()->create([
            'store_id' => $this->store->id,
        ]);

        $context = ['cart' => []]; // Missing subtotal

        $this->assertFalse($rule->evaluate($context));
    }

    public function test_handles_null_context_gracefully(): void
    {
        $rule = PromotionRule::factory()->cartSubtotal()->create([
            'store_id' => $this->store->id,
        ]);

        $context = [];

        $this->assertFalse($rule->evaluate($context));
    }
}
