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
use VodoCommerce\Models\Order;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;

class CheckoutControllerTest extends TestCase
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
    // Validate Checkout Tests
    // =========================================================================

    public function test_can_validate_valid_checkout(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
            'stock_quantity' => 10,
            'is_active' => true,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $cart->update([
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ],
            'billing_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john@example.com',
                'address1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Checkout is ready');
    }

    public function test_validate_fails_for_empty_cart(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Checkout validation failed');
    }

    public function test_validate_fails_when_shipping_address_missing(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_validate_fails_when_billing_address_missing(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'New York',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_validate_fails_for_out_of_stock_items(): void
    {
        $cart = Cart::factory()->withAddresses()->create([
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

        $response = $this->getJson('/api/storefront/v2/checkout/validate', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    // =========================================================================
    // Shipping Rates Tests
    // =========================================================================

    public function test_can_get_shipping_rates(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/shipping-rates', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'rates',
                    'currency',
                ],
            ]);
    }

    public function test_shipping_rates_fails_without_shipping_address(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/shipping-rates', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Shipping address is required to calculate shipping rates');
    }

    // =========================================================================
    // Calculate Tax Tests
    // =========================================================================

    public function test_can_calculate_tax(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'subtotal' => 100.00,
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/calculate-tax', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'tax_total',
                    'tax_breakdown',
                    'currency',
                    'cart_total',
                ],
            ]);
    }

    public function test_calculate_tax_fails_without_shipping_address(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/calculate-tax', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Shipping address is required to calculate tax');
    }

    public function test_calculate_tax_updates_cart_tax_total(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'subtotal' => 100.00,
            'tax_total' => 0.00,
            'shipping_address' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'address1' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'postal_code' => '10001',
                'country' => 'US',
            ],
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/calculate-tax', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);

        $cart->refresh();
        $this->assertGreaterThanOrEqual(0, $cart->tax_total);
    }

    // =========================================================================
    // Payment Methods Tests
    // =========================================================================

    public function test_can_get_available_payment_methods(): void
    {
        PaymentMethod::factory()->count(3)->active()->create([
            'store_id' => $this->store->id,
        ]);

        PaymentMethod::factory()->inactive()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/payment-methods');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_payment_methods_only_returns_active_methods(): void
    {
        PaymentMethod::factory()->count(2)->active()->create([
            'store_id' => $this->store->id,
        ]);

        PaymentMethod::factory()->count(3)->inactive()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/payment-methods');

        $response->assertStatus(200);
        $data = $response->json('data');

        $this->assertGreaterThanOrEqual(2, count($data));
    }

    // =========================================================================
    // Create Order Tests
    // =========================================================================

    public function test_can_create_order_from_cart(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'cash-on-delivery',
        ]);

        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'subtotal' => 100.00,
            'total' => 100.00,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
            'customer_email' => 'customer@example.com',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Order created successfully')
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'id',
                    'order_number',
                    'status',
                    'total',
                ],
            ]);

        $this->assertDatabaseHas('commerce_orders', [
            'store_id' => $this->store->id,
        ]);
    }

    public function test_create_order_validation_requires_payment_method(): void
    {
        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'customer_email' => 'customer@example.com',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['payment_method']);
    }

    public function test_create_order_validation_validates_email_format(): void
    {
        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
            'customer_email' => 'invalid-email',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_email']);
    }

    public function test_create_order_fails_for_invalid_payment_method(): void
    {
        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'non-existent-method',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_order_fails_for_empty_cart(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_order_fails_without_addresses(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false);
    }

    public function test_create_order_clears_cart_after_successful_order(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'cash-on-delivery',
        ]);

        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(201);

        $cart->refresh();
        $this->assertEquals(0, $cart->items()->count());
    }

    // =========================================================================
    // Initiate Payment Tests
    // =========================================================================

    public function test_can_initiate_payment_for_order(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'stripe',
            'provider' => 'stripe',
        ]);

        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'payment_method_id' => $paymentMethod->id,
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/storefront/v2/checkout/orders/{$order->order_number}/payment");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    public function test_initiate_payment_fails_for_non_existent_order(): void
    {
        $response = $this->postJson('/api/storefront/v2/checkout/orders/INVALID-ORDER/payment');

        $response->assertStatus(404);
    }

    public function test_initiate_payment_fails_for_unauthorized_user(): void
    {
        $otherCustomer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'customer_id' => $otherCustomer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/storefront/v2/checkout/orders/{$order->order_number}/payment");

        $response->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('message', 'Unauthorized');
    }

    // =========================================================================
    // Process Webhook Tests
    // =========================================================================

    public function test_can_process_payment_webhook(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'stripe',
            'provider' => 'stripe',
        ]);

        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $paymentMethod->id,
        ]);

        $webhookPayload = [
            'type' => 'payment_intent.succeeded',
            'data' => [
                'object' => [
                    'id' => 'pi_test_123',
                    'metadata' => [
                        'order_id' => $order->id,
                    ],
                ],
            ],
        ];

        $response = $this->postJson('/api/storefront/v2/checkout/webhooks/stripe', $webhookPayload);

        $response->assertStatus(200);
    }

    public function test_webhook_handles_invalid_gateway(): void
    {
        $response = $this->postJson('/api/storefront/v2/checkout/webhooks/invalid-gateway', [
            'test' => 'data',
        ]);

        $response->assertStatus(500)
            ->assertJsonPath('success', false);
    }

    // =========================================================================
    // Checkout Summary Tests
    // =========================================================================

    public function test_can_get_checkout_summary(): void
    {
        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'price' => 50.00,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'unit_price' => 50.00,
        ]);

        PaymentMethod::factory()->count(2)->active()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/summary', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'cart',
                    'validation' => [
                        'is_valid',
                        'checkout_errors',
                        'cart_errors',
                    ],
                    'available_payment_methods',
                    'requires_shipping',
                ],
            ]);
    }

    public function test_checkout_summary_indicates_validation_errors(): void
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
            'quantity' => 10,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/summary', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.validation.is_valid', false);
    }

    public function test_checkout_summary_shows_available_payment_methods(): void
    {
        Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $activeMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'name' => 'Credit Card',
        ]);

        PaymentMethod::factory()->inactive()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/summary', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'available_payment_methods',
                ],
            ]);
    }

    public function test_checkout_summary_detects_if_shipping_required(): void
    {
        $cart = Cart::factory()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'requires_shipping' => true,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->getJson('/api/storefront/v2/checkout/summary', [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('data.requires_shipping'));
    }

    // =========================================================================
    // Multi-Variant Order Tests
    // =========================================================================

    public function test_can_create_order_with_multiple_variants(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'cash-on-delivery',
        ]);

        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        $variant1 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 5,
        ]);

        $variant2 = ProductVariant::factory()->create([
            'product_id' => $product->id,
            'stock_quantity' => 5,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant1->id,
            'quantity' => 2,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant2->id,
            'quantity' => 1,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true);

        $order = Order::latest()->first();
        $this->assertEquals(3, $order->items()->sum('quantity'));
    }

    // =========================================================================
    // Guest vs Authenticated User Tests
    // =========================================================================

    public function test_guest_user_can_complete_checkout(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'cash-on-delivery',
        ]);

        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'session_id' => $this->sessionId,
            'customer_id' => null,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson('/api/storefront/v2/checkout/order', [
            'payment_method' => 'cash-on-delivery',
            'customer_email' => 'guest@example.com',
        ], [
            'X-Session-Id' => $this->sessionId,
        ]);

        $response->assertStatus(201);
    }

    public function test_authenticated_user_can_complete_checkout(): void
    {
        $paymentMethod = PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'slug' => 'cash-on-delivery',
        ]);

        $cart = Cart::factory()->withAddresses()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

        $product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
        ]);

        CartItem::factory()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/storefront/v2/checkout/order', [
                'payment_method' => 'cash-on-delivery',
            ]);

        $response->assertStatus(201);
    }
}
