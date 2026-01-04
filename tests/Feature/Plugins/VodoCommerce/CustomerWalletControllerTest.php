<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\CustomerWallet;
use VodoCommerce\Models\Store;

class CustomerWalletControllerTest extends TestCase
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
    // Deposit Tests
    // =========================================================================

    public function test_can_deposit_to_customer_wallet(): void
    {
        $depositData = [
            'amount' => 100.50,
            'description' => 'Initial deposit',
            'reference' => 'REF-001',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/deposit", $depositData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.amount', 100.50)
            ->assertJsonPath('data.wallet.balance', 100.50);

        $this->assertDatabaseHas('commerce_customer_wallets', [
            'customer_id' => $this->customer->id,
            'balance' => 100.50,
        ]);

        $this->assertDatabaseHas('commerce_customer_wallet_transactions', [
            'type' => 'deposit',
            'amount' => 100.50,
            'description' => 'Initial deposit',
        ]);
    }

    public function test_wallet_is_created_automatically_on_first_deposit(): void
    {
        $this->assertDatabaseMissing('commerce_customer_wallets', [
            'customer_id' => $this->customer->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/deposit", [
                'amount' => 50.00,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('commerce_customer_wallets', [
            'customer_id' => $this->customer->id,
            'balance' => 50.00,
        ]);
    }

    public function test_cannot_deposit_negative_amount(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/deposit", [
                'amount' => -50.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_cannot_deposit_without_amount(): void
    {
        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/deposit", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    // =========================================================================
    // Withdraw Tests
    // =========================================================================

    public function test_can_withdraw_from_customer_wallet(): void
    {
        $wallet = CustomerWallet::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 200.00,
        ]);

        $withdrawData = [
            'amount' => 50.00,
            'description' => 'Withdrawal',
        ];

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/withdraw", $withdrawData);

        $response->assertStatus(201)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.transaction.amount', -50.00)
            ->assertJsonPath('data.wallet.balance', 150.00);

        $this->assertDatabaseHas('commerce_customer_wallets', [
            'customer_id' => $this->customer->id,
            'balance' => 150.00,
        ]);
    }

    public function test_cannot_withdraw_more_than_balance(): void
    {
        CustomerWallet::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 50.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/withdraw", [
                'amount' => 100.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonStructure([
                'status',
                'success',
                'message',
            ]);
    }

    public function test_cannot_withdraw_negative_amount(): void
    {
        CustomerWallet::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 100.00,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/withdraw", [
                'amount' => -50.00,
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['amount']);
    }

    // =========================================================================
    // Transaction History Tests
    // =========================================================================

    public function test_can_view_wallet_transaction_history(): void
    {
        $wallet = CustomerWallet::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 100.00,
        ]);

        $wallet->deposit(50.00, 'Deposit 1');
        $wallet->deposit(30.00, 'Deposit 2');
        $wallet->withdraw(20.00, 'Withdrawal 1');

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$this->customer->id}/wallet/transactions");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'type', 'amount', 'balance_after', 'description', 'created_at'],
                ],
                'pagination',
            ])
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_transactions_by_type(): void
    {
        $wallet = CustomerWallet::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 100.00,
        ]);

        $wallet->deposit(50.00, 'Deposit');
        $wallet->withdraw(20.00, 'Withdrawal');

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$this->customer->id}/wallet/transactions?type=deposit");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.type', 'deposit');
    }

    public function test_transactions_show_correct_balance_after(): void
    {
        $wallet = CustomerWallet::factory()->create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store->id,
            'balance' => 0,
        ]);

        $wallet->deposit(100.00, 'First deposit');
        $wallet->deposit(50.00, 'Second deposit');

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$this->customer->id}/wallet/transactions");

        $response->assertStatus(200);

        $transactions = $response->json('data');
        $this->assertEquals(100.00, $transactions[1]['balance_after']);
        $this->assertEquals(150.00, $transactions[0]['balance_after']);
    }

    // =========================================================================
    // Multi-tenancy Tests
    // =========================================================================

    public function test_cannot_access_wallet_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/customers/{$otherCustomer->id}/wallet/deposit", [
                'amount' => 100.00,
            ]);

        $response->assertStatus(404);
    }

    public function test_cannot_view_transactions_from_different_store(): void
    {
        $otherStore = Store::factory()->create();
        $otherCustomer = Customer::factory()->create(['store_id' => $otherStore->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/customers/{$otherCustomer->id}/wallet/transactions");

        $response->assertStatus(404);
    }

    // =========================================================================
    // Authentication Tests
    // =========================================================================

    public function test_requires_authentication_to_deposit(): void
    {
        $response = $this->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/deposit", [
            'amount' => 100.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_withdraw(): void
    {
        $response = $this->postJson("/api/admin/v2/customers/{$this->customer->id}/wallet/withdraw", [
            'amount' => 50.00,
        ]);

        $response->assertStatus(401);
    }

    public function test_requires_authentication_to_view_transactions(): void
    {
        $response = $this->getJson("/api/admin/v2/customers/{$this->customer->id}/wallet/transactions");

        $response->assertStatus(401);
    }
}
