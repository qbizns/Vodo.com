<?php

declare(strict_types=1);

namespace Tests\Feature\Plugins\VodoCommerce;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Order;
use VodoCommerce\Models\PaymentMethod;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Transaction;

class TransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Store $store;
    protected PaymentMethod $paymentMethod;
    protected Customer $customer;
    protected Order $order;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->store = Store::factory()->create();
        $this->paymentMethod = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
        ]);
        $this->customer = Customer::factory()->create([
            'store_id' => $this->store->id,
        ]);
        $this->order = Order::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);
    }

    // =========================================================================
    // Index Tests
    // =========================================================================

    public function test_can_list_all_transactions(): void
    {
        Transaction::factory()->count(3)->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'transaction_id',
                        'type',
                        'status',
                        'amount',
                        'currency',
                    ],
                ],
                'metadata' => [
                    'statistics',
                    'filters',
                    'pagination',
                ],
            ]);
    }

    public function test_can_filter_transactions_by_status(): void
    {
        Transaction::factory()->count(2)->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(1)->pending()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions?status=completed');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_transactions_by_type(): void
    {
        Transaction::factory()->count(2)->payment()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(1)->refund()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions?type=payment');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_transactions_by_customer(): void
    {
        $customer2 = Customer::factory()->create(['store_id' => $this->store->id]);

        Transaction::factory()->count(2)->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(1)->create([
            'store_id' => $this->store->id,
            'customer_id' => $customer2->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/transactions?customer_id={$this->customer->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_transactions_by_order(): void
    {
        $order2 = Order::factory()->create([
            'store_id' => $this->store->id,
            'customer_id' => $this->customer->id,
        ]);

        Transaction::factory()->count(2)->create([
            'store_id' => $this->store->id,
            'order_id' => $this->order->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(1)->create([
            'store_id' => $this->store->id,
            'order_id' => $order2->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/transactions?order_id={$this->order->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_transactions_by_currency(): void
    {
        Transaction::factory()->count(2)->currency('USD')->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(1)->currency('EUR')->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions?currency=USD');

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_transactions_by_date_range(): void
    {
        Transaction::factory()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
            'created_at' => now()->subDays(10),
        ]);
        Transaction::factory()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions?start_date=' . now()->subDays(7)->toDateString() . '&end_date=' . now()->toDateString());

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_index_includes_statistics(): void
    {
        Transaction::factory()->count(3)->completed()->payment()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'metadata' => [
                    'statistics' => [
                        'total_count',
                        'total_amount',
                        'total_fees',
                        'total_net',
                        'completed_count',
                        'pending_count',
                        'failed_count',
                        'refund_count',
                    ],
                ],
            ]);
    }

    public function test_index_supports_pagination(): void
    {
        Transaction::factory()->count(100)->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions?per_page=20&page=2');

        $response->assertStatus(200)
            ->assertJsonCount(20, 'data')
            ->assertJsonPath('metadata.pagination.current_page', 2)
            ->assertJsonPath('metadata.pagination.per_page', 20);
    }

    // =========================================================================
    // Show Tests
    // =========================================================================

    public function test_can_show_transaction_details(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.id', $transaction->id)
            ->assertJsonPath('data.transaction_id', $transaction->transaction_id)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'transaction_id',
                    'type',
                    'status',
                    'amount',
                    'fee_amount',
                    'net_amount',
                    'currency',
                    'payment_method',
                    'can_be_refunded',
                    'refundable_amount',
                ],
            ]);
    }

    public function test_show_includes_refunds_relationship(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $refund = Transaction::factory()->refund()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
            'parent_transaction_id' => $transaction->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/admin/v2/transactions/{$transaction->id}");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.refunds');
    }

    public function test_cannot_show_nonexistent_transaction(): void
    {
        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions/99999');

        $response->assertStatus(404);
    }

    // =========================================================================
    // Update Tests
    // =========================================================================

    public function test_can_update_transaction_metadata(): void
    {
        $transaction = Transaction::factory()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/transactions/{$transaction->id}", [
                'metadata' => [
                    'custom_field' => 'custom_value',
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.metadata.custom_field', 'custom_value');

        $this->assertDatabaseHas('commerce_transactions', [
            'id' => $transaction->id,
        ]);
    }

    public function test_can_update_transaction_notes(): void
    {
        $transaction = Transaction::factory()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/transactions/{$transaction->id}", [
                'notes' => 'Customer requested special handling',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.notes', 'Customer requested special handling');
    }

    public function test_update_requires_valid_fields(): void
    {
        $transaction = Transaction::factory()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/api/admin/v2/transactions/{$transaction->id}", []);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Statistics Tests
    // =========================================================================

    public function test_can_get_transaction_statistics(): void
    {
        Transaction::factory()->count(5)->completed()->payment()->amount(100)->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions/statistics');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'payment_statistics' => [
                        'total_revenue',
                        'total_fees',
                        'gross_revenue',
                        'total_transactions',
                        'completed_transactions',
                        'pending_transactions',
                        'failed_transactions',
                        'average_transaction_value',
                    ],
                    'refund_statistics' => [
                        'total_refunded',
                        'total_refunds',
                        'completed_refunds',
                        'pending_refunds',
                    ],
                ],
            ]);
    }

    public function test_can_filter_statistics_by_currency(): void
    {
        Transaction::factory()->count(2)->completed()->currency('USD')->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(1)->completed()->currency('EUR')->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions/statistics?currency=USD');

        $response->assertStatus(200)
            ->assertJsonPath('data.payment_statistics.currency', 'USD');
    }

    public function test_can_filter_statistics_by_date_range(): void
    {
        Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
            'created_at' => now()->subDays(30),
        ]);
        Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions/statistics?start_date=' . now()->subDays(7)->toDateString() . '&end_date=' . now()->toDateString());

        $response->assertStatus(200)
            ->assertJsonPath('data.period.start_date', now()->subDays(7)->toDateString())
            ->assertJsonPath('data.period.end_date', now()->toDateString());
    }

    // =========================================================================
    // Revenue by Payment Method Tests
    // =========================================================================

    public function test_can_get_revenue_by_payment_method(): void
    {
        $paymentMethod2 = PaymentMethod::factory()->create([
            'store_id' => $this->store->id,
        ]);

        Transaction::factory()->count(3)->completed()->payment()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);
        Transaction::factory()->count(2)->completed()->payment()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $paymentMethod2->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/api/admin/v2/transactions/revenue-by-payment-method');

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonCount(2, 'data.revenue_by_payment_method')
            ->assertJsonStructure([
                'data' => [
                    'revenue_by_payment_method' => [
                        '*' => [
                            'payment_method',
                            'total_revenue',
                            'total_fees',
                            'transaction_count',
                        ],
                    ],
                    'total_payment_methods',
                ],
            ]);
    }

    // =========================================================================
    // Process Transaction Tests
    // =========================================================================

    public function test_can_process_pending_transaction(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/process", [
                'external_id' => 'ext_123456',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Transaction::STATUS_COMPLETED)
            ->assertJsonPath('message', 'Transaction processed successfully');

        $this->assertDatabaseHas('commerce_transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_COMPLETED,
            'external_id' => 'ext_123456',
        ]);
    }

    public function test_can_process_processing_transaction(): void
    {
        $transaction = Transaction::factory()->processing()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/process");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', Transaction::STATUS_COMPLETED);
    }

    public function test_cannot_process_completed_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/process");

        $response->assertStatus(422);
    }

    // =========================================================================
    // Fail Transaction Tests
    // =========================================================================

    public function test_can_fail_transaction(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/fail", [
                'reason' => 'Insufficient funds',
                'code' => 'insufficient_funds',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Transaction::STATUS_FAILED)
            ->assertJsonPath('message', 'Transaction marked as failed');

        $this->assertDatabaseHas('commerce_transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_FAILED,
            'failure_reason' => 'Insufficient funds',
            'failure_code' => 'insufficient_funds',
        ]);
    }

    public function test_cannot_fail_completed_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/fail", [
                'reason' => 'Test failure',
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Create Refund Tests
    // =========================================================================

    public function test_can_create_refund_for_completed_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->amount(100)->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/refund", [
                'amount' => 50,
                'reason' => 'Customer requested refund',
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.type', Transaction::TYPE_REFUND)
            ->assertJsonPath('data.amount', 50)
            ->assertJsonPath('message', 'Refund created successfully');

        $this->assertDatabaseHas('commerce_transactions', [
            'parent_transaction_id' => $transaction->id,
            'type' => Transaction::TYPE_REFUND,
            'amount' => 50,
        ]);
    }

    public function test_refund_amount_must_be_greater_than_zero(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/refund", [
                'amount' => 0,
            ]);

        $response->assertStatus(422);
    }

    public function test_cannot_refund_more_than_original_amount(): void
    {
        $transaction = Transaction::factory()->completed()->amount(100)->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/refund", [
                'amount' => 150,
            ]);

        $response->assertStatus(422);
    }

    // =========================================================================
    // Cancel Transaction Tests
    // =========================================================================

    public function test_can_cancel_pending_transaction(): void
    {
        $transaction = Transaction::factory()->pending()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/cancel");

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', Transaction::STATUS_CANCELLED)
            ->assertJsonPath('message', 'Transaction cancelled successfully');

        $this->assertDatabaseHas('commerce_transactions', [
            'id' => $transaction->id,
            'status' => Transaction::STATUS_CANCELLED,
        ]);
    }

    public function test_cannot_cancel_completed_transaction(): void
    {
        $transaction = Transaction::factory()->completed()->create([
            'store_id' => $this->store->id,
            'payment_method_id' => $this->paymentMethod->id,
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/admin/v2/transactions/{$transaction->id}/cancel");

        $response->assertStatus(422);
    }
}
