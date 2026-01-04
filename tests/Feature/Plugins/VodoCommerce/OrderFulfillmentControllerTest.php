<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderFulfillment;
use VodoCommerce\Models\OrderItem;
use VodoCommerce\Models\Store;

class OrderFulfillmentControllerTest extends TestCase
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
        $this->order = Order::factory()->create(['store_id' => $this->store->id]);
    }

    // =========================================================================
    // List Fulfillments Tests
    // =========================================================================

    public function test_can_list_order_fulfillments(): void
    {
        OrderFulfillment::factory()->count(3)->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$this->order->id}/fulfillments");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_list_fulfillments_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$otherOrder->id}/fulfillments");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Create Fulfillment Tests
    // =========================================================================

    public function test_can_create_fulfillment(): void
    {
        $item = OrderItem::factory()->create([
            'order_id' => $this->order->id,
            'quantity' => 5,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/fulfillments", [
                'items' => [
                    ['order_item_id' => $item->id, 'quantity' => 3],
                ],
                'tracking_number' => 'TRK123456',
                'carrier' => 'DHL',
                'notes' => 'Handle with care',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tracking_number', 'TRK123456')
            ->assertJsonPath('data.carrier', 'DHL')
            ->assertJsonPath('data.status', 'pending');

        $this->assertDatabaseHas('commerce_order_fulfillments', [
            'order_id' => $this->order->id,
            'tracking_number' => 'TRK123456',
            'carrier' => 'DHL',
        ]);

        $this->assertDatabaseHas('commerce_order_fulfillment_items', [
            'order_item_id' => $item->id,
            'quantity' => 3,
        ]);
    }

    public function test_create_fulfillment_requires_items(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/fulfillments", [
                'tracking_number' => 'TRK123456',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items']);
    }

    public function test_create_fulfillment_validates_item_quantity(): void
    {
        $item = OrderItem::factory()->create(['order_id' => $this->order->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/fulfillments", [
                'items' => [
                    ['order_item_id' => $item->id, 'quantity' => 0],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.quantity']);
    }

    public function test_cannot_create_fulfillment_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $item = OrderItem::factory()->create(['order_id' => $otherOrder->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$otherOrder->id}/fulfillments", [
                'items' => [
                    ['order_item_id' => $item->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Show Fulfillment Tests
    // =========================================================================

    public function test_can_show_fulfillment(): void
    {
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/fulfillments/{$fulfillment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $fulfillment->id);
    }

    public function test_cannot_show_fulfillment_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/fulfillments/{$fulfillment->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Fulfillment Tests
    // =========================================================================

    public function test_can_update_fulfillment(): void
    {
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'tracking_number' => 'OLD123',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/fulfillments/{$fulfillment->id}", [
                'tracking_number' => 'NEW456',
                'carrier' => 'FedEx',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.tracking_number', 'NEW456')
            ->assertJsonPath('data.carrier', 'FedEx');

        $this->assertDatabaseHas('commerce_order_fulfillments', [
            'id' => $fulfillment->id,
            'tracking_number' => 'NEW456',
            'carrier' => 'FedEx',
        ]);
    }

    public function test_cannot_update_fulfillment_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/fulfillments/{$fulfillment->id}", [
                'tracking_number' => 'NEW456',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Ship Fulfillment Tests
    // =========================================================================

    public function test_can_mark_fulfillment_as_shipped(): void
    {
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/fulfillments/{$fulfillment->id}/ship", [
                'tracking_number' => 'SHIP123',
                'carrier' => 'UPS',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'in_transit');

        $fulfillment->refresh();
        $this->assertEquals('in_transit', $fulfillment->status);
        $this->assertNotNull($fulfillment->shipped_at);
    }

    public function test_cannot_ship_fulfillment_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/fulfillments/{$fulfillment->id}/ship");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Deliver Fulfillment Tests
    // =========================================================================

    public function test_can_mark_fulfillment_as_delivered(): void
    {
        $fulfillment = OrderFulfillment::factory()->shipped()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/fulfillments/{$fulfillment->id}/deliver");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'delivered');

        $fulfillment->refresh();
        $this->assertEquals('delivered', $fulfillment->status);
        $this->assertNotNull($fulfillment->delivered_at);
    }

    // =========================================================================
    // Delete Fulfillment Tests
    // =========================================================================

    public function test_can_delete_fulfillment(): void
    {
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/fulfillments/{$fulfillment->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_order_fulfillments', [
            'id' => $fulfillment->id,
        ]);
    }

    public function test_cannot_delete_fulfillment_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $fulfillment = OrderFulfillment::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/fulfillments/{$fulfillment->id}");

        $response->assertStatus(404);
    }
}
