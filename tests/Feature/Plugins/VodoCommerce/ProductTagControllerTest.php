<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductTag;
use VodoCommerce\Models\Store;

class ProductTagControllerTest extends TestCase
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

    public function test_can_list_tags(): void
    {
        ProductTag::factory()->count(5)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tags');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'slug', 'color', 'created_at'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_search_tags_by_name(): void
    {
        ProductTag::factory()->create(['store_id' => $this->store->id, 'name' => 'Summer']);
        ProductTag::factory()->create(['store_id' => $this->store->id, 'name' => 'Winter']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tags?search=Summer');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'Summer');
    }

    public function test_tags_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        ProductTag::factory()->create(['store_id' => $this->store->id, 'name' => 'My Tag']);
        ProductTag::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Tag']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/tags');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'My Tag');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_tag(): void
    {
        $tagData = [
            'name' => 'Summer Collection',
            'description' => 'Products for summer',
            'color' => '#FF5733',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tags', $tagData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Summer Collection')
            ->assertJsonPath('data.slug', 'summer-collection')
            ->assertJsonPath('data.color', '#FF5733');

        $this->assertDatabaseHas('commerce_product_tags', [
            'name' => 'Summer Collection',
            'slug' => 'summer-collection',
            'store_id' => $this->store->id,
        ]);
    }

    public function test_can_create_tag_with_custom_slug(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tags', [
                'name' => 'Summer',
                'slug' => 'summer-2024',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.slug', 'summer-2024');
    }

    public function test_cannot_create_tag_without_name(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tags', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_create_duplicate_tag_slug_in_same_store(): void
    {
        ProductTag::factory()->create([
            'store_id' => $this->store->id,
            'slug' => 'summer',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tags', [
                'name' => 'Summer',
                'slug' => 'summer',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_validates_color_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/tags', [
                'name' => 'Summer',
                'color' => 'invalid-color',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['color']);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_tag(): void
    {
        $tag = ProductTag::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $tag->id)
            ->assertJsonPath('data.name', $tag->name);
    }

    public function test_cannot_show_tag_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $tag = ProductTag::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/tags/{$tag->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_tag(): void
    {
        $tag = ProductTag::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/tags/{$tag->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('commerce_product_tags', [
            'id' => $tag->id,
        ]);
    }

    public function test_cannot_delete_tag_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $tag = ProductTag::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/tags/{$tag->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Attach to Product Tests
    // =========================================================================

    public function test_can_attach_tag_to_product(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $tag = ProductTag::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => [$tag->id],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('commerce_product_tag_pivot', [
            'product_id' => $product->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_can_attach_multiple_tags_to_product(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $tags = ProductTag::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => $tags->pluck('id')->toArray(),
            ]);

        $response->assertStatus(200);

        foreach ($tags as $tag) {
            $this->assertDatabaseHas('commerce_product_tag_pivot', [
                'product_id' => $product->id,
                'tag_id' => $tag->id,
            ]);
        }
    }

    public function test_can_sync_tags_on_product(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $oldTag = ProductTag::factory()->create(['store_id' => $this->store->id]);
        $newTag = ProductTag::factory()->create(['store_id' => $this->store->id]);

        $product->tags()->attach($oldTag->id);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => [$newTag->id],
            ]);

        $response->assertStatus(200);

        $this->assertDatabaseMissing('commerce_product_tag_pivot', [
            'product_id' => $product->id,
            'tag_id' => $oldTag->id,
        ]);

        $this->assertDatabaseHas('commerce_product_tag_pivot', [
            'product_id' => $product->id,
            'tag_id' => $newTag->id,
        ]);
    }

    public function test_cannot_attach_tag_from_different_store(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);
        $otherStore = Store::factory()->create();
        $tag = ProductTag::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => [$tag->id],
            ]);

        $response->assertStatus(422);

        $this->assertDatabaseMissing('commerce_product_tag_pivot', [
            'product_id' => $product->id,
            'tag_id' => $tag->id,
        ]);
    }

    public function test_cannot_attach_tags_to_product_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $product = Product::factory()->create(['store_id' => $otherStore->id]);
        $tag = ProductTag::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => [$tag->id],
            ]);

        $response->assertStatus(404);
    }

    public function test_validates_tag_ids_array(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tag_ids']);
    }

    public function test_validates_tag_ids_exists(): void
    {
        $product = Product::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$product->id}/tags", [
                'tag_ids' => [99999],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['tag_ids.0']);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication(): void
    {
        $response = $this->getJson('/api/admin/v2/tags');

        $response->assertStatus(401);
    }
}
