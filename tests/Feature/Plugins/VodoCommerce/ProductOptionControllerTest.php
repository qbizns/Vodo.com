<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductOption;
use VodoCommerce\Models\ProductOptionTemplate;
use VodoCommerce\Models\Store;

class ProductOptionControllerTest extends TestCase
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
    }

    // =========================================================================
    // Product Options - Index Tests
    // =========================================================================

    public function test_can_list_product_options(): void
    {
        ProductOption::factory()->count(3)->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$this->product->id}/options");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'type', 'is_required', 'position', 'values'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_cannot_list_options_for_product_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherProduct = Product::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/products/{$otherProduct->id}/options");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Product Options - Store Tests
    // =========================================================================

    public function test_can_create_product_option(): void
    {
        $optionData = [
            'name' => 'Size',
            'type' => 'select',
            'is_required' => true,
            'values' => [
                ['label' => 'Small', 'price_adjustment' => 0],
                ['label' => 'Medium', 'price_adjustment' => 5],
                ['label' => 'Large', 'price_adjustment' => 10],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", $optionData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Size')
            ->assertJsonPath('data.type', 'select')
            ->assertJsonPath('data.is_required', true)
            ->assertJsonCount(3, 'data.values');

        $this->assertDatabaseHas('commerce_product_options', [
            'product_id' => $this->product->id,
            'name' => 'Size',
            'type' => 'select',
        ]);

        $this->assertDatabaseHas('commerce_product_option_values', [
            'label' => 'Small',
            'price_adjustment' => 0,
        ]);
    }

    public function test_validates_option_name_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", [
                'type' => 'select',
                'values' => [['label' => 'Value']],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_validates_option_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", [
                'name' => 'Size',
                'type' => 'invalid-type',
                'values' => [['label' => 'Value']],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_validates_option_values_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", [
                'name' => 'Size',
                'type' => 'select',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['values']);
    }

    public function test_validates_option_values_array(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", [
                'name' => 'Size',
                'type' => 'select',
                'values' => 'not-an-array',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['values']);
    }

    public function test_validates_option_value_label_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", [
                'name' => 'Size',
                'type' => 'select',
                'values' => [
                    ['price_adjustment' => 10],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['values.0.label']);
    }

    public function test_validates_price_adjustment_numeric(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/products/{$this->product->id}/options", [
                'name' => 'Size',
                'type' => 'select',
                'values' => [
                    ['label' => 'Small', 'price_adjustment' => 'not-numeric'],
                ],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['values.0.price_adjustment']);
    }

    // =========================================================================
    // Product Options - Update Tests
    // =========================================================================

    public function test_can_update_product_option(): void
    {
        $option = ProductOption::factory()->create([
            'product_id' => $this->product->id,
            'name' => 'Size',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/products/{$this->product->id}/options/{$option->id}", [
                'name' => 'Updated Size',
                'is_required' => false,
                'values' => [
                    ['label' => 'XS', 'price_adjustment' => 0],
                    ['label' => 'XL', 'price_adjustment' => 15],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Size')
            ->assertJsonPath('data.is_required', false);

        $this->assertDatabaseHas('commerce_product_options', [
            'id' => $option->id,
            'name' => 'Updated Size',
            'is_required' => false,
        ]);
    }

    public function test_cannot_update_option_from_different_product(): void
    {
        $otherProduct = Product::factory()->create(['store_id' => $this->store->id]);
        $option = ProductOption::factory()->create(['product_id' => $otherProduct->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/products/{$this->product->id}/options/{$option->id}", [
                'name' => 'Updated',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Product Options - Delete Tests
    // =========================================================================

    public function test_can_delete_product_option(): void
    {
        $option = ProductOption::factory()->create([
            'product_id' => $this->product->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$this->product->id}/options/{$option->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('commerce_product_options', [
            'id' => $option->id,
        ]);
    }

    public function test_cannot_delete_option_from_different_product(): void
    {
        $otherProduct = Product::factory()->create(['store_id' => $this->store->id]);
        $option = ProductOption::factory()->create(['product_id' => $otherProduct->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/products/{$this->product->id}/options/{$option->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Templates - Index Tests
    // =========================================================================

    public function test_can_list_option_templates(): void
    {
        ProductOptionTemplate::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/option-templates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'name', 'type', 'is_required', 'position', 'values'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_templates_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        ProductOptionTemplate::factory()->create(['store_id' => $this->store->id, 'name' => 'My Template']);
        ProductOptionTemplate::factory()->create(['store_id' => $otherStore->id, 'name' => 'Other Template']);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/option-templates');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'My Template');
    }

    // =========================================================================
    // Templates - Store Tests
    // =========================================================================

    public function test_can_create_option_template(): void
    {
        $templateData = [
            'name' => 'Standard Size',
            'type' => 'select',
            'is_required' => true,
            'values' => [
                ['label' => 'Small', 'price_adjustment' => 0],
                ['label' => 'Medium', 'price_adjustment' => 5],
                ['label' => 'Large', 'price_adjustment' => 10],
            ],
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/option-templates', $templateData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Standard Size')
            ->assertJsonPath('data.type', 'select')
            ->assertJsonCount(3, 'data.values');

        $this->assertDatabaseHas('commerce_product_option_templates', [
            'store_id' => $this->store->id,
            'name' => 'Standard Size',
            'type' => 'select',
        ]);
    }

    public function test_validates_template_name_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/option-templates', [
                'type' => 'select',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    // =========================================================================
    // Templates - Update Tests
    // =========================================================================

    public function test_can_update_option_template(): void
    {
        $template = ProductOptionTemplate::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Size',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/option-templates/{$template->id}", [
                'name' => 'Updated Size Template',
                'is_required' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'Updated Size Template');

        $this->assertDatabaseHas('commerce_product_option_templates', [
            'id' => $template->id,
            'name' => 'Updated Size Template',
        ]);
    }

    public function test_cannot_update_template_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $template = ProductOptionTemplate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/option-templates/{$template->id}", [
                'name' => 'Updated',
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Templates - Delete Tests
    // =========================================================================

    public function test_can_delete_option_template(): void
    {
        $template = ProductOptionTemplate::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/option-templates/{$template->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('commerce_product_option_templates', [
            'id' => $template->id,
        ]);
    }

    public function test_cannot_delete_template_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $template = ProductOptionTemplate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/option-templates/{$template->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication(): void
    {
        $response = $this->getJson("/api/admin/v2/products/{$this->product->id}/options");

        $response->assertStatus(401);
    }
}
