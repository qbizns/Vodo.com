<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Customer $customer;
    protected string $sessionId;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->customer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);
        $this->sessionId = Str::uuid()->toString();
    }

    // =========================================================================
    // Show Cart Tests
    // =========================================================================

    public function test_can_get_cart_for_guest_user(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'customer_id' => null,
        ]);

        $response = $this->getJson('/api/storefront/v2/cart', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $cart->id)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'items',
                    'item_count',
                    'is_empty',
                    'subtotal',
                    'discount_total',
                    'shipping_total',
                    'tax_total',
                    'total',
                    'currency',
                ],
            ]);
    }

    public function test_can_get_cart_for_authenticated_user(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/storefront/v2/cart');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'items',
                ],
            ]);
    }

    public function test_creates_new_cart_if_none_exists(): void
    {
        $response = $this->getJson('/api/storefront/v2/cart', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('commerce_carts', [
            'session_id' => $this->sessionId,
            'store_id' => $this->store->id,
        ]);
    }

    // =========================================================================
    // Add Item Tests
    // =========================================================================

    public function test_can_add_product_to_cart(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
            'stock_quantity' => 10,
            'track_inventory' => true,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Item added to cart successfully');

        $this->assertDatabaseHas('commerce_cart_items', [
            'product_id' => $product->id,
            'quantity' => 2,
        ]);
    }

    public function test_can_add_product_variant_to_cart(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 75.00,
            'stock_quantity' => 5,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('commerce_cart_items', [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ]);
    }

    public function test_can_add_product_with_options(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        $options = [
            'color' => 'Blue',
            'size' => 'Large',
        ];

        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => $product->id,
            'quantity' => 1,
            'options' => $options,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('commerce_cart_items', [
            'product_id' => $product->id,
        ]);

        $cartItem = CartItem::where('product_id', $product->id)->first();
        $this->assertEquals($options, $cartItem->options);
    }

    public function test_add_item_validation_requires_product_id(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'quantity' => 1,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Validation failed')
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_add_item_validation_requires_valid_quantity(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => $product->id,
            'quantity' => 0,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_add_item_fails_for_non_existent_product(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => 99999,
            'quantity' => 1,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422);
    }

    public function test_add_item_handles_out_of_stock_product(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 2,
            'track_inventory' => true,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => $product->id,
            'quantity' => 10,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    public function test_adding_existing_item_increases_quantity(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/items', [
            'product_id' => $product->id,
            'quantity' => 3,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);

        $this->assertEquals(1, CartItem::where('product_id', $product->id)->count());
        $this->assertEquals(5, CartItem::where('product_id', $product->id)->first()->quantity);
    }

    // =========================================================================
    // Update Item Tests
    // =========================================================================

    public function test_can_update_cart_item_quantity(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->putJson("/api/storefront/v2/cart/items/{$item->id}", [
            'quantity' => 5,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart item updated successfully');

        $this->assertEquals(5, $item->fresh()->quantity);
    }

    public function test_can_set_item_quantity_to_zero_to_remove_it(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'quantity' => 2,
        ]);

        $response = $this->putJson("/api/storefront/v2/cart/items/{$item->id}", [
            'quantity' => 0,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('commerce_cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_update_item_validation_requires_quantity(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
        ]);

        $response = $this->putJson("/api/storefront/v2/cart/items/{$item->id}", [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_update_item_fails_for_non_existent_item(): void
    {
        $response = $this->putJson('/api/storefront/v2/cart/items/99999', [
            'quantity' => 1,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(404);
    }

    public function test_update_item_handles_insufficient_stock(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 3,
            'track_inventory' => true,
        ]);

        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ]);

        $response = $this->putJson("/api/storefront/v2/cart/items/{$item->id}", [
            'quantity' => 10,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('success', false);
    }

    // =========================================================================
    // Remove Item Tests
    // =========================================================================

    public function test_can_remove_item_from_cart(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
        ]);

        $response = $this->deleteJson("/api/storefront/v2/cart/items/{$item->id}", [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Item removed from cart');

        $this->assertDatabaseMissing('commerce_cart_items', [
            'id' => $item->id,
        ]);
    }

    public function test_remove_item_fails_for_non_existent_item(): void
    {
        $response = $this->deleteJson('/api/storefront/v2/cart/items/99999', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Discount Code Tests
    // =========================================================================

    public function test_can_apply_discount_code(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'subtotal' => 100,
        ]);

        $discount = Discount::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'SAVE10',
            'type' => 'percentage',
            'value' => 10,
            'is_active' => true,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/discount', [
            'code' => 'SAVE10',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertContains('SAVE10', $cart->fresh()->discount_codes);
    }

    public function test_apply_discount_validation_requires_code(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/discount', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_apply_discount_fails_for_invalid_code(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/discount', [
            'code' => 'INVALID',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_can_remove_discount_code(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'discount_codes' => ['SAVE10'],
        ]);

        $response = $this->deleteJson('/api/storefront/v2/cart/discount', [
            'code' => 'SAVE10',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Discount code removed');

        $this->assertNotContains('SAVE10', $cart->fresh()->discount_codes ?? []);
    }

    public function test_remove_discount_validation_requires_code(): void
    {
        $response = $this->deleteJson('/api/storefront/v2/cart/discount', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    // =========================================================================
    // Address Tests
    // =========================================================================

    public function test_can_set_billing_address(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $address = [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'phone' => '1234567890',
            'address1' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'country' => 'US',
        ];

        $response = $this->postJson('/api/storefront/v2/cart/billing-address', $address, [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Billing address updated');

        $cart = Cart::where('session_id', $this->sessionId)->first();
        $this->assertEquals('John', $cart->billing_address['first_name']);
        $this->assertEquals('john@example.com', $cart->billing_address['email']);
    }

    public function test_billing_address_validation_requires_required_fields(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/billing-address', [
            'first_name' => 'John',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'email', 'address1', 'city', 'postal_code', 'country']);
    }

    public function test_billing_address_validation_requires_valid_email(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/billing-address', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'invalid-email',
            'address1' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'country' => 'US',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_billing_address_validation_requires_two_letter_country_code(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/billing-address', [
            'first_name' => 'John',
            'last_name' => 'Doe',
            'email' => 'john@example.com',
            'address1' => '123 Main St',
            'city' => 'New York',
            'postal_code' => '10001',
            'country' => 'USA',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['country']);
    }

    public function test_can_set_shipping_address(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $address = [
            'first_name' => 'Jane',
            'last_name' => 'Smith',
            'phone' => '0987654321',
            'address1' => '456 Oak Ave',
            'city' => 'Los Angeles',
            'postal_code' => '90001',
            'country' => 'US',
        ];

        $response = $this->postJson('/api/storefront/v2/cart/shipping-address', $address, [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Shipping address updated');

        $cart = Cart::where('session_id', $this->sessionId)->first();
        $this->assertEquals('Jane', $cart->shipping_address['first_name']);
    }

    public function test_shipping_address_validation_requires_required_fields(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/shipping-address', [
            'first_name' => 'Jane',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['last_name', 'address1', 'city', 'postal_code', 'country']);
    }

    // =========================================================================
    // Shipping Method Tests
    // =========================================================================

    public function test_can_set_shipping_method(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/shipping-method', [
            'method' => 'standard',
            'cost' => 10.00,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Shipping method updated');

        $cart = Cart::where('session_id', $this->sessionId)->first();
        $this->assertEquals('standard', $cart->shipping_method);
        $this->assertEquals(10.00, $cart->shipping_total);
    }

    public function test_shipping_method_validation_requires_method_and_cost(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/shipping-method', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['method', 'cost']);
    }

    public function test_shipping_method_validation_requires_non_negative_cost(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/shipping-method', [
            'method' => 'standard',
            'cost' => -5.00,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['cost']);
    }

    // =========================================================================
    // Notes Tests
    // =========================================================================

    public function test_can_set_cart_notes(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/notes', [
            'notes' => 'Please handle with care',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart notes updated');

        $cart = Cart::where('session_id', $this->sessionId)->first();
        $this->assertEquals('Please handle with care', $cart->notes);
    }

    public function test_can_clear_cart_notes(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'notes' => 'Some notes',
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/notes', [
            'notes' => null,
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);

        $cart = Cart::where('session_id', $this->sessionId)->first();
        $this->assertNull($cart->notes);
    }

    public function test_notes_validation_enforces_max_length(): void
    {
        $response = $this->postJson('/api/storefront/v2/cart/notes', [
            'notes' => str_repeat('a', 1001),
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['notes']);
    }

    // =========================================================================
    // Clear Cart Tests
    // =========================================================================

    public function test_can_clear_cart(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'subtotal' => 100,
            'total' => 100,
        ]);

        CartItem::factory()->count(3)->create([
            'cart_id' => $cart->id,
        ]);

        $response = $this->deleteJson('/api/storefront/v2/cart', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart cleared successfully');

        $this->assertEquals(0, CartItem::where('cart_id', $cart->id)->count());
        $cart->refresh();
        $this->assertEquals(0, $cart->subtotal);
        $this->assertEquals(0, $cart->total);
    }

    // =========================================================================
    // Validate Cart Tests
    // =========================================================================

    public function test_can_validate_cart_with_valid_items(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->getJson('/api/storefront/v2/cart/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart is valid');
    }

    public function test_validate_detects_out_of_stock_items(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 1,
            'track_inventory' => true,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 5,
        ]);

        $response = $this->getJson('/api/storefront/v2/cart/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Cart has validation errors');
    }

    // =========================================================================
    // Summary Tests
    // =========================================================================

    public function test_can_get_cart_summary(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $response = $this->getJson('/api/storefront/v2/cart/summary', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'item_count',
                    'subtotal',
                    'total',
                ],
            ]);
    }

    // =========================================================================
    // Sync Prices Tests
    // =========================================================================

    public function test_can_sync_cart_prices(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
        ]);

        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'unit_price' => 40.00,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/sync-prices', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Cart prices synchronized');

        $this->assertEquals(50.00, $item->fresh()->unit_price);
    }

    public function test_sync_prices_updates_variant_prices(): void
    {
        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
        ]);

        $variant = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'price' => 75.00,
        ]);

        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $item = CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'unit_price' => 60.00,
        ]);

        $response = $this->postJson('/api/storefront/v2/cart/sync-prices', [], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);

        $this->assertEquals(75.00, $item->fresh()->unit_price);
    }
}
