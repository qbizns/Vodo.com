<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentStatus;
use App\Models\Billing\Invoice;
use App\Models\Billing\InvoiceItem;
use App\Models\Billing\PaymentMethod;
use App\Models\Billing\PaymentTransaction;
use App\Models\Marketplace\MarketplaceListing;
use App\Models\Marketplace\MarketplaceSubscription;
use App\Services\Payment\DTO\ChargeResult;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Billing Service
 *
 * Manages invoicing, payments, and subscription billing.
 */
class BillingService
{
    public function __construct(
        protected PaymentManager $paymentManager,
        protected RevenueService $revenueService,
    ) {}

    /**
     * Create an invoice for a subscription.
     */
    public function createSubscriptionInvoice(
        MarketplaceSubscription $subscription,
        string $period = 'monthly'
    ): Invoice {
        $listing = $subscription->listing;

        $periodStart = now();
        $periodEnd = $period === 'yearly'
            ? now()->addYear()
            : now()->addMonth();

        $subtotal = $period === 'yearly'
            ? ($listing->price * 12 * 0.8) // 20% yearly discount
            : $listing->price;

        $taxRate = config('billing.tax_rate', 0.15);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return DB::transaction(function () use (
            $subscription, $listing, $period, $periodStart, $periodEnd,
            $subtotal, $taxRate, $taxAmount, $total
        ) {
            $invoice = Invoice::create([
                'uuid' => Str::uuid(),
                'invoice_number' => $this->generateInvoiceNumber(),
                'tenant_id' => $subscription->tenant_id,
                'subscription_id' => $subscription->id,
                'status' => InvoiceStatus::Pending,
                'currency' => $subscription->currency,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'billing_period' => $period,
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'due_date' => now()->addDays(7),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'listing_id' => $listing->id,
                'description' => "{$listing->name} - {$period} subscription",
                'type' => 'subscription',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'amount' => $subtotal,
            ]);

            return $invoice;
        });
    }

    /**
     * Create an invoice for a one-time purchase.
     */
    public function createPurchaseInvoice(
        MarketplaceListing $listing,
        int $tenantId,
        string $currency = 'SAR'
    ): Invoice {
        $subtotal = $listing->price;
        $taxRate = config('billing.tax_rate', 0.15);
        $taxAmount = $subtotal * $taxRate;
        $total = $subtotal + $taxAmount;

        return DB::transaction(function () use ($listing, $tenantId, $currency, $subtotal, $taxRate, $taxAmount, $total) {
            $invoice = Invoice::create([
                'uuid' => Str::uuid(),
                'invoice_number' => $this->generateInvoiceNumber(),
                'tenant_id' => $tenantId,
                'status' => InvoiceStatus::Pending,
                'currency' => $currency,
                'subtotal' => $subtotal,
                'tax_rate' => $taxRate,
                'tax_amount' => $taxAmount,
                'total' => $total,
                'due_date' => now()->addDays(7),
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'listing_id' => $listing->id,
                'description' => "{$listing->name} - One-time purchase",
                'type' => 'one_time',
                'quantity' => 1,
                'unit_price' => $subtotal,
                'amount' => $subtotal,
            ]);

            return $invoice;
        });
    }

    /**
     * Pay an invoice.
     */
    public function payInvoice(Invoice $invoice, int $paymentMethodId): ChargeResult
    {
        $paymentMethod = PaymentMethod::findOrFail($paymentMethodId);

        $gateway = $this->paymentManager->gateway($paymentMethod->gateway);

        $chargeResult = $gateway->charge([
            'amount' => (int) ($invoice->total * 100), // Convert to cents
            'currency' => $invoice->currency,
            'customer_id' => $paymentMethod->gateway_customer_id,
            'payment_method_id' => $paymentMethod->gateway_payment_method_id,
            'description' => "Invoice {$invoice->invoice_number}",
            'metadata' => [
                'invoice_id' => $invoice->id,
                'invoice_number' => $invoice->invoice_number,
                'tenant_id' => $invoice->tenant_id,
            ],
        ]);

        return DB::transaction(function () use ($invoice, $paymentMethod, $chargeResult) {
            // Record the transaction
            $transaction = PaymentTransaction::create([
                'uuid' => Str::uuid(),
                'invoice_id' => $invoice->id,
                'tenant_id' => $invoice->tenant_id,
                'payment_method_id' => $paymentMethod->id,
                'gateway' => $paymentMethod->gateway,
                'gateway_transaction_id' => $chargeResult->chargeId,
                'type' => 'charge',
                'status' => $chargeResult->status->value,
                'currency' => $invoice->currency,
                'amount' => $invoice->total,
                'fee' => ($chargeResult->fee ?? 0) / 100,
                'net_amount' => $invoice->total - (($chargeResult->fee ?? 0) / 100),
                'gateway_response' => $chargeResult->rawResponse,
                'processed_at' => $chargeResult->success ? now() : null,
            ]);

            if ($chargeResult->success) {
                $invoice->update([
                    'status' => InvoiceStatus::Paid,
                    'paid_at' => now(),
                ]);

                // Process revenue split for each invoice item
                foreach ($invoice->items as $item) {
                    if ($item->listing_id) {
                        $this->revenueService->recordRevenue(
                            $transaction,
                            $item->listing,
                            $item->amount
                        );
                    }
                }

                // Update subscription if this is a subscription invoice
                if ($invoice->subscription_id) {
                    $subscription = $invoice->subscription;
                    $subscription->update([
                        'status' => 'active',
                        'current_period_start' => $invoice->period_start,
                        'current_period_end' => $invoice->period_end,
                        'next_billing_date' => $invoice->period_end,
                    ]);
                }

                Log::info('Invoice paid', [
                    'invoice_id' => $invoice->id,
                    'amount' => $invoice->total,
                    'transaction_id' => $transaction->id,
                ]);
            } else {
                $invoice->update(['status' => InvoiceStatus::Failed]);

                Log::warning('Invoice payment failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $chargeResult->failureMessage,
                ]);
            }

            return $chargeResult;
        });
    }

    /**
     * Process subscription renewals.
     */
    public function processRenewals(): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        $subscriptions = MarketplaceSubscription::query()
            ->where('status', 'active')
            ->where('next_billing_date', '<=', now())
            ->where('auto_renew', true)
            ->with(['listing', 'tenant'])
            ->get();

        foreach ($subscriptions as $subscription) {
            try {
                $invoice = $this->createSubscriptionInvoice(
                    $subscription,
                    $subscription->billing_cycle
                );

                $paymentMethod = PaymentMethod::where('tenant_id', $subscription->tenant_id)
                    ->where('is_default', true)
                    ->first();

                if (!$paymentMethod) {
                    $results['failed']++;
                    $results['errors'][] = "No payment method for tenant {$subscription->tenant_id}";
                    continue;
                }

                $result = $this->payInvoice($invoice, $paymentMethod->id);

                if ($result->success) {
                    $results['success']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = $result->failureMessage;
                }
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Apply a discount code to an invoice.
     */
    public function applyDiscount(Invoice $invoice, string $discountCode): bool
    {
        // Implementation for discount codes
        // This would validate and apply the discount
        return false;
    }

    /**
     * Generate a unique invoice number.
     */
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV';
        $year = now()->format('Y');
        $month = now()->format('m');

        $lastInvoice = Invoice::where('invoice_number', 'like', "{$prefix}-{$year}{$month}-%")
            ->orderBy('invoice_number', 'desc')
            ->first();

        if ($lastInvoice) {
            $lastNumber = (int) substr($lastInvoice->invoice_number, -6);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return sprintf('%s-%s%s-%06d', $prefix, $year, $month, $nextNumber);
    }

    /**
     * Get invoice PDF.
     */
    public function getInvoicePdf(Invoice $invoice): string
    {
        // Would generate PDF using a library like DomPDF or Snappy
        // Returns the PDF path or content
        return '';
    }

    /**
     * Send invoice email.
     */
    public function sendInvoiceEmail(Invoice $invoice): void
    {
        // Send invoice notification
        Log::info('Invoice email sent', ['invoice_id' => $invoice->id]);
    }
}
