<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Affiliate;
use VodoCommerce\Models\AffiliateLink;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;

class AffiliateControllerTest extends TestCase
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

    public function test_can_list_affiliates(): void
    {
        Affiliate::factory()->count(3)->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/affiliates');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'success',
                'data' => [
                    '*' => ['id', 'code', 'commission_rate', 'commission_type', 'total_earnings', 'is_active'],
                ],
                'pagination',
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_affiliates_by_active_status(): void
    {
        Affiliate::factory()->count(2)->create(['store_id' => $this->store->id, 'is_active' => true]);
        Affiliate::factory()->count(1)->create(['store_id' => $this->store->id, 'is_active' => false]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/affiliates?is_active=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_affiliates_are_scoped_to_store(): void
    {
        $otherStore = Store::factory()->create();
        Affiliate::factory()->create(['store_id' => $this->store->id]);
        Affiliate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/affiliates');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // Store Tests
    // =========================================================================

    public function test_can_create_affiliate(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $affiliateData = [
            'customer_id' => $customer->id,
            'code' => 'AFFILIATE123',
            'commission_rate' => 10.0,
            'commission_type' => 'percentage',
        ];

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/affiliates', $affiliateData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.code', 'AFFILIATE123')
            ->assertJsonPath('data.commission_rate', 10.0)
            ->assertJsonPath('data.commission_type', 'percentage');

        $this->assertDatabaseHas('commerce_affiliates', [
            'customer_id' => $customer->id,
            'code' => 'AFFILIATE123',
            'store_id' => $this->store->id,
        ]);
    }

    public function test_generates_unique_code_if_not_provided(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/affiliates', [
                'customer_id' => $customer->id,
                'commission_rate' => 10.0,
                'commission_type' => 'percentage',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => ['id', 'code', 'commission_rate'],
            ]);

        $this->assertNotEmpty($response->json('data.code'));
    }

    public function test_cannot_create_affiliate_without_required_fields(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/affiliates', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['customer_id', 'commission_rate', 'commission_type']);
    }

    public function test_cannot_create_affiliate_with_duplicate_code(): void
    {
        Affiliate::factory()->create([
            'store_id' => $this->store->id,
            'code' => 'UNIQUE123',
        ]);

        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/affiliates', [
                'customer_id' => $customer->id,
                'code' => 'UNIQUE123',
                'commission_rate' => 10.0,
                'commission_type' => 'percentage',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['code']);
    }

    public function test_validates_commission_type(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/api/admin/v2/affiliates', [
                'customer_id' => $customer->id,
                'commission_rate' => 10.0,
                'commission_type' => 'invalid',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['commission_type']);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_affiliate(): void
    {
        $affiliate = Affiliate::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/affiliates/{$affiliate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $affiliate->id)
            ->assertJsonPath('data.code', $affiliate->code);
    }

    public function test_cannot_show_affiliate_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $affiliate = Affiliate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/affiliates/{$affiliate->id}");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_affiliate(): void
    {
        $affiliate = Affiliate::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/affiliates/{$affiliate->id}", [
                'commission_rate' => 15.0,
                'is_active' => false,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.commission_rate', 15.0)
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('commerce_affiliates', [
            'id' => $affiliate->id,
            'commission_rate' => 15.0,
            'is_active' => false,
        ]);
    }

    public function test_cannot_update_affiliate_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $affiliate = Affiliate::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/affiliates/{$affiliate->id}", [
                'commission_rate' => 20.0,
            ]);

        $response->assertStatus(404);
    }

    // =========================================================================
    // Delete Tests
    // =========================================================================

    public function test_can_delete_affiliate(): void
    {
        $affiliate = Affiliate::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/api/admin/v2/affiliates/{$affiliate->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('commerce_affiliates', [
            'id' => $affiliate->id,
        ]);
    }

    // =========================================================================
    // Link Management Tests
    // =========================================================================

    public function test_can_view_affiliate_links(): void
    {
        $affiliate = Affiliate::factory()->create(['store_id' => $this->store->id]);
        AffiliateLink::factory()->count(3)->create(['affiliate_id' => $affiliate->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/affiliates/{$affiliate->id}/links");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'url', 'utm_source', 'clicks', 'conversions', 'is_active'],
                ],
            ]);
    }

    public function test_can_create_affiliate_link(): void
    {
        $affiliate = Affiliate::factory()->create(['store_id' => $this->store->id]);

        $linkData = [
            'url' => 'https://example.com/product',
            'utm_source' => 'facebook',
            'utm_medium' => 'social',
            'utm_campaign' => 'summer-sale',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/affiliates/{$affiliate->id}/links", $linkData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.url', 'https://example.com/product')
            ->assertJsonPath('data.utm_source', 'facebook');

        $this->assertDatabaseHas('commerce_affiliate_links', [
            'affiliate_id' => $affiliate->id,
            'url' => 'https://example.com/product',
        ]);
    }

    public function test_cannot_create_link_without_url(): void
    {
        $affiliate = Affiliate::factory()->create(['store_id' => $this->store->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/affiliates/{$affiliate->id}/links", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['url']);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_list_affiliates(): void
    {
        $response = $this->getJson('/api/admin/v2/affiliates');

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_create_affiliate(): void
    {
        $customer = Customer::factory()->create(['store_id' => $this->store->id]);

        $response = $this->postJson('/api/admin/v2/affiliates', [
            'customer_id' => $customer->id,
            'commission_rate' => 10.0,
            'commission_type' => 'percentage',
        ]);

        $response->assertStatus(401);
    }
}
