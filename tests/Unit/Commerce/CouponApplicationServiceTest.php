<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\CouponApplicationService;
use VodoCommerce\Services\PromotionEngine;

class CouponApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CouponApplicationService $service;
    protected Store $store;
    protected Cart $cart;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $promotionEngine = new PromotionEngine();
        $this->service = new CouponApplicationService($promotionEngine);

        $this->store = Store::factory()->create();
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
        $this->cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'subtotal' => 100.00,
        ]);
    }

    // =========================================================================
    // Validate Coupon Tests
    // =========================================================================

    public function test_validates_valid_coupon(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'VALID10',
            'is_active' => true,
        ]);

        $result = $this->service->validateCoupon('VALID10', $this->cart, $this->customer->id);

        $this->assertTrue($result['valid']);
        $this->assertInstanceOf(Discount::class, $result['discount']);
    }

    public function test_invalidates_nonexistent_coupon(): void
    {
        $result = $this->service->validateCoupon('NOTFOUND', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertNull($result['discount']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    public function test_invalidates_inactive_coupon(): void
    {
        Discount::factory()->inactive()->create([
            'store_id' => $this->store->id,
            'code' => 'INACTIVE',
        ]);

        $result = $this->service->validateCoupon('INACTIVE', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('no longer valid', $result['message']);
    }

    public function test_invalidates_expired_coupon(): void
    {
        Discount::factory()->expired()->create([
            'store_id' => $this->store->id,
            'code' => 'EXPIRED',
        ]);

        $result = $this->service->validateCoupon('EXPIRED', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
    }

    public function test_validates_minimum_order_requirement(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'MIN200',
            'minimum_order' => 200.00,
        ]);

        $this->cart->update(['subtotal' => 100.00]);

        $result = $this->service->validateCoupon('MIN200', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('200', $result['message']);
    }

    public function test_validates_customer_eligibility(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'GROUP123',
            'customer_eligibility' => Discount::ELIGIBILITY_SPECIFIC_CUSTOMERS,
            'allowed_customer_ids' => [999], // Not our customer
        ]);

        $result = $this->service->validateCoupon('GROUP123', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not eligible', $result['message']);
    }

    public function test_validates_first_order_requirement(): void
    {
        $this->customer->update(['total_orders' => 5]);

        Discount::factory()->firstOrderOnly()->create([
            'store_id' => $this->store->id,
            'code' => 'FIRST20',
        ]);

        $result = $this->service->validateCoupon('FIRST20', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('first-time', $result['message']);
    }

    // =========================================================================
    // Apply Coupon Tests
    // =========================================================================

    public function test_applies_valid_coupon(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'APPLY10',
        ]);

        $result = $this->service->applyCoupon('APPLY10', $this->cart, $this->customer->id);

        $this->assertTrue($result['valid']);
        $this->assertContains('APPLY10', $result['cart']->discount_codes);
    }

    public function test_cannot_apply_same_coupon_twice(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'TWICE',
        ]);

        $this->service->applyCoupon('TWICE', $this->cart, $this->customer->id);
        $result = $this->service->applyCoupon('TWICE', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('already applied', $result['message']);
    }

    public function test_cannot_stack_non_stackable_coupons(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'FIRST',
            'is_stackable' => false,
        ]);

        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'SECOND',
            'is_stackable' => false,
        ]);

        $this->service->applyCoupon('FIRST', $this->cart, $this->customer->id);
        $result = $this->service->applyCoupon('SECOND', $this->cart, $this->customer->id);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('cannot be combined', $result['message']);
    }

    // =========================================================================
    // Remove Coupon Tests
    // =========================================================================

    public function test_removes_coupon_from_cart(): void
    {
        $this->cart->update(['discount_codes' => ['REMOVE']]);

        $result = $this->service->removeCoupon('REMOVE', $this->cart);

        $this->assertTrue($result['valid']);
        $this->assertNotContains('REMOVE', $result['cart']->discount_codes ?? []);
    }

    public function test_cannot_remove_nonexistent_coupon(): void
    {
        $result = $this->service->removeCoupon('NOTHERE', $this->cart);

        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('not found', $result['message']);
    }

    // =========================================================================
    // Record Usage Tests
    // =========================================================================

    public function test_records_coupon_usage(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'TRACK',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->service->recordUsage(
            $order->id,
            $this->store->id,
            $this->customer->id,
            ['TRACK'],
            100.00,
            []
        );

        $this->assertDatabaseHas('commerce_coupon_usages', [
            'discount_id' => $discount->id,
            'customer_id' => $this->customer->id,
            'order_id' => $order->id,
            'discount_code' => 'TRACK',
        ]);
    }

    public function test_records_usage_for_multiple_coupons(): void
    {
        $discount1 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'MULTI1',
        ]);

        $discount2 = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'MULTI2',
        ]);

        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->service->recordUsage(
            $order->id,
            $this->store->id,
            $this->customer->id,
            ['MULTI1', 'MULTI2'],
            100.00,
            []
        );

        $this->assertDatabaseHas('commerce_coupon_usages', ['discount_code' => 'MULTI1']);
        $this->assertDatabaseHas('commerce_coupon_usages', ['discount_code' => 'MULTI2']);
    }

    public function test_increments_discount_usage_count(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'INCREMENT',
            'current_usage' => 5,
        ]);

        $order = Order::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $this->service->recordUsage(
            $order->id,
            $this->store->id,
            null,
            ['INCREMENT'],
            100.00,
            []
        );

        $discount->refresh();
        $this->assertEquals(6, $discount->current_usage);
    }

    // =========================================================================
    // Get Automatic Discounts Tests
    // =========================================================================

    public function test_gets_automatic_discounts(): void
    {
        Discount::factory()->automatic()->create([
            'store_id' => $this->store->id,
            'code' => 'AUTO1',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        $automaticDiscounts = $this->service->getAutomaticDiscounts($this->cart, $this->customer->id);

        $this->assertNotEmpty($automaticDiscounts);
    }

    public function test_automatic_discounts_exclude_manual_coupons(): void
    {
        Discount::factory()->automatic()->create([
            'store_id' => $this->store->id,
            'code' => 'AUTO',
        ]);

        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'MANUAL',
            'is_automatic' => false,
        ]);

        $automaticDiscounts = $this->service->getAutomaticDiscounts($this->cart, $this->customer->id);

        $codes = array_column($automaticDiscounts, 'discount');
        $codes = array_map(fn($d) => $d->code, $codes);

        $this->assertContains('AUTO', $codes);
        $this->assertNotContains('MANUAL', $codes);
    }

    public function test_automatic_discounts_respect_first_order_requirement(): void
    {
        $this->customer->update(['total_orders' => 5]);

        Discount::factory()->automatic()->firstOrderOnly()->create([
            'store_id' => $this->store->id,
            'code' => 'FIRST_AUTO',
        ]);

        $automaticDiscounts = $this->service->getAutomaticDiscounts($this->cart, $this->customer->id);

        $this->assertEmpty($automaticDiscounts);
    }

    // =========================================================================
    // Integration Tests
    // =========================================================================

    public function test_full_coupon_lifecycle(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'LIFECYCLE',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        // 1. Validate
        $validation = $this->service->validateCoupon('LIFECYCLE', $this->cart, $this->customer->id);
        $this->assertTrue($validation['valid']);

        // 2. Apply
        $application = $this->service->applyCoupon('LIFECYCLE', $this->cart, $this->customer->id);
        $this->assertTrue($application['valid']);

        // 3. Record usage
        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

        $this->service->recordUsage(
            $order->id,
            $this->store->id,
            $this->customer->id,
            ['LIFECYCLE'],
            100.00,
            []
        );

        // 4. Verify usage tracked
        $this->assertDatabaseHas('commerce_coupon_usages', [
            'discount_code' => 'LIFECYCLE',
            'order_id' => $order->id,
        ]);

        // 5. Remove
        $removal = $this->service->removeCoupon('LIFECYCLE', $this->cart);
        $this->assertTrue($removal['valid']);
    }
}
