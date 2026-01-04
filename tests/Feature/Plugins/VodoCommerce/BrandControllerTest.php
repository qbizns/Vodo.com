<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Brand;
use VodoCommerce\Models\Store;

class BrandControllerTest extends TestCase
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

    public function test_can_list_brands(): void
    {
        Brand::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/brands');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'logo', 'is_active', 'created_at'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_brands_by_active_status(): void
    {
        Brand::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        Brand::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/brands?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_search_brands_by_name(): void
    {
        Brand::factory()->create(['store_id' => $this->store->id, 'name' => 'Nike']);
        Brand::factory()->create(['store_id' => $this->store->id, 'name' => 'Adidas']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/brands?search=Nike');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Nike');
    }

    public function test_brands_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        Brand::factory()->create(['store_id' => $this->store->id, 'name' => 'Store Brand']);
        Brand::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Store Brand']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/brands');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Store Brand');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_brand(): void
    {
        $brandData = [
            'name' => 'Nike',
            'description' => 'Just Do It',
            'website' => 'https://nike.com',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/brands', $brandData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Nike')
            ->assertJsonPath('data.slug', 'nike')
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('commerce_brands', [
            'name' => 'Nike',
            'slug' => 'nike',
            'store_id' => $this->store->id,
        ]);
    }

    public function test_can_create_brand_with_custom_slug(): void
    {
        $brandData = [
            'name' => 'Nike',
            'slug' => 'nike-shoes',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/brands', $brandData);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'nike-shoes');

        $this->assertDatabaseHas('commerce_brands', [
            'slug' => 'nike-shoes',
        ]);
    }

    public function test_cannot_create_brand_without_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/brands', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_brand_with_duplicate_slug_in_same_store(): void
    {
        Brand::factory()->create([
            'store_id' => $this->store->id,
            'slug' => 'nike',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/brands', [
                'name' => 'Nike',
                'slug' => 'nike',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_can_create_brand_with_same_slug_in_different_store(): void
    {
        $otherStore = Store::factory()->create();
        Brand::factory()->create([
            'store_id' => $otherStore->id,
            'slug' => 'nike',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/brands', [
                'name' => 'Nike',
                'slug' => 'nike',
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_brands', [
            'store_id' => $this->store->id,
            'slug' => 'nike',
        ]);
    }

    public function test_validates_website_url_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/brands', [
                'name' => 'Nike',
                'website' => 'invalid-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['website']);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_brand(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/brands/{$brand->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $brand->id)
            ->assertJsonPath('data.name', $brand->name);
    }

    public function test_cannot_show_brand_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/brands/{$brand->id}");

        $response->assertStatus(404);
    }

    public function test_returns_404_for_nonexistent_brand(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/brands/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_brand(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/brands/{$brand->id}", [
                'name' => 'Updated Name',
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Name')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('commerce_brands', [
            'id' => $brand->id,
            'name' => 'Updated Name',
            'is_active' => false,
        ]);
    }

    public function test_cannot_update_brand_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/brands/{$brand->id}", [
                'name' => 'Updated Name',
            ]);

        $response->assertStatus(404);

        $this->assertDatabaseMissing('commerce_brands', [
            'id' => $brand->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_validates_name_on_update(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/brands/{$brand->id}", [
                'name' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_brand(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/brands/{$brand->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('commerce_brands', [
            'id' => $brand->id,
        ]);
    }

    public function test_cannot_delete_brand_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $brand = Brand::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/brands/{$brand->id}");

        $response->assertStatus(404);

        $this->assertDatabaseHas('commerce_brands', [
            'id' => $brand->id,
            'deleted_at' => null,
        ]);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_list_brands(): void
    {
        $response = $this->getJson('/api/admin/v2/brands');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_brand(): void
    {
        $response = $this->postJson('/api/admin/v2/brands', ['name' => 'Nike']);

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_update_brand(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $response = $this->putJson("/api/admin/v2/brands/{$brand->id}", ['name' => 'Updated']);

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_delete_brand(): void
    {
        $brand = Brand::factory()->create(['store_id' => $this->store->id]);

        $response = $this->deleteJson("/api/admin/v2/brands/{$brand->id}");

        $response->assertStatus(401);
    }
}
