<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;

class CouponControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Cart $cart;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
        $this->cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    // =========================================================================
    // Validate Coupon Tests
    // =========================================================================

    public function test_can_validate_active_coupon(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'SAVE20',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 20,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'code' => 'SAVE20',
                'cart_id' => $this->cart->id,
                'customer_id' => $this->customer->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.valid', true)
            ->assertJsonStructure([
                'data' => [
                    'valid',
                    'message',
                    'discount' => ['id', 'code', 'name', 'type', 'value'],
                ],
            ]);
    }

    public function test_cannot_validate_invalid_coupon_code(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'code' => 'INVALID',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_cannot_validate_inactive_coupon(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'INACTIVE',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'code' => 'INACTIVE',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_validate_expired_coupon(): void
    {
        Discount::factory()->expired()->create([
            'store_id' => $this->store->id,
            'code' => 'EXPIRED',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'code' => 'EXPIRED',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_validates_minimum_order_requirement(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'MIN100',
            'minimum_order' => 100.00,
        ]);

        $this->cart->update(['subtotal' => 50.00]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'code' => 'MIN100',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Apply Coupon Tests
    // =========================================================================

    public function test_can_apply_valid_coupon_to_cart(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'APPLY10',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        $this->cart->update(['subtotal' => 100.00]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/apply', [
                'code' => 'APPLY10',
                'cart_id' => $this->cart->id,
                'customer_id' => $this->customer->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->cart->refresh();
        $this->assertContains('APPLY10', $this->cart->discount_codes);
    }

    public function test_cannot_apply_same_coupon_twice(): void
    {
        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'TWICE',
        ]);

        $this->cart->update(['discount_codes' => ['TWICE']]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/apply', [
                'code' => 'TWICE',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422);
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

        $this->cart->update(['discount_codes' => ['FIRST']]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/apply', [
                'code' => 'SECOND',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_can_stack_stackable_coupons(): void
    {
        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'STACK1',
            'is_stackable' => true,
            'priority' => 1,
        ]);

        Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'STACK2',
            'is_stackable' => true,
            'priority' => 2,
        ]);

        $this->cart->update(['discount_codes' => ['STACK1'], 'subtotal' => 100.00]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/apply', [
                'code' => 'STACK2',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(200);

        $this->cart->refresh();
        $this->assertCount(2, $this->cart->discount_codes);
    }

    // =========================================================================
    // Remove Coupon Tests
    // =========================================================================

    public function test_can_remove_coupon_from_cart(): void
    {
        $this->cart->update(['discount_codes' => ['REMOVE']]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/remove', [
                'code' => 'REMOVE',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->cart->refresh();
        $this->assertNotContains('REMOVE', $this->cart->discount_codes ?? []);
    }

    public function test_cannot_remove_nonexistent_coupon(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/remove', [
                'code' => 'NOTHERE',
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Automatic Discounts Tests
    // =========================================================================

    public function test_can_get_automatic_discounts_for_cart(): void
    {
        Discount::factory()->automatic()->create([
            'store_id' => $this->store->id,
            'code' => 'AUTO10',
            'type' => Discount::TYPE_PERCENTAGE,
            'value' => 10,
        ]);

        $this->cart->update(['subtotal' => 100.00]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/carts/{$this->cart->id}/automatic-discounts");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'automatic_discounts',
                    'count',
                ],
            ]);
    }

    public function test_automatic_discounts_respect_eligibility(): void
    {
        Discount::factory()->automatic()->firstOrderOnly()->create([
            'store_id' => $this->store->id,
            'code' => 'FIRSTORDER',
        ]);

        // Customer with existing orders shouldn't see first order discount
        $this->customer->update(['total_orders' => 5]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/carts/{$this->cart->id}/automatic-discounts?customer_id={$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.count', 0);
    }

    // =========================================================================
    // Validation Tests
    // =========================================================================

    public function test_validate_requires_code(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'cart_id' => $this->cart->id,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_validate_requires_cart_id(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/validate', [
                'code' => 'TEST',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cart_id']);
    }

    public function test_apply_requires_valid_cart(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/coupons/apply', [
                'code' => 'TEST',
                'cart_id' => 99999,
            ]);

        $response->assertStatus(422);
    }
}
