<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\InventoryReservation;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\InventoryReservationService;

/**
 * CheckoutConcurrencyTest
 *
 * Tests to verify that the checkout flow handles concurrency correctly,
 * preventing overselling and ensuring data integrity under load.
 */
class CheckoutConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = Store::factory()->create();
        $this->product = Product::factory()->create([
            'store_id' => $this->store->id,
            'stock_quantity' => 10,
            'stock_status' => 'in_stock',
        ]);
    }

    // =========================================================================
    // ATOMIC STOCK DECREMENT TESTS
    // =========================================================================

    public function test_stock_decrement_prevents_negative_stock(): void
    {
        // Start with 10 items
        $this->assertEquals(10, $this->product->stock_quantity);

        // Decrement 8 - should succeed
        $result = $this->product->decrementStock(8);
        $this->assertTrue($result);
        $this->product->refresh();
        $this->assertEquals(2, $this->product->stock_quantity);

        // Try to decrement 5 more - should fail (only 2 available)
        $result = $this->product->decrementStock(5);
        $this->assertFalse($result);

        // Stock should still be 2
        $this->product->refresh();
        $this->assertEquals(2, $this->product->stock_quantity);
    }

    public function test_concurrent_decrements_cannot_oversell(): void
    {
        // Set stock to exactly 1
        $this->product->update(['stock_quantity' => 1]);

        // Simulate two concurrent decrement attempts
        $results = [];

        // First decrement should succeed
        $results[] = $this->product->decrementStock(1);

        // Second decrement should fail (stock is now 0)
        $freshProduct = Product::find($this->product->id);
        $results[] = $freshProduct->decrementStock(1);

        // Only one should have succeeded
        $this->assertEquals(1, array_sum($results)); // true = 1, false = 0

        // Stock should be 0, not negative
        $this->product->refresh();
        $this->assertGreaterThanOrEqual(0, $this->product->stock_quantity);
    }

    public function test_stock_decrement_updates_status_when_empty(): void
    {
        $this->product->update(['stock_quantity' => 1, 'stock_status' => 'in_stock']);

        $this->product->decrementStock(1);
        $this->product->refresh();

        $this->assertEquals(0, $this->product->stock_quantity);
        $this->assertEquals('out_of_stock', $this->product->stock_status);
    }

    // =========================================================================
    // VARIANT STOCK TESTS
    // =========================================================================

    public function test_variant_stock_decrement_is_atomic(): void
    {
        $variant = ProductVariant::factory()->create([
            'product_id' => $this->product->id,
            'stock_quantity' => 5,
        ]);

        // Decrement 3 - should succeed
        $this->assertTrue($variant->decrementStock(3));
        $variant->refresh();
        $this->assertEquals(2, $variant->stock_quantity);

        // Decrement 3 more - should fail
        $this->assertFalse($variant->decrementStock(3));
        $variant->refresh();
        $this->assertEquals(2, $variant->stock_quantity);
    }

    // =========================================================================
    // ORDER NUMBER UNIQUENESS TESTS
    // =========================================================================

    public function test_order_numbers_are_unique(): void
    {
        $orderNumbers = [];

        for ($i = 0; $i < 100; $i++) {
            $orderNumber = Order::generateOrderNumber($this->store->id);
            $this->assertNotContains($orderNumber, $orderNumbers, "Duplicate order number: {$orderNumber}");
            $orderNumbers[] = $orderNumber;
        }
    }

    public function test_order_number_format_is_correct(): void
    {
        $orderNumber = Order::generateOrderNumber($this->store->id);

        // Format: ORD-YYMMDD-XXXXXXXX
        $this->assertMatchesRegularExpression(
            '/^ORD-\d{6}-[A-Z0-9]{8}$/',
            $orderNumber
        );
    }

    public function test_order_number_handles_collision(): void
    {
        // Create an order with a known number
        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'order_number' => 'ORD-' . now()->format('ymd') . '-TESTTEST',
        ]);

        // Generate new order numbers - none should match the existing one
        for ($i = 0; $i < 50; $i++) {
            $newNumber = Order::generateOrderNumber($this->store->id);
            $this->assertNotEquals($order->order_number, $newNumber);
        }
    }

    // =========================================================================
    // INVENTORY RESERVATION TESTS
    // =========================================================================

    public function test_reservation_holds_stock(): void
    {
        $cart = Cart::factory()->create(['store_id' => $this->store->id]);
        $service = new InventoryReservationService($this->store);

        // Product has 10 stock
        $available = $service->getAvailableStock($this->product->id);
        $this->assertEquals(10, $available);

        // Reserve 3
        $reservation = $service->reserve($cart, $this->product, 3);
        $this->assertNotNull($reservation);
        $this->assertEquals(3, $reservation->quantity);

        // Available should now be 7
        $available = $service->getAvailableStock($this->product->id);
        $this->assertEquals(7, $available);
    }

    public function test_reservation_prevents_overselling(): void
    {
        $cart1 = Cart::factory()->create(['store_id' => $this->store->id]);
        $cart2 = Cart::factory()->create(['store_id' => $this->store->id]);
        $service = new InventoryReservationService($this->store);

        // Reserve 8 for cart 1
        $r1 = $service->reserve($cart1, $this->product, 8);
        $this->assertNotNull($r1);

        // Try to reserve 5 for cart 2 - should fail (only 2 available)
        $r2 = $service->reserve($cart2, $this->product, 5);
        $this->assertNull($r2);

        // Reserve 2 for cart 2 - should succeed
        $r3 = $service->reserve($cart2, $this->product, 2);
        $this->assertNotNull($r3);
    }

    public function test_reservation_expires(): void
    {
        $cart = Cart::factory()->create(['store_id' => $this->store->id]);
        $service = new InventoryReservationService($this->store);

        // Create reservation that's already expired
        $reservation = InventoryReservation::create([
            'store_id' => $this->store->id,
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 5,
            'expires_at' => now()->subMinute(), // Already expired
        ]);

        $this->assertTrue($reservation->isExpired());
        $this->assertFalse($reservation->isActive());

        // Available stock should NOT include expired reservation
        $available = $service->getAvailableStock($this->product->id);
        $this->assertEquals(10, $available); // Full stock available
    }

    public function test_reservation_cleanup_removes_expired(): void
    {
        $cart = Cart::factory()->create(['store_id' => $this->store->id]);

        // Create mix of active and expired reservations
        InventoryReservation::create([
            'store_id' => $this->store->id,
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 2,
            'expires_at' => now()->subMinutes(5), // Expired
        ]);

        InventoryReservation::create([
            'store_id' => $this->store->id,
            'cart_id' => $cart->id,
            'product_id' => $this->product->id,
            'quantity' => 3,
            'expires_at' => now()->addMinutes(10), // Active
        ]);

        $this->assertEquals(2, InventoryReservation::count());

        // Cleanup expired
        $cleaned = InventoryReservation::cleanupExpired();

        $this->assertEquals(1, $cleaned);
        $this->assertEquals(1, InventoryReservation::count());
    }

    public function test_own_reservation_not_counted_against_cart(): void
    {
        $cart = Cart::factory()->create(['store_id' => $this->store->id]);
        $service = new InventoryReservationService($this->store);

        // Reserve 5 for this cart
        $service->reserve($cart, $this->product, 5);

        // Available for OTHER carts should be 5
        $availableForOthers = $service->getAvailableStock($this->product->id, null, null);
        $this->assertEquals(5, $availableForOthers);

        // Available for THIS cart should still be 10 (own reservation excluded)
        $availableForSelf = $service->getAvailableStock($this->product->id, null, $cart->id);
        $this->assertEquals(10, $availableForSelf);
    }

    public function test_reservation_released_on_item_removal(): void
    {
        $cart = Cart::factory()->create(['store_id' => $this->store->id]);
        $service = new InventoryReservationService($this->store);

        // Reserve
        $service->reserve($cart, $this->product, 5);
        $this->assertEquals(5, $service->getAvailableStock($this->product->id));

        // Release
        $service->release($cart, $this->product->id);
        $this->assertEquals(10, $service->getAvailableStock($this->product->id));
    }

    public function test_reservation_update_quantity(): void
    {
        $cart = Cart::factory()->create(['store_id' => $this->store->id]);
        $service = new InventoryReservationService($this->store);

        // Reserve 3
        $service->reserve($cart, $this->product, 3);
        $this->assertEquals(7, $service->getAvailableStock($this->product->id));

        // Update to 5
        $result = $service->updateQuantity($cart, $this->product->id, null, 5);
        $this->assertTrue($result);
        $this->assertEquals(5, $service->getAvailableStock($this->product->id));

        // Try to update to 15 - should fail (only 10 total stock)
        $result = $service->updateQuantity($cart, $this->product->id, null, 15);
        $this->assertFalse($result);

        // Should still have 5 reserved
        $this->assertEquals(5, $service->getAvailableStock($this->product->id));
    }

    // =========================================================================
    // CHECKOUT FLOW INTEGRATION TESTS
    // =========================================================================

    public function test_checkout_fails_gracefully_on_insufficient_stock(): void
    {
        $this->product->update(['stock_quantity' => 1]);

        // First checkout should succeed
        $result1 = $this->product->decrementStock(1);
        $this->assertTrue($result1);

        // Second checkout should fail gracefully
        $freshProduct = Product::find($this->product->id);
        $result2 = $freshProduct->decrementStock(1);
        $this->assertFalse($result2);
    }

    public function test_transaction_rollback_restores_stock(): void
    {
        $originalStock = $this->product->stock_quantity;

        try {
            DB::transaction(function () {
                // Decrement stock
                $this->product->decrementStock(5);
                $this->product->refresh();
                $this->assertEquals(5, $this->product->stock_quantity);

                // Throw exception to trigger rollback
                throw new \Exception('Simulated payment failure');
            });
        } catch (\Exception $e) {
            // Expected exception
        }

        // Stock should be restored after rollback
        $this->product->refresh();
        $this->assertEquals($originalStock, $this->product->stock_quantity);
    }
}
