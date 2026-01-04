<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\LoyaltyPoint;
use VodoCommerce\Models\Store;

class LoyaltyPointControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->customer = Customer::factory()->create(['store_id' => $this->store->id]);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_view_customer_loyalty_points(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 500,
            'lifetime_earned' => 1000,
            'lifetime_spent' => 500,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.balance', 500)
            ->assertJsonPath('data.lifetime_earned', 1000)
            ->assertJsonPath('data.lifetime_spent', 500);
    }

    public function test_creates_loyalty_points_account_if_not_exists(): void
    {
        $this->assertDatabaseMissing('commerce_loyalty_points', [
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points");

        $response->assertStatus(200)
            ->assertJsonPath('data.balance', 0)
            ->assertJsonPath('data.lifetime_earned', 0);

        $this->assertDatabaseHas('commerce_loyalty_points', [
            'customer_id' => $this->customer->id,
            'balance' => 0,
        ]);
    }

    public function test_cannot_view_loyalty_points_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$otherCustomer->id}/loyalty-points");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Adjust Tests
    // =========================================================================

    public function test_can_earn_loyalty_points(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'earned',
                'points' => 250,
                'description' => 'Bonus points for purchase',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.points', 250)
            ->assertJsonPath('data.transaction.type', 'earned')
            ->assertJsonPath('data.loyalty_points.balance', 350);

        $this->assertDatabaseHas('commerce_loyalty_points', [
            'customer_id' => $this->customer->id,
            'balance' => 350,
        ]);

        $this->assertDatabaseHas('commerce_loyalty_point_transactions', [
            'type' => 'earned',
            'points' => 250,
            'balance_after' => 350,
        ]);
    }

    public function test_can_spend_loyalty_points(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 500,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'spent',
                'points' => 100,
                'description' => 'Redeemed for discount',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.points', -100)
            ->assertJsonPath('data.transaction.type', 'spent')
            ->assertJsonPath('data.loyalty_points.balance', 400);

        $this->assertDatabaseHas('commerce_loyalty_points', [
            'customer_id' => $this->customer->id,
            'balance' => 400,
        ]);
    }

    public function test_can_adjust_loyalty_points(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 100,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'adjusted',
                'points' => 50,
                'description' => 'Manual adjustment',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.type', 'adjusted')
            ->assertJsonPath('data.loyalty_points.balance', 150);
    }

    public function test_cannot_spend_more_points_than_balance(): void
    {
        LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 50,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'spent',
                'points' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'success',
                'message',
            ]);
    }

    public function test_validates_adjustment_type(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'invalid',
                'points' => 100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['type']);
    }

    public function test_validates_points_is_required(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'earned',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points']);
    }

    public function test_validates_points_is_positive(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
                'type' => 'earned',
                'points' => -100,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['points']);
    }

    // =========================================================================
    // Transaction History Tests
    // =========================================================================

    public function test_transactions_show_correct_balance_progression(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 0,
        ]);

        $loyaltyPoints->earn(100, 'First earn');
        $loyaltyPoints->earn(50, 'Second earn');
        $loyaltyPoints->spend(30, 'First spend');

        $transactions = $loyaltyPoints->transactions()->orderBy('created_at')->get();

        $this->assertEquals(100, $transactions[0]->balance_after);
        $this->assertEquals(150, $transactions[1]->balance_after);
        $this->assertEquals(120, $transactions[2]->balance_after);
    }

    public function test_lifetime_earned_tracks_total_points_earned(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 0,
            'lifetime_earned' => 0,
        ]);

        $loyaltyPoints->earn(100, 'First');
        $loyaltyPoints->earn(50, 'Second');
        $loyaltyPoints->spend(30, 'Spend');

        $loyaltyPoints->refresh();

        $this->assertEquals(150, $loyaltyPoints->lifetime_earned);
        $this->assertEquals(30, $loyaltyPoints->lifetime_spent);
        $this->assertEquals(120, $loyaltyPoints->balance);
    }

    // =========================================================================
    // Expiration Tests
    // =========================================================================

    public function test_can_set_expiration_date_for_points(): void
    {
        $loyaltyPoints = LoyaltyPoint::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'expires_at' => now()->addYear(),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['id', 'balance', 'expires_at'],
            ]);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_view_loyalty_points(): void
    {
        $response = $this->getJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points");

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_adjust_loyalty_points(): void
    {
        $response = $this->postJson("/api/admin/v2/customers/{$this->customer->id}/loyalty-points/adjust", [
            'type' => 'earned',
            'points' => 100,
        ]);

        $response->assertStatus(401);
    }
}
