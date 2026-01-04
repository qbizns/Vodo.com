<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use VodoCommerce\Models\DigitalProductCode;
use VodoCommerce\Models\DigitalProductFile;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\Store;

class DigitalProductControllerTest extends TestCase
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
        $this->product = Product::factory()->create([
            'store_id' => $this->store->id,
            'is_downloadable' => true,
        ]);

        Storage::fake('local');
    }

    // =========================================================================
    // File Attachment Tests
    // =========================================================================

    public function test_can_attach_digital_file_to_product(): void
    {
        $file = UploadedFile::fake()->create('ebook.pdf', 5000); // 5MB

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-files", [
                'file' => $file,
                'name' => 'E-Book PDF',
                'download_limit' => 3,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'E-Book PDF')
            ->assertJsonPath('data.download_limit', 3)
            ->assertJsonPath('data.is_active', true);

        $this->assertDatabaseHas('commerce_digital_product_files', [
            'product_id' => $this->product->id,
            'name' => 'E-Book PDF',
            'download_limit' => 3,
        ]);

        Storage::disk('local')->assertExists('digital-products/' . $file->hashName());
    }

    public function test_validates_file_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-files", [
                'name' => 'E-Book',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_validates_file_size_limit(): void
    {
        $file = UploadedFile::fake()->create('huge-file.zip', 600000); // 600MB

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-files", [
                'file' => $file,
                'name' => 'Huge File',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    public function test_validates_name_required(): void
    {
        $file = UploadedFile::fake()->create('ebook.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-files", [
                'file' => $file,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_cannot_attach_file_to_non_downloadable_product(): void
    {
        $nonDigitalProduct = Product::factory()->create([
            'store_id' => $this->store->id,
            'is_downloadable' => false,
        ]);

        $file = UploadedFile::fake()->create('ebook.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$nonDigitalProduct->id}/digital-files", [
                'file' => $file,
                'name' => 'E-Book',
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_attach_file_to_product_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create([
            'store_id' => $otherStore->id,
            'is_downloadable' => true,
        ]);

        $file = UploadedFile::fake()->create('ebook.pdf', 100);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$otherProduct->id}/digital-files", [
                'file' => $file,
                'name' => 'E-Book',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // List Files Tests
    // =========================================================================

    public function test_can_list_digital_files(): void
    {
        DigitalProductFile::factory()->count(3)->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$this->product->id}/digital-files");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'file_size', 'mime_type', 'download_limit', 'is_active'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_list_files_from_product_in_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$otherProduct->id}/digital-files");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Code Generation Tests
    // =========================================================================

    public function test_can_generate_digital_codes(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/generate", [
                'quantity' => 5,
                'expires_in_days' => 30,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', '5 codes generated successfully')
            ->assertJsonCount(5, 'data');

        $this->assertDatabaseCount('commerce_digital_product_codes', 5);
    }

    public function test_validates_quantity_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/generate", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_validates_quantity_minimum(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/generate", [
                'quantity' => 0,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_validates_quantity_maximum(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/generate", [
                'quantity' => 1001,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }

    public function test_generated_codes_are_unique(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/generate", [
                'quantity' => 10,
            ]);

        $response->assertStatus(201);

        $codes = DigitalProductCode::where('product_id', $this->product->id)
            ->pluck('code')
            ->toArray();

        $this->assertCount(10, array_unique($codes));
    }

    public function test_can_generate_codes_with_expiration(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/generate", [
                'quantity' => 3,
                'expires_in_days' => 7,
            ]);

        $response->assertStatus(201);

        $code = DigitalProductCode::where('product_id', $this->product->id)->first();

        $this->assertNotNull($code->expires_at);
        $this->assertEquals(
            now()->addDays(7)->format('Y-m-d'),
            $code->expires_at->format('Y-m-d')
        );
    }

    public function test_cannot_generate_codes_for_product_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$otherProduct->id}/digital-codes/generate", [
                'quantity' => 5,
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Code Import Tests
    // =========================================================================

    public function test_can_import_digital_codes(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/import", [
                'codes' => ['CODE123', 'CODE456', 'CODE789'],
                'expires_in_days' => 60,
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', '3 codes imported successfully')
            ->assertJsonCount(3, 'data');

        $this->assertDatabaseHas('commerce_digital_product_codes', [
            'product_id' => $this->product->id,
            'code' => 'CODE123',
        ]);

        $this->assertDatabaseHas('commerce_digital_product_codes', [
            'product_id' => $this->product->id,
            'code' => 'CODE456',
        ]);
    }

    public function test_validates_codes_array_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/import", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codes']);
    }

    public function test_validates_codes_must_be_array(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/import", [
                'codes' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codes']);
    }

    public function test_validates_codes_must_be_unique_in_request(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/import", [
                'codes' => ['CODE123', 'CODE123'], // Duplicate
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codes.1']);
    }

    public function test_validates_codes_must_not_already_exist(): void
    {
        DigitalProductCode::factory()->create([
            'product_id' => $this->product->id,
            'code' => 'EXISTING',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/digital-codes/import", [
                'codes' => ['EXISTING', 'NEW123'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['codes.0']);
    }

    public function test_cannot_import_codes_for_product_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$otherProduct->id}/digital-codes/import", [
                'codes' => ['CODE123'],
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // List Codes Tests
    // =========================================================================

    public function test_can_list_digital_codes(): void
    {
        DigitalProductCode::factory()->count(10)->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$this->product->id}/digital-codes");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'code', 'assigned_at', 'expires_at'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(10, 'data');
    }

    public function test_can_filter_codes_by_status(): void
    {
        DigitalProductCode::factory()->count(5)->create([
            'product_id' => $this->product->id,
            'assigned_at' => null,
        ]);

        DigitalProductCode::factory()->count(3)->create([
            'product_id' => $this->product->id,
            'assigned_at' => now(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$this->product->id}/digital-codes?status=available");

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_search_codes(): void
    {
        DigitalProductCode::factory()->create([
            'product_id' => $this->product->id,
            'code' => 'ABC123',
        ]);

        DigitalProductCode::factory()->create([
            'product_id' => $this->product->id,
            'code' => 'XYZ789',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$this->product->id}/digital-codes?search=ABC");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.code', 'ABC123');
    }

    public function test_cannot_list_codes_from_product_in_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$otherProduct->id}/digital-codes");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication(): void
    {
        $response = $this->getJson("/api/admin/v2/products/{$this->product->id}/digital-files");

        $response->assertStatus(401);
    }
}
