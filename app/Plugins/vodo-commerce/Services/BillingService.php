<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Events\CommerceEvents;
use VodoCommerce\Models\Subscription;
use VodoCommerce\Models\SubscriptionInvoice;
use VodoCommerce\Models\SubscriptionEvent;
use VodoCommerce\Models\Transaction;

class BillingService
{
    public function __construct(
        protected SubscriptionService $subscriptionService,
        protected TransactionService $transactionService
    ) {
    }

    /**
     * Process all subscriptions due for billing.
     */
    public function processRecurringBilling(): array
    {
        $subscriptions = $this->subscriptionService->getSubscriptionsDueForBilling();

        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($subscriptions as $subscription) {
            try {
                $this->billSubscription($subscription);
                $results['processed']++;
                $results['succeeded']++;
            } catch (\Exception $e) {
                $results['processed']++;
                $results['failed']++;
                $results['errors'][] = [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Subscription billing failed', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Bill a single subscription.
     */
    public function billSubscription(Subscription $subscription): SubscriptionInvoice
    {
        return DB::transaction(function () use ($subscription) {
            // Create invoice
            $invoice = $this->createInvoiceForSubscription($subscription);

            // Attempt payment
            if ($subscription->payment_method_id) {
                try {
                    $this->chargeInvoice($invoice);
                } catch (\Exception $e) {
                    // Payment failed
                    $this->handleFailedPayment($invoice, $subscription, $e->getMessage());
                    throw $e;
                }
            }

            // Renew subscription for next period
            $this->subscriptionService->renewSubscription($subscription);

            return $invoice;
        });
    }

    /**
     * Create an invoice for a subscription's current billing period.
     */
    public function createInvoiceForSubscription(Subscription $subscription): SubscriptionInvoice
    {
        $subtotal = (float) $subscription->amount;
        $usageCharges = 0;
        $lineItems = [];

        // Add base subscription items
        foreach ($subscription->items as $item) {
            $lineItems[] = [
                'description' => $this->getItemDescription($item),
                'quantity' => $item->quantity,
                'price' => $item->price,
                'total' => $item->total,
            ];
        }

        // Calculate usage charges for metered items
        $usageDetails = [];
        foreach ($subscription->items()->metered()->get() as $item) {
            $overageCharges = $item->calculateOverageCharges();

            if ($overageCharges > 0) {
                $usageCharges += $overageCharges;
                $usageDetails[] = [
                    'item_id' => $item->id,
                    'metric' => $item->type,
                    'usage' => $item->current_usage,
                    'included' => $item->included_units,
                    'overage' => $item->getOverageUnits(),
                    'charges' => $overageCharges,
                ];

                $lineItems[] = [
                    'description' => "Usage charges: {$item->type}",
                    'quantity' => $item->getOverageUnits(),
                    'price' => $item->price_per_unit,
                    'total' => $overageCharges,
                ];
            }
        }

        // Calculate tax (simplified - should integrate with TaxService)
        $taxTotal = 0; // TODO: Integrate with TaxService

        $total = $subtotal + $usageCharges + $taxTotal;

        // Create invoice
        $invoice = $subscription->invoices()->create([
            'store_id' => $subscription->store_id,
            'customer_id' => $subscription->customer_id,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'subtotal' => $subtotal,
            'usage_charges' => $usageCharges,
            'usage_details' => !empty($usageDetails) ? $usageDetails : null,
            'tax_total' => $taxTotal,
            'total' => $total,
            'amount_due' => $total,
            'currency' => $subscription->currency,
            'due_date' => now(),
            'line_items' => $lineItems,
        ]);

        // Mark usage records as billed
        foreach ($subscription->usageRecords()->unbilled()->get() as $usage) {
            $usage->markAsBilled($invoice->id);
        }

        // Fire event for plugin extensibility
        do_action(CommerceEvents::SUBSCRIPTION_INVOICE_CREATED, $invoice, $subscription);

        return $invoice;
    }

    /**
     * Charge an invoice using the subscription's payment method.
     */
    public function chargeInvoice(SubscriptionInvoice $invoice): Transaction
    {
        $subscription = $invoice->subscription;

        if (!$subscription->payment_method_id) {
            throw new \RuntimeException('Subscription has no payment method');
        }

        // Create transaction
        $transaction = $this->transactionService->createTransaction(
            $subscription->store_id,
            $subscription->customer_id,
            null, // No order - this is a subscription payment
            $subscription->payment_method_id,
            $invoice->total,
            $invoice->currency,
            Transaction::TYPE_SUBSCRIPTION,
            [
                'subscription_id' => $subscription->id,
                'invoice_id' => $invoice->id,
                'description' => "Subscription payment: {$subscription->subscription_number}",
            ]
        );

        // Process payment (integrate with payment gateway)
        try {
            // TODO: Integrate with actual payment gateway
            // For now, we'll mark as succeeded (this should call payment gateway)
            $transaction->markAsCompleted('mock_gateway_ref');

            // Mark invoice as paid
            $invoice->markAsPaid($transaction->id);

            // Log successful payment
            $subscription->logEvent(
                SubscriptionEvent::EVENT_PAYMENT_SUCCEEDED,
                "Payment succeeded for invoice {$invoice->invoice_number}",
                ['invoice_id' => $invoice->id, 'amount' => $invoice->total]
            );

            // Reset failed payment counter
            if ($subscription->failed_payment_count > 0) {
                $subscription->update(['failed_payment_count' => 0]);
            }

            // Fire events for plugin extensibility
            do_action(CommerceEvents::SUBSCRIPTION_INVOICE_PAID, $invoice, $subscription, $transaction);
            do_action(CommerceEvents::SUBSCRIPTION_PAYMENT_SUCCEEDED, $subscription, $invoice, $transaction);

        } catch (\Exception $e) {
            $transaction->markAsFailed($e->getMessage());
            throw $e;
        }

        return $transaction;
    }

    /**
     * Handle a failed payment.
     */
    protected function handleFailedPayment(
        SubscriptionInvoice $invoice,
        Subscription $subscription,
        string $error
    ): void {
        // Calculate next retry date
        $nextRetryAt = $invoice->calculateNextRetryDate();

        // Mark invoice as failed
        $invoice->markAsFailed($error, $nextRetryAt);

        // Update subscription
        $subscription->markAsPastDue();

        // Fire events for plugin extensibility
        do_action(CommerceEvents::SUBSCRIPTION_INVOICE_FAILED, $invoice, $subscription, $error);
        do_action(CommerceEvents::SUBSCRIPTION_PAYMENT_FAILED, $subscription, $invoice, $error);
        do_action(CommerceEvents::SUBSCRIPTION_PAST_DUE, $subscription, $invoice);

        // Check if we should cancel after too many failures
        if ($subscription->failed_payment_count >= 4) {
            $subscription->cancel(
                true,
                'Cancelled due to repeated payment failures',
                'system'
            );
        }
    }

    /**
     * Retry failed invoice payments.
     */
    public function retryFailedPayments(): array
    {
        $invoices = SubscriptionInvoice::dueForRetry()->get();

        $results = [
            'processed' => 0,
            'succeeded' => 0,
            'failed' => 0,
        ];

        foreach ($invoices as $invoice) {
            // Fire event before retry for plugin extensibility
            do_action(CommerceEvents::SUBSCRIPTION_INVOICE_RETRY, $invoice, $invoice->subscription);

            try {
                $this->chargeInvoice($invoice);
                $results['processed']++;
                $results['succeeded']++;
            } catch (\Exception $e) {
                $results['processed']++;
                $results['failed']++;

                Log::warning('Invoice retry failed', [
                    'invoice_id' => $invoice->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * Send trial ending notifications.
     */
    public function sendTrialEndingNotifications(int $days = 3): int
    {
        $subscriptions = $this->subscriptionService->getSubscriptionsWithEndingTrials($days);
        $count = 0;

        foreach ($subscriptions as $subscription) {
            // Fire event for plugin extensibility
            do_action(CommerceEvents::SUBSCRIPTION_TRIAL_ENDING, $subscription, $subscription->trial_ends_at);

            // TODO: Send email notification to customer
            // This should integrate with email service

            Log::info('Trial ending notification sent', [
                'subscription_id' => $subscription->id,
                'customer_id' => $subscription->customer_id,
                'trial_ends_at' => $subscription->trial_ends_at,
            ]);

            $count++;
        }

        return $count;
    }

    /**
     * Process scheduled cancellations.
     */
    public function processScheduledCancellations(): int
    {
        return $this->subscriptionService->processScheduledCancellations();
    }

    /**
     * Get item description for invoice line item.
     */
    protected function getItemDescription(SubscriptionItem $item): string
    {
        if ($item->product_id) {
            $product = $item->product;
            $description = $product->name;

            if ($item->product_variant_id) {
                $variant = $item->variant;
                $description .= " ({$variant->name})";
            }

            return $description;
        }

        return ucfirst($item->type);
    }

    /**
     * Generate invoice PDF (placeholder for future implementation).
     */
    public function generateInvoicePdf(SubscriptionInvoice $invoice): ?string
    {
        // TODO: Implement PDF generation
        // This should use a PDF library like DomPDF or mPDF

        return null;
    }
}
