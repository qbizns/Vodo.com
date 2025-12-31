<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\MarketplaceStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayoutStatus;
use App\Models\Billing\DeveloperPaymentAccount;
use App\Models\Billing\DeveloperPayout;
use App\Models\Billing\PaymentTransaction;
use App\Models\Billing\RevenueSplit;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceListing;
use App\Services\Payment\RevenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RevenueServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RevenueService $service;
    protected MarketplaceListing $listing;

    protected function setUp(): void
    {
        parent::setUp();

        $category = MarketplaceCategory::create([
            'name' => 'Test',
            'slug' => 'test',
            'description' => 'Test category',
        ]);

        $this->listing = MarketplaceListing::create([
            'developer_id' => 1,
            'category_id' => $category->id,
            'plugin_slug' => 'test-plugin',
            'name' => 'Test Plugin',
            'short_description' => 'A test plugin',
            'description' => 'A test plugin for testing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'subscription',
            'price' => 100.00,
        ]);

        $this->service = app(RevenueService::class);
    }

    public function test_record_revenue_creates_split(): void
    {
        $transaction = PaymentTransaction::create([
            'uuid' => Str::uuid(),
            'tenant_id' => 1,
            'gateway' => 'stripe',
            'gateway_transaction_id' => 'pi_test_123',
            'type' => 'charge',
            'status' => PaymentStatus::Succeeded,
            'currency' => 'SAR',
            'amount' => 100.00,
            'fee' => 3.00,
            'net_amount' => 97.00,
        ]);

        $split = $this->service->recordRevenue($transaction, $this->listing, 100.00);

        $this->assertEquals($this->listing->id, $split->listing_id);
        $this->assertEquals($this->listing->developer_id, $split->developer_id);
        $this->assertEquals(100.00, $split->gross_amount);
        $this->assertEquals('pending', $split->status);
    }

    public function test_platform_fee_calculated_correctly(): void
    {
        $transaction = PaymentTransaction::create([
            'uuid' => Str::uuid(),
            'tenant_id' => 1,
            'gateway' => 'stripe',
            'type' => 'charge',
            'status' => PaymentStatus::Succeeded,
            'currency' => 'SAR',
            'amount' => 100.00,
            'fee' => 3.00,
            'net_amount' => 97.00,
        ]);

        $split = $this->service->recordRevenue($transaction, $this->listing, 100.00);

        // Default 20% platform fee
        $this->assertEquals(20.00, round($split->platform_fee, 2));
        $this->assertEquals(0.20, $split->platform_fee_rate);
        $this->assertEquals(3.00, $split->gateway_fee);

        // Developer gets: gross - platform fee - gateway fee
        $expectedDeveloperAmount = 100.00 - 20.00 - 3.00;
        $this->assertEquals($expectedDeveloperAmount, round($split->developer_amount, 2));
    }

    public function test_custom_commission_rate_applied(): void
    {
        DeveloperPaymentAccount::create([
            'developer_id' => 1,
            'gateway' => 'stripe',
            'account_status' => 'verified',
            'commission_rate' => 0.15, // 15% instead of 20%
        ]);

        $transaction = PaymentTransaction::create([
            'uuid' => Str::uuid(),
            'tenant_id' => 1,
            'gateway' => 'stripe',
            'type' => 'charge',
            'status' => PaymentStatus::Succeeded,
            'currency' => 'SAR',
            'amount' => 100.00,
            'fee' => 0,
            'net_amount' => 100.00,
        ]);

        $split = $this->service->recordRevenue($transaction, $this->listing, 100.00);

        $this->assertEquals(15.00, round($split->platform_fee, 2));
        $this->assertEquals(0.15, $split->platform_fee_rate);
    }

    public function test_get_developer_earnings(): void
    {
        $this->createRevenueSplits();

        $earnings = $this->service->getDeveloperEarnings(1);

        $this->assertArrayHasKey('pending', $earnings);
        $this->assertArrayHasKey('available', $earnings);
        $this->assertArrayHasKey('paid', $earnings);
        $this->assertArrayHasKey('total', $earnings);
    }

    public function test_mark_as_available_respects_holding_period(): void
    {
        config(['billing.payout_holding_days' => 7]);

        // Create a split from 10 days ago
        RevenueSplit::create([
            'transaction_id' => $this->createTransaction()->id,
            'listing_id' => $this->listing->id,
            'developer_id' => 1,
            'currency' => 'SAR',
            'gross_amount' => 100,
            'platform_fee' => 20,
            'platform_fee_rate' => 0.20,
            'developer_amount' => 80,
            'status' => 'pending',
            'created_at' => now()->subDays(10),
        ]);

        // Create a split from 3 days ago
        RevenueSplit::create([
            'transaction_id' => $this->createTransaction()->id,
            'listing_id' => $this->listing->id,
            'developer_id' => 1,
            'currency' => 'SAR',
            'gross_amount' => 100,
            'platform_fee' => 20,
            'platform_fee_rate' => 0.20,
            'developer_amount' => 80,
            'status' => 'pending',
            'created_at' => now()->subDays(3),
        ]);

        $count = $this->service->markAsAvailable(1);

        // Only the 10-day old split should be marked available
        $this->assertEquals(1, $count);
        $this->assertEquals(1, RevenueSplit::where('status', 'available')->count());
        $this->assertEquals(1, RevenueSplit::where('status', 'pending')->count());
    }

    public function test_create_payout_requires_verified_account(): void
    {
        DeveloperPaymentAccount::create([
            'developer_id' => 1,
            'gateway' => 'stripe',
            'account_status' => 'pending', // Not verified
        ]);

        $this->createAvailableRevenueSplit();

        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
        $this->service->createPayout(1);
    }

    public function test_create_payout_requires_available_funds(): void
    {
        DeveloperPaymentAccount::create([
            'developer_id' => 1,
            'gateway' => 'stripe',
            'gateway_account_id' => 'acct_123',
            'account_status' => 'verified',
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('No available funds');

        $this->service->createPayout(1);
    }

    public function test_create_payout_respects_minimum(): void
    {
        DeveloperPaymentAccount::create([
            'developer_id' => 1,
            'gateway' => 'stripe',
            'gateway_account_id' => 'acct_123',
            'account_status' => 'verified',
            'minimum_payout' => 500.00,
        ]);

        $this->createAvailableRevenueSplit(80.00); // Less than minimum

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('below minimum payout threshold');

        $this->service->createPayout(1);
    }

    public function test_create_payout_links_revenue_splits(): void
    {
        $account = DeveloperPaymentAccount::create([
            'developer_id' => 1,
            'gateway' => 'stripe',
            'gateway_account_id' => 'acct_123',
            'account_status' => 'verified',
            'minimum_payout' => 10.00,
        ]);

        $split1 = $this->createAvailableRevenueSplit(100.00);
        $split2 = $this->createAvailableRevenueSplit(150.00);

        $payout = $this->service->createPayout(1);

        $this->assertEquals(PayoutStatus::Pending, $payout->status);
        $this->assertEquals(250.00, $payout->gross_amount);
        $this->assertEquals(2, $payout->items_count);

        $split1->refresh();
        $split2->refresh();

        $this->assertEquals($payout->id, $split1->payout_id);
        $this->assertEquals($payout->id, $split2->payout_id);
    }

    public function test_get_platform_stats(): void
    {
        $this->createRevenueSplits();

        $stats = $this->service->getPlatformStats('month');

        $this->assertArrayHasKey('gross_revenue', $stats);
        $this->assertArrayHasKey('platform_fees', $stats);
        $this->assertArrayHasKey('developer_payouts', $stats);
        $this->assertArrayHasKey('transaction_count', $stats);
    }

    protected function createTransaction(): PaymentTransaction
    {
        return PaymentTransaction::create([
            'uuid' => Str::uuid(),
            'tenant_id' => 1,
            'gateway' => 'stripe',
            'type' => 'charge',
            'status' => PaymentStatus::Succeeded,
            'currency' => 'SAR',
            'amount' => 100.00,
            'fee' => 0,
            'net_amount' => 100.00,
        ]);
    }

    protected function createRevenueSplits(): void
    {
        RevenueSplit::create([
            'transaction_id' => $this->createTransaction()->id,
            'listing_id' => $this->listing->id,
            'developer_id' => 1,
            'currency' => 'SAR',
            'gross_amount' => 100,
            'platform_fee' => 20,
            'platform_fee_rate' => 0.20,
            'developer_amount' => 80,
            'status' => 'pending',
        ]);

        RevenueSplit::create([
            'transaction_id' => $this->createTransaction()->id,
            'listing_id' => $this->listing->id,
            'developer_id' => 1,
            'currency' => 'SAR',
            'gross_amount' => 50,
            'platform_fee' => 10,
            'platform_fee_rate' => 0.20,
            'developer_amount' => 40,
            'status' => 'available',
        ]);
    }

    protected function createAvailableRevenueSplit(float $amount = 80.00): RevenueSplit
    {
        return RevenueSplit::create([
            'transaction_id' => $this->createTransaction()->id,
            'listing_id' => $this->listing->id,
            'developer_id' => 1,
            'currency' => 'SAR',
            'gross_amount' => $amount / 0.8, // Reverse calculate from developer amount
            'platform_fee' => ($amount / 0.8) * 0.2,
            'platform_fee_rate' => 0.20,
            'developer_amount' => $amount,
            'status' => 'available',
        ]);
    }
}
