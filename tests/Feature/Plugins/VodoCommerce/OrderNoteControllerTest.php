<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\OrderNote;
use VodoCommerce\Models\Store;

class OrderNoteControllerTest extends TestCase
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
    // List Notes Tests
    // =========================================================================

    public function test_can_list_order_notes(): void
    {
        OrderNote::factory()->count(3)->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$this->order->id}/notes");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_list_notes_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$otherOrder->id}/notes");

        $response->assertStatus(404);
    }

    public function test_list_notes_returns_paginated_results(): void
    {
        OrderNote::factory()->count(25)->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/orders/{$this->order->id}/notes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
                'pagination' => ['total', 'per_page', 'current_page'],
            ]);
    }

    // =========================================================================
    // Create Note Tests
    // =========================================================================

    public function test_can_create_order_note(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/notes", [
                'content' => 'Important note about this order',
                'is_customer_visible' => false,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Important note about this order')
            ->assertJsonPath('data.is_customer_visible', false)
            ->assertJsonPath('data.author_type', 'admin');

        $this->assertDatabaseHas('commerce_order_notes', [
            'order_id' => $this->order->id,
            'content' => 'Important note about this order',
            'is_customer_visible' => false,
        ]);
    }

    public function test_can_create_customer_visible_note(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/notes", [
                'content' => 'Your order is being processed',
                'is_customer_visible' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_customer_visible', true);

        $this->assertDatabaseHas('commerce_order_notes', [
            'order_id' => $this->order->id,
            'is_customer_visible' => true,
        ]);
    }

    public function test_create_note_requires_content(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/notes", [
                'is_customer_visible' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_create_note_validates_content_max_length(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$this->order->id}/notes", [
                'content' => str_repeat('a', 5001),
                'is_customer_visible' => false,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['content']);
    }

    public function test_cannot_create_note_for_order_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/orders/{$otherOrder->id}/notes", [
                'content' => 'Test note',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Note Tests
    // =========================================================================

    public function test_can_update_order_note(): void
    {
        $note = OrderNote::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'content' => 'Original content',
            'is_customer_visible' => false,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/notes/{$note->id}", [
                'content' => 'Updated content',
                'is_customer_visible' => true,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.content', 'Updated content')
            ->assertJsonPath('data.is_customer_visible', true);

        $this->assertDatabaseHas('commerce_order_notes', [
            'id' => $note->id,
            'content' => 'Updated content',
            'is_customer_visible' => true,
        ]);
    }

    public function test_cannot_update_note_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $note = OrderNote::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/notes/{$note->id}", [
                'content' => 'Updated content',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Delete Note Tests
    // =========================================================================

    public function test_can_delete_order_note(): void
    {
        $note = OrderNote::factory()->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_order_notes', [
            'id' => $note->id,
        ]);
    }

    public function test_cannot_delete_note_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherOrder = Order::factory()->create(['store_id' => $otherStore->id]);
        $note = OrderNote::factory()->create([
            'store_id' => $otherStore->id,
            'order_id' => $otherOrder->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/notes/{$note->id}");

        $response->assertStatus(404);
    }
}
