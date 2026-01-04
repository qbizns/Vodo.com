<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderTimelineEvent;
use VodoCommerce\Models\Store;

class OrderManagementControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        Storage::fake('local');
    }

    // =========================================================================
    // Timeline Tests
    // =========================================================================

    public function test_can_get_order_timeline(): void
    {
        $order = Order::factory()->create(['store_id' => $this->store->id]);

        OrderTimelineEvent::factory()->count(5)->create([
            'order_id' => $order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$order->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data');
    }

    public function test_cannot_get_timeline_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $order = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$order->id}/timeline");

        $response->assertStatus(404);
    }

    public function test_timeline_returns_events_in_descending_order(): void
    {
        $order = Order::factory()->create(['store_id' => $this->store->id]);

        $oldEvent = OrderTimelineEvent::factory()->create([
            'order_id' => $order->id,
            'created_at' => now()->subDays(5),
        ]);

        $newEvent = OrderTimelineEvent::factory()->create([
            'order_id' => $order->id,
            'created_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$order->id}/timeline");

        $response->assertStatus(200)
            ->assertJsonPath('data.0.id', $newEvent->id)
            ->assertJsonPath('data.1.id', $oldEvent->id);
    }

    // =========================================================================
    // Cancel Order Tests
    // =========================================================================

    public function test_can_cancel_order(): void
    {
        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/cancel", [
                'reason' => 'Customer requested cancellation',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('cancelled', $order->status);
        $this->assertEquals('Customer requested cancellation', $order->cancel_reason);
        $this->assertNotNull($order->cancelled_at);
    }

    public function test_cancel_order_requires_reason(): void
    {
        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/cancel");

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['reason']);
    }

    public function test_cannot_cancel_completed_order(): void
    {
        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/cancel", [
                'reason' => 'Test',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_cancel_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $order = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/cancel", [
                'reason' => 'Test',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Status Tests
    // =========================================================================

    public function test_can_update_order_status(): void
    {
        $order = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/status", [
                'status' => 'processing',
                'note' => 'Order is being processed',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $order->refresh();
        $this->assertEquals('processing', $order->status);

        $this->assertDatabaseHas('commerce_order_status_histories', [
            'order_id' => $order->id,
            'from_status' => 'pending',
            'to_status' => 'processing',
        ]);
    }

    public function test_update_status_requires_valid_status(): void
    {
        $order = Order::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/status", [
                'status' => 'invalid_status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_cannot_update_status_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $order = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$order->id}/status", [
                'status' => 'processing',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Export Orders Tests
    // =========================================================================

    public function test_can_export_orders(): void
    {
        Order::factory()->count(5)->create([
            'store_id' => $this->store->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/export', [
                'status' => 'completed',
                'format' => 'csv',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['file_path', 'download_url'],
            ]);
    }

    public function test_export_validates_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/export', [
                'format' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['format']);
    }

    public function test_export_filters_by_date_range(): void
    {
        Order::factory()->create([
            'store_id' => $this->store->id,
            'placed_at' => now()->subDays(10),
        ]);

        Order::factory()->create([
            'store_id' => $this->store->id,
            'placed_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/export', [
                'date_from' => now()->subDays(7)->toDateString(),
                'date_to' => now()->toDateString(),
                'format' => 'csv',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);
    }

    // =========================================================================
    // Bulk Action Tests
    // =========================================================================

    public function test_can_bulk_cancel_orders(): void
    {
        $order1 = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        $order2 = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'cancel',
                'order_ids' => [$order1->id, $order2->id],
                'reason' => 'Bulk cancellation',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', 2)
            ->assertJsonPath('data.failed', 0);

        $order1->refresh();
        $order2->refresh();
        $this->assertEquals('cancelled', $order1->status);
        $this->assertEquals('cancelled', $order2->status);
    }

    public function test_bulk_action_validates_order_ids(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'cancel',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['order_ids']);
    }

    public function test_bulk_action_validates_action_type(): void
    {
        $order = Order::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'invalid_action',
                'order_ids' => [$order->id],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['action']);
    }

    public function test_can_bulk_export_orders(): void
    {
        $order1 = Order::factory()->create(['store_id' => $this->store->id]);
        $order2 = Order::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'export',
                'order_ids' => [$order1->id, $order2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'success',
                'data' => ['file_path', 'download_url', 'exported_count'],
            ])
            ->assertJsonPath('data.exported_count', 2);
    }

    public function test_can_bulk_mark_as_exported(): void
    {
        $order1 = Order::factory()->create([
            'store_id' => $this->store->id,
            'is_exported' => false,
        ]);

        $order2 = Order::factory()->create([
            'store_id' => $this->store->id,
            'is_exported' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'mark_as_exported',
                'order_ids' => [$order1->id, $order2->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', 2);

        $order1->refresh();
        $order2->refresh();
        $this->assertTrue($order1->is_exported);
        $this->assertTrue($order2->is_exported);
        $this->assertNotNull($order1->exported_at);
        $this->assertNotNull($order2->exported_at);
    }

    public function test_bulk_cancel_only_cancels_valid_orders(): void
    {
        $pendingOrder = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'pending',
        ]);

        $completedOrder = Order::factory()->create([
            'store_id' => $this->store->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'cancel',
                'order_ids' => [$pendingOrder->id, $completedOrder->id],
                'reason' => 'Bulk cancellation',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.success', 1)
            ->assertJsonPath('data.failed', 1);

        $pendingOrder->refresh();
        $completedOrder->refresh();
        $this->assertEquals('cancelled', $pendingOrder->status);
        $this->assertEquals('completed', $completedOrder->status);
    }

    public function test_cannot_bulk_action_orders_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $order = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/orders/bulk-action', [
                'action' => 'cancel',
                'order_ids' => [$order->id],
                'reason' => 'Test',
            ]);

        $response->assertStatus(404);
    }
}
