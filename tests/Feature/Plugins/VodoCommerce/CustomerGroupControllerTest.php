<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerGroup;
use VodoCommerce\Models\Store;

class CustomerGroupControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
    }

    // =========================================================================
    // Index Tests
    // =========================================================================

    public function test_can_list_customer_groups(): void
    {
        CustomerGroup::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/customer-groups');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'discount_percentage', 'is_active'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_customer_groups_by_active_status(): void
    {
        CustomerGroup::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        CustomerGroup::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/customer-groups?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_customer_groups_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        CustomerGroup::factory()->create(['store_id' => $this->store->id, 'name' => 'VIP']);
        CustomerGroup::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other VIP']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/customer-groups');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'VIP');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_customer_group(): void
    {
        $groupData = [
            'name' => 'VIP Customers',
            'discount_percentage' => 10.50,
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customer-groups', $groupData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'VIP Customers')
            ->assertJsonPath('data.slug', 'vip-customers')
            ->assertJsonPath('data.discount_percentage', 10.50);

        $this->assertDatabaseHas('commerce_customer_groups', [
            'name' => 'VIP Customers',
            'slug' => 'vip-customers',
            'store_id' => $this->store->id,
        ]);
    }

    public function test_cannot_create_customer_group_without_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customer-groups', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_customer_group_with_duplicate_slug_in_same_store(): void
    {
        CustomerGroup::factory()->create([
            'store_id' => $this->store->id,
            'slug' => 'vip',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customer-groups', [
                'name' => 'VIP',
                'slug' => 'vip',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_validates_discount_percentage_range(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/customer-groups', [
                'name' => 'VIP',
                'discount_percentage' => 150,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['discount_percentage']);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_customer_group(): void
    {
        $group = CustomerGroup::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customer-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $group->id)
            ->assertJsonPath('data.name', $group->name);
    }

    public function test_cannot_show_customer_group_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $group = CustomerGroup::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customer-groups/{$group->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_customer_group(): void
    {
        $group = CustomerGroup::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/customer-groups/{$group->id}", [
                'name' => 'Updated Group',
                'discount_percentage' => 15.0,
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Group')
            ->assertJsonPath('data.discount_percentage', 15.0);

        $this->assertDatabaseHas('commerce_customer_groups', [
            'id' => $group->id,
            'name' => 'Updated Group',
            'is_active' => false,
        ]);
    }

    public function test_cannot_update_customer_group_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $group = CustomerGroup::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/customer-groups/{$group->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_customer_group(): void
    {
        $group = CustomerGroup::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/customer-groups/{$group->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('commerce_customer_groups', [
            'id' => $group->id,
        ]);
    }

    public function test_cannot_delete_customer_group_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $group = CustomerGroup::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/customer-groups/{$group->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('commerce_customer_groups', [
            'id' => $group->id,
            'deleted_at' => null,
        ]);
    }

    // =========================================================================
    // Member Management Tests
    // =========================================================================

    public function test_can_add_customer_to_group(): void
    {
        $group = CustomerGroup::factory()->create(['store_id' => $this->store->id]);
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customer-groups/{$group->id}/customers", [
                'customer_id' => $customer->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('commerce_customer_group_memberships', [
            'customer_group_id' => $group->id,
            'customer_id' => $customer->id,
        ]);
    }

    public function test_can_remove_customer_from_group(): void
    {
        $group = CustomerGroup::factory()->create(['store_id' => $this->store->id]);
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);
        $group->customers()->attach($customer->id);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/customer-groups/{$group->id}/customers/{$customer->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_customer_group_memberships', [
            'customer_group_id' => $group->id,
            'customer_id' => $customer->id,
        ]);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_list_customer_groups(): void
    {
        $response = $this->getJson('/api/admin/v2/customer-groups');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_customer_group(): void
    {
        $response = $this->postJson('/api/admin/v2/customer-groups', ['name' => 'VIP']);

        $response->assertStatus(401);
    }
}
