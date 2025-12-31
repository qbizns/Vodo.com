<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Enums\InvoiceStatus;
use App\Enums\MarketplaceStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\PaymentMethod;
use App\Models\Marketplace\MarketplaceCategory;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceSubscription;
use App\Services\Payment\BillingService;
use App\Services\Payment\PaymentManager;
use App\Services\Payment\RevenueService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingService $service;
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
            'description' => 'A test plugin for testing billing',
            'status' => MarketplaceStatus::Published,
            'pricing_model' => 'subscription',
            'price' => 29.99,
        ]);

        $this->service = app(BillingService::class);
    }

    public function test_create_purchase_invoice(): void
    {
        $invoice = $this->service->createPurchaseInvoice($this->listing, 1, 'SAR');

        $this->assertDatabaseHas('marketplace_invoices', [
            'id' => $invoice->id,
            'tenant_id' => 1,
            'status' => InvoiceStatus::Pending->value,
            'currency' => 'SAR',
        ]);

        $this->assertCount(1, $invoice->items);
        $this->assertEquals($this->listing->id, $invoice->items->first()->listing_id);
    }

    public function test_create_subscription_invoice(): void
    {
        $subscription = MarketplaceSubscription::create([
            'listing_id' => $this->listing->id,
            'tenant_id' => 1,
            'status' => 'active',
            'currency' => 'SAR',
            'billing_cycle' => 'monthly',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $invoice = $this->service->createSubscriptionInvoice($subscription, 'monthly');

        $this->assertEquals(InvoiceStatus::Pending, $invoice->status);
        $this->assertEquals($subscription->id, $invoice->subscription_id);
        $this->assertEquals('monthly', $invoice->billing_period);
        $this->assertNotNull($invoice->period_start);
        $this->assertNotNull($invoice->period_end);
    }

    public function test_yearly_subscription_gets_discount(): void
    {
        $subscription = MarketplaceSubscription::create([
            'listing_id' => $this->listing->id,
            'tenant_id' => 1,
            'status' => 'active',
            'currency' => 'SAR',
            'billing_cycle' => 'yearly',
            'current_period_start' => now(),
            'current_period_end' => now()->addYear(),
        ]);

        $monthlyInvoice = $this->service->createSubscriptionInvoice($subscription, 'monthly');
        $yearlyInvoice = $this->service->createSubscriptionInvoice($subscription, 'yearly');

        // Yearly should be 80% of 12 months (20% discount)
        $expectedYearly = $this->listing->price * 12 * 0.8;

        $this->assertEquals(round($expectedYearly, 2), round($yearlyInvoice->subtotal, 2));
        $this->assertLessThan($monthlyInvoice->subtotal * 12, $yearlyInvoice->subtotal);
    }

    public function test_invoice_includes_tax(): void
    {
        config(['billing.tax_rate' => 0.15]);

        $invoice = $this->service->createPurchaseInvoice($this->listing, 1, 'SAR');

        $expectedTax = $invoice->subtotal * 0.15;
        $expectedTotal = $invoice->subtotal + $expectedTax;

        $this->assertEquals(round($expectedTax, 2), round($invoice->tax_amount, 2));
        $this->assertEquals(round($expectedTotal, 2), round($invoice->total, 2));
    }

    public function test_invoice_number_is_unique(): void
    {
        $invoice1 = $this->service->createPurchaseInvoice($this->listing, 1, 'SAR');
        $invoice2 = $this->service->createPurchaseInvoice($this->listing, 2, 'SAR');

        $this->assertNotEquals($invoice1->invoice_number, $invoice2->invoice_number);
        $this->assertStringStartsWith('INV-', $invoice1->invoice_number);
    }

    public function test_invoice_has_due_date(): void
    {
        $invoice = $this->service->createPurchaseInvoice($this->listing, 1, 'SAR');

        $this->assertNotNull($invoice->due_date);
        $this->assertTrue($invoice->due_date->isFuture());
    }
}
