<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\OrderRefund;
use VodoCommerce\Models\Store;

class OrderRefundControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->order = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'completed',
            'total' => 100.00,
        ]);
    }

    // =========================================================================
    // List Refunds Tests
    // =========================================================================

    public function test_can_list_order_refunds(): void
    {
        OrderRefund::factory()->count(3)->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$this->order->id}/refunds");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_list_refunds_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$otherOrder->id}/refunds");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Create Refund Tests
    // =========================================================================

    public function test_can_create_refund(): void
    {
        $item = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'quantity' => 2,
            'price' => 50.00,
            'total' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/refunds", [
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity' => 1,
                        'amount' => 50.00,
                    ],
                ],
                'reason' => 'Product defective',
                'refund_method' => 'original_payment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.amount', 50.00)
            ->assertJsonPath('data.reason', 'Product defective')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('commerce_order_refunds', [
            'order_id' => $this->order->id,
            'amount' => 50.00,
            'reason' => 'Product defective',
            'status' => 'pending',
        ]);

        $this->assertDatabaseHas('commerce_order_refund_items', [
            'order_item_id' => $item->id,
            'quantity' => 1,
            'amount' => 50.00,
        ]);
    }

    public function test_create_refund_requires_items(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/refunds", [
                'reason' => 'Test',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_create_refund_validates_item_quantity(): void
    {
        $item = OrderItem::factory()->create(['order_id' => $this->order->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/refunds", [
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity' => 0,
                        'amount' => 10.00,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_create_refund_validates_amount(): void
    {
        $item = OrderItem::factory()->create(['order_id' => $this->order->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/refunds", [
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity' => 1,
                        'amount' => -10.00,
                    ],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.amount']);
    }

    public function test_cannot_create_refund_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $item = OrderItem::factory()->create(['order_id' => $otherOrder->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$otherOrder->id}/refunds", [
                'items' => [
                    [
                        'order_item_id' => $item->id,
                        'quantity' => 1,
                        'amount' => 10.00,
                    ],
                ],
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Show Refund Tests
    // =========================================================================

    public function test_can_show_refund(): void
    {
        $refund = OrderRefund::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/refunds/{$refund->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $refund->id);
    }

    public function test_cannot_show_refund_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $refund = OrderRefund::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/refunds/{$refund->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Approve Refund Tests
    // =========================================================================

    public function test_can_approve_refund(): void
    {
        $refund = OrderRefund::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/refunds/{$refund->id}/approve");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'processing');

        $refund->refresh();
        $this->assertEquals('processing', $refund->status);
        $this->assertNotNull($refund->approved_at);
    }

    public function test_cannot_approve_refund_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $refund = OrderRefund::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/refunds/{$refund->id}/approve");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Reject Refund Tests
    // =========================================================================

    public function test_can_reject_refund(): void
    {
        $refund = OrderRefund::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/refunds/{$refund->id}/reject", [
                'reason' => 'Does not meet refund policy',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'rejected');

        $refund->refresh();
        $this->assertEquals('rejected', $refund->status);
        $this->assertNotNull($refund->rejected_at);
        $this->assertEquals('Does not meet refund policy', $refund->rejection_reason);
    }

    public function test_reject_refund_requires_reason(): void
    {
        $refund = OrderRefund::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/refunds/{$refund->id}/reject");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    // =========================================================================
    // Process Refund Tests
    // =========================================================================

    public function test_can_process_refund(): void
    {
        $refund = OrderRefund::factory()->approved()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/refunds/{$refund->id}/process");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $refund->refresh();
        $this->assertEquals('completed', $refund->status);
        $this->assertNotNull($refund->processed_at);
    }

    public function test_cannot_process_refund_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $refund = OrderRefund::factory()->approved()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/refunds/{$refund->id}/process");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Delete Refund Tests
    // =========================================================================

    public function test_can_delete_refund(): void
    {
        $refund = OrderRefund::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/refunds/{$refund->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_order_refunds', [
            'id' => $refund->id,
        ]);
    }

    public function test_cannot_delete_refund_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $refund = OrderRefund::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/refunds/{$refund->id}");

        $response->assertStatus(404);
    }
}
