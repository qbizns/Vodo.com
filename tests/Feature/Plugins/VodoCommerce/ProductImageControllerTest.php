<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductImage;
use VodoCommerce\Models\Store;

class ProductImageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->product = Product::factory()->create(['store_id' => $this->store->id]);

        Storage::fake('public');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_attach_image_to_product(): void
    {
        $file = UploadedFile::fake()->image('product.jpg', 800, 600);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'image' => $file,
                'alt_text' => 'Product image',
                'is_primary' => true,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.alt_text', 'Product image')
            ->assertJsonPath('data.is_primary', true);

        $this->assertDatabaseHas('commerce_product_images', [
            'product_id' => $this->product->id,
            'alt_text' => 'Product image',
            'is_primary' => true,
        ]);

        Storage::disk('public')->assertExists('product-images/' . $file->hashName());
    }

    public function test_can_attach_image_with_url(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'url' => 'https://example.com/image.jpg',
                'alt_text' => 'External image',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.url', 'https://example.com/image.jpg');

        $this->assertDatabaseHas('commerce_product_images', [
            'product_id' => $this->product->id,
            'url' => 'https://example.com/image.jpg',
        ]);
    }

    public function test_validates_image_file_or_url_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'alt_text' => 'Product image',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_validates_image_file_type(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_validates_image_file_size(): void
    {
        $file = UploadedFile::fake()->create('huge-image.jpg', 11000); // 11MB

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'image' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['image']);
    }

    public function test_validates_url_format(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'url' => 'not-a-valid-url',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    public function test_cannot_attach_image_to_product_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$otherProduct->id}/images", [
                'image' => $file,
            ]);

        $response->assertStatus(404);
    }

    public function test_sets_first_image_as_primary_automatically(): void
    {
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'image' => $file,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.is_primary', true);
    }

    public function test_only_one_image_can_be_primary(): void
    {
        $image1 = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => true,
        ]);

        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/images", [
                'image' => $file,
                'is_primary' => true,
            ]);

        $response->assertStatus(201);

        $image1->refresh();
        $this->assertFalse($image1->is_primary);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_product_image(): void
    {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'url' => 'product-images/test.jpg',
        ]);

        Storage::disk('public')->put('product-images/test.jpg', 'fake-content');

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$this->product->id}/images/{$image->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertDatabaseMissing('commerce_product_images', [
            'id' => $image->id,
        ]);

        Storage::disk('public')->assertMissing('product-images/test.jpg');
    }

    public function test_cannot_delete_image_from_different_product(): void
    {
        $otherProduct = Product::factory()->create(['store_id' => $this->store->id]);
        $image = ProductImage::factory()->create(['product_id' => $otherProduct->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$this->product->id}/images/{$image->id}");

        $response->assertStatus(404);
    }

    public function test_cannot_delete_image_from_product_in_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);
        $image = ProductImage::factory()->create(['product_id' => $otherProduct->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$otherProduct->id}/images/{$image->id}");

        $response->assertStatus(404);
    }

    public function test_does_not_delete_external_images(): void
    {
        $image = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'url' => 'https://example.com/external-image.jpg',
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$this->product->id}/images/{$image->id}");

        $response->assertStatus(200);

        $this->assertDatabaseMissing('commerce_product_images', [
            'id' => $image->id,
        ]);
    }

    public function test_assigns_new_primary_when_deleting_primary_image(): void
    {
        $primaryImage = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => true,
            'position' => 0,
        ]);

        $secondaryImage = ProductImage::factory()->create([
            'product_id' => $this->product->id,
            'is_primary' => false,
            'position' => 1,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$this->product->id}/images/{$primaryImage->id}");

        $response->assertStatus(200);

        $secondaryImage->refresh();
        $this->assertTrue($secondaryImage->is_primary);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_attach_image(): void
    {
        $file = UploadedFile::fake()->image('product.jpg');

        $response = $this->postJson("/api/admin/v2/products/{$this->product->id}/images", [
            'image' => $file,
        ]);

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_delete_image(): void
    {
        $image = ProductImage::factory()->create(['product_id' => $this->product->id]);

        $response = $this->deleteJson("/api/admin/v2/products/{$this->product->id}/images/{$image->id}");

        $response->assertStatus(401);
    }
}
