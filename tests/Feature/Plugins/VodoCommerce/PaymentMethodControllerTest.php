<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Store;

class PaymentMethodControllerTest extends TestCase
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

    public function test_can_list_all_payment_methods(): void
    {
        PaymentMethod::factory()->count(3)->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'type',
                        'provider',
                        'is_active',
                        'is_default',
                    ],
                ],
            ]);
    }

    public function test_can_filter_active_payment_methods(): void
    {
        PaymentMethod::factory()->count(2)->active()->create([
            'store_id' => $this->store->id,
        ]);
        PaymentMethod::factory()->count(1)->inactive()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods?active_only=1');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_payment_methods_are_ordered_by_display_order(): void
    {
        PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'First',
            'display_order' => 1,
        ]);
        PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Third',
            'display_order' => 3,
        ]);
        PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'name' => 'Second',
            'display_order' => 2,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods');

        $response->assertStatus(200);

        $names = collect($response->json('data'))->pluck('name')->toArray();
        $this->assertEquals(['First', 'Second', 'Third'], $names);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_payment_method_details(): void
    {
        $paymentMethod = PaymentMethod::factory()->stripe()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/payment-methods/{$paymentMethod->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $paymentMethod->id)
            ->assertJsonPath('data.name', $paymentMethod->name)
            ->assertJsonPath('data.provider', PaymentMethod::PROVIDER_STRIPE)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'type',
                    'provider',
                    'fees',
                    'supported_currencies',
                    'is_configured',
                ],
            ]);
    }

    public function test_cannot_show_nonexistent_payment_method(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Available Payment Methods Tests
    // =========================================================================

    public function test_can_get_available_payment_methods_for_checkout(): void
    {
        PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'supported_currencies' => ['USD', 'EUR'],
            'minimum_amount' => 10,
            'maximum_amount' => 1000,
        ]);

        PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'supported_currencies' => ['USD'],
            'minimum_amount' => 5,
            'maximum_amount' => 500,
        ]);

        // This one should be filtered out (inactive)
        PaymentMethod::factory()->inactive()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/available?amount=100&currency=USD');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data');
    }

    public function test_available_methods_filtered_by_currency(): void
    {
        PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'supported_currencies' => ['USD'],
        ]);

        PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'supported_currencies' => ['EUR'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/available?amount=100&currency=USD');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_available_methods_filtered_by_amount(): void
    {
        PaymentMethod::factory()->active()->withLimits(10, 100)->create([
            'store_id' => $this->store->id,
        ]);

        PaymentMethod::factory()->active()->withLimits(50, 500)->create([
            'store_id' => $this->store->id,
        ]);

        // Amount below first method's minimum
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/available?amount=5&currency=USD');

        $response->assertStatus(200)
            ->assertJsonCount(0, 'data');

        // Amount valid for first method
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/available?amount=50&currency=USD');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');

        // Amount above first method's maximum
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/available?amount=200&currency=USD');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_available_methods_filtered_by_country(): void
    {
        PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'supported_countries' => ['US', 'CA'],
        ]);

        PaymentMethod::factory()->active()->create([
            'store_id' => $this->store->id,
            'supported_countries' => ['GB', 'FR'],
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/payment-methods/available?amount=100&currency=USD&country=US');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    // =========================================================================
    // Banks Tests
    // =========================================================================

    public function test_can_get_supported_banks(): void
    {
        $paymentMethod = PaymentMethod::factory()->withBanks([
            'Bank of America',
            'Chase',
            'Wells Fargo',
        ])->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/banks");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data.banks')
            ->assertJsonPath('data.banks.0', 'Bank of America')
            ->assertJsonPath('data.banks.1', 'Chase')
            ->assertJsonPath('data.banks.2', 'Wells Fargo');
    }

    public function test_returns_empty_banks_array_when_not_configured(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/banks");

        $response->assertStatus(200)
            ->assertJsonPath('data.banks', []);
    }

    // =========================================================================
    // Calculate Fees Tests
    // =========================================================================

    public function test_can_calculate_fees_for_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'fees' => [
                'fixed' => 0.30,
                'percentage' => 2.9,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/calculate-fees", [
                'amount' => 100.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.gross_amount', 100.00)
            ->assertJsonPath('data.fee_amount', 3.20)
            ->assertJsonPath('data.net_amount', 96.80);
    }

    public function test_calculate_fees_applies_minimum_fee(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'fees' => [
                'fixed' => 0.30,
                'percentage' => 2.9,
                'min' => 5.00,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/calculate-fees", [
                'amount' => 10.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.fee_amount', 5.00);
    }

    public function test_calculate_fees_applies_maximum_fee(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'fees' => [
                'fixed' => 0,
                'percentage' => 10,
                'max' => 50.00,
            ],
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/calculate-fees", [
                'amount' => 1000.00,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.fee_amount', 50.00);
    }

    public function test_calculate_fees_requires_amount(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/calculate-fees", []);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Test Connection Tests
    // =========================================================================

    public function test_can_test_connection_for_configured_stripe(): void
    {
        $paymentMethod = PaymentMethod::factory()->stripe()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/test-connection");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.success', true);
    }

    public function test_connection_test_fails_for_unconfigured_payment_method(): void
    {
        $paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
            'provider' => PaymentMethod::PROVIDER_STRIPE,
            'configuration' => [], // No credentials
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/test-connection");

        $response->assertStatus(200)
            ->assertJsonPath('data.success', false);
    }

    public function test_offline_payment_methods_always_pass_connection_test(): void
    {
        $paymentMethod = PaymentMethod::factory()->offline()->create([
            'store_id' => $this->store->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/payment-methods/{$paymentMethod->id}/test-connection");

        $response->assertStatus(200)
            ->assertJsonPath('data.success', true);
    }
}
