<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\Customer;
use VodoCommerce\Models\Store;
use VodoCommerce\Models\Subscription;
use VodoCommerce\Models\SubscriptionPlan;
use VodoCommerce\Models\SubscriptionItem;
use VodoCommerce\Models\SubscriptionInvoice;
use VodoCommerce\Models\SubscriptionUsage;
use VodoCommerce\Models\SubscriptionEvent;
use VodoCommerce\Models\PaymentMethod;

class SubscriptionService
{
    /**
     * Create a new subscription for a customer.
     */
    public function createSubscription(
        Store $store,
        Customer $customer,
        SubscriptionPlan $plan,
        ?PaymentMethod $paymentMethod = null,
        array $items = [],
        bool $startTrial = false
    ): Subscription {
        return DB::transaction(function () use ($store, $customer, $plan, $paymentMethod, $items, $startTrial) {
            // Determine if starting with trial
            $isTrial = $startTrial && $plan->has_trial;
            $trialEndsAt = $isTrial ? now()->addDays($plan->trial_days) : null;

            // Calculate billing period
            $periodStart = $isTrial ? $trialEndsAt : now();
            $periodEnd = $this->calculatePeriodEnd($periodStart, $plan->billing_interval, $plan->billing_interval_count);

            // Create subscription
            $subscription = Subscription::create([
                'store_id' => $store->id,
                'customer_id' => $customer->id,
                'subscription_plan_id' => $plan->id,
                'status' => $isTrial ? Subscription::STATUS_TRIAL : Subscription::STATUS_INCOMPLETE,
                'amount' => $plan->price,
                'currency' => $plan->currency,
                'billing_interval' => $plan->billing_interval,
                'billing_interval_count' => $plan->billing_interval_count,
                'is_trial' => $isTrial,
                'trial_ends_at' => $trialEndsAt,
                'current_period_start' => $periodStart,
                'current_period_end' => $periodEnd,
                'next_billing_date' => $periodEnd,
                'payment_method_id' => $paymentMethod?->id,
                'started_at' => now(),
            ]);

            // Add subscription items
            if (empty($items)) {
                // Default: add plan as single item
                $subscription->items()->create([
                    'type' => SubscriptionItem::TYPE_SERVICE,
                    'price' => $plan->price,
                    'quantity' => 1,
                    'total' => $plan->price,
                ]);
            } else {
                foreach ($items as $item) {
                    $subscription->items()->create($item);
                }
            }

            // Log event
            $subscription->logEvent(
                SubscriptionEvent::EVENT_CREATED,
                'Subscription created',
                ['plan_id' => $plan->id, 'trial' => $isTrial]
            );

            if ($isTrial) {
                $subscription->logEvent(
                    SubscriptionEvent::EVENT_TRIAL_STARTED,
                    "Trial started - ends {$trialEndsAt->format('Y-m-d')}"
                );
            }

            return $subscription;
        });
    }

    /**
     * Upgrade or downgrade subscription to a new plan.
     */
    public function changePlan(
        Subscription $subscription,
        SubscriptionPlan $newPlan,
        bool $prorate = true
    ): Subscription {
        return DB::transaction(function () use ($subscription, $newPlan, $prorate) {
            $oldPlan = $subscription->plan;
            $oldAmount = $subscription->amount;

            // Calculate proration if enabled
            $prorationAmount = 0;
            if ($prorate && $oldPlan->allow_proration) {
                $prorationAmount = $newPlan->calculateProrationAmount(
                    $oldPlan,
                    $subscription->current_period_start,
                    $subscription->current_period_end
                );
            }

            // Determine if upgrade or downgrade
            $isUpgrade = (float) $newPlan->price > (float) $oldPlan->price;
            $eventType = $isUpgrade ? SubscriptionEvent::EVENT_UPGRADED : SubscriptionEvent::EVENT_DOWNGRADED;

            // Update subscription
            $subscription->update([
                'subscription_plan_id' => $newPlan->id,
                'amount' => $newPlan->price,
                'billing_interval' => $newPlan->billing_interval,
                'billing_interval_count' => $newPlan->billing_interval_count,
            ]);

            // Create proration invoice if applicable
            if ($prorate && $prorationAmount != 0) {
                $this->createProrationInvoice($subscription, $prorationAmount, $oldPlan, $newPlan);
            }

            // Log event
            $subscription->logEvent(
                $eventType,
                "Plan changed from {$oldPlan->name} to {$newPlan->name}",
                [
                    'old_plan_id' => $oldPlan->id,
                    'new_plan_id' => $newPlan->id,
                    'old_amount' => $oldAmount,
                    'new_amount' => $newPlan->price,
                    'proration_amount' => $prorationAmount,
                ]
            );

            return $subscription->fresh();
        });
    }

    /**
     * Pause a subscription.
     */
    public function pauseSubscription(Subscription $subscription, ?Carbon $resumeAt = null): Subscription
    {
        $subscription->pause($resumeAt);

        return $subscription->fresh();
    }

    /**
     * Resume a paused subscription.
     */
    public function resumeSubscription(Subscription $subscription): Subscription
    {
        $subscription->resume();

        // Recalculate next billing date
        $periodEnd = $this->calculatePeriodEnd(
            now(),
            $subscription->billing_interval,
            $subscription->billing_interval_count
        );

        $subscription->update([
            'current_period_start' => now(),
            'current_period_end' => $periodEnd,
            'next_billing_date' => $periodEnd,
        ]);

        return $subscription->fresh();
    }

    /**
     * Cancel a subscription.
     */
    public function cancelSubscription(
        Subscription $subscription,
        bool $immediately = false,
        ?string $reason = null,
        ?string $cancelledByType = 'customer',
        ?int $cancelledById = null
    ): Subscription {
        $subscription->cancel($immediately, $reason, $cancelledByType, $cancelledById);

        return $subscription->fresh();
    }

    /**
     * Renew a subscription (advance to next billing period).
     */
    public function renewSubscription(Subscription $subscription): Subscription
    {
        return DB::transaction(function () use ($subscription) {
            // Calculate new billing period
            $newPeriodStart = $subscription->current_period_end->copy();
            $newPeriodEnd = $this->calculatePeriodEnd(
                $newPeriodStart,
                $subscription->billing_interval,
                $subscription->billing_interval_count
            );

            // Reset trial status if was in trial
            if ($subscription->isTrial() && $subscription->trial_ends_at->isPast()) {
                $subscription->update([
                    'is_trial' => false,
                    'status' => Subscription::STATUS_ACTIVE,
                ]);

                $subscription->logEvent(
                    SubscriptionEvent::EVENT_TRIAL_ENDED,
                    'Trial period ended'
                );
            }

            // Update billing period
            $subscription->update([
                'current_period_start' => $newPeriodStart,
                'current_period_end' => $newPeriodEnd,
                'next_billing_date' => $newPeriodEnd,
            ]);

            // Reset usage counters for metered items
            foreach ($subscription->items()->metered()->get() as $item) {
                $item->resetUsage();
            }

            // Log renewal event
            $subscription->logEvent(
                SubscriptionEvent::EVENT_RENEWED,
                "Subscription renewed for period {$newPeriodStart->format('Y-m-d')} to {$newPeriodEnd->format('Y-m-d')}"
            );

            return $subscription->fresh();
        });
    }

    /**
     * Add an item to a subscription.
     */
    public function addItem(
        Subscription $subscription,
        string $type,
        float $price,
        int $quantity = 1,
        ?int $productId = null,
        ?int $variantId = null,
        array $meteredConfig = []
    ): SubscriptionItem {
        $itemData = [
            'type' => $type,
            'price' => $price,
            'quantity' => $quantity,
            'total' => $price * $quantity,
            'product_id' => $productId,
            'product_variant_id' => $variantId,
        ];

        // Add metered billing configuration if provided
        if (!empty($meteredConfig)) {
            $itemData['is_metered'] = true;
            $itemData['price_per_unit'] = $meteredConfig['price_per_unit'] ?? 0;
            $itemData['included_units'] = $meteredConfig['included_units'] ?? null;
        }

        return $subscription->items()->create($itemData);
    }

    /**
     * Remove an item from a subscription.
     */
    public function removeItem(SubscriptionItem $item): bool
    {
        return $item->delete();
    }

    /**
     * Record usage for a metered subscription item.
     */
    public function recordUsage(
        SubscriptionItem $item,
        int $quantity,
        ?string $metric = null,
        ?string $action = null
    ): SubscriptionUsage {
        if (!$item->is_metered) {
            throw new \RuntimeException('Cannot record usage for non-metered item');
        }

        return $item->recordUsage($quantity, $metric, $action);
    }

    /**
     * Calculate period end date based on interval.
     */
    protected function calculatePeriodEnd(Carbon $start, string $interval, int $count): Carbon
    {
        return match ($interval) {
            SubscriptionPlan::INTERVAL_DAILY => $start->copy()->addDays($count),
            SubscriptionPlan::INTERVAL_WEEKLY => $start->copy()->addWeeks($count),
            SubscriptionPlan::INTERVAL_MONTHLY => $start->copy()->addMonths($count),
            SubscriptionPlan::INTERVAL_YEARLY => $start->copy()->addYears($count),
            default => $start->copy()->addMonths($count),
        };
    }

    /**
     * Create a proration invoice.
     */
    protected function createProrationInvoice(
        Subscription $subscription,
        float $prorationAmount,
        SubscriptionPlan $oldPlan,
        SubscriptionPlan $newPlan
    ): SubscriptionInvoice {
        $invoice = $subscription->invoices()->create([
            'store_id' => $subscription->store_id,
            'customer_id' => $subscription->customer_id,
            'status' => SubscriptionInvoice::STATUS_PENDING,
            'period_start' => $subscription->current_period_start,
            'period_end' => $subscription->current_period_end,
            'subtotal' => $prorationAmount,
            'total' => $prorationAmount,
            'amount_due' => $prorationAmount,
            'currency' => $subscription->currency,
            'is_proration' => true,
            'proration_amount' => $prorationAmount,
            'due_date' => now(),
            'line_items' => [
                [
                    'description' => "Proration: {$oldPlan->name} → {$newPlan->name}",
                    'amount' => $prorationAmount,
                ],
            ],
        ]);

        return $invoice;
    }

    /**
     * Get subscriptions due for billing.
     */
    public function getSubscriptionsDueForBilling(): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::dueForBilling()->get();
    }

    /**
     * Get subscriptions with ending trials.
     */
    public function getSubscriptionsWithEndingTrials(int $days = 3): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::endingTrial($days)->get();
    }

    /**
     * Get subscriptions scheduled for cancellation.
     */
    public function getSubscriptionsScheduledForCancellation(): \Illuminate\Database\Eloquent\Collection
    {
        return Subscription::scheduledForCancellation()->get();
    }

    /**
     * Process subscriptions scheduled for cancellation.
     */
    public function processScheduledCancellations(): int
    {
        $subscriptions = $this->getSubscriptionsScheduledForCancellation();
        $count = 0;

        foreach ($subscriptions as $subscription) {
            $subscription->update([
                'status' => Subscription::STATUS_CANCELLED,
                'ended_at' => now(),
            ]);

            $subscription->logEvent(
                SubscriptionEvent::EVENT_CANCELLED,
                'Subscription cancelled at end of period'
            );

            $count++;
        }

        return $count;
    }
}
