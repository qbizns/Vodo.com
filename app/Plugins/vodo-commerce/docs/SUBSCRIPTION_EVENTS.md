# Subscription Events - Plugin Developer Guide

This guide explains how to extend Vodo Commerce subscription functionality using the Plugin Bus and hook system. All subscription lifecycle events are exposed through the `CommerceEvents` class, allowing your plugin to react to or modify subscription behavior without editing core code.

## Table of Contents

- [Overview](#overview)
- [Action Events](#action-events)
- [Filter Events](#filter-events)
- [Complete Event Reference](#complete-event-reference)
- [Common Use Cases](#common-use-cases)

## Overview

Vodo Commerce uses a WordPress-style hook system that integrates with the platform's Plugin Bus. This enables your plugin to:

- **Listen** for subscription lifecycle events (using `add_action()`)
- **Modify** subscription data and behavior (using `add_filter()`)
- **Extend** functionality without modifying core code

### Basic Hook Usage

```php
// In your plugin's boot() method or service provider

// Listen for an event (action)
add_action(CommerceEvents::SUBSCRIPTION_CREATED, function ($subscription, $plan, $customer) {
    // Your code here - e.g., send notification, update CRM, etc.
});

// Modify data (filter)
add_filter(CommerceEvents::FILTER_PRORATION_AMOUNT, function ($amount, $oldPlan, $newPlan) {
    // Modify and return the proration amount
    return $amount * 1.1; // Add 10% surcharge
}, 10, 3);
```

## Action Events

Action events fire at key points in the subscription lifecycle. They allow your plugin to react to changes but cannot modify the data.

### Subscription Lifecycle Events

#### `SUBSCRIPTION_CREATED`
Fired when a new subscription is created.

**Parameters:**
- `Subscription $subscription` - The newly created subscription
- `SubscriptionPlan $plan` - The plan the customer subscribed to
- `Customer $customer` - The customer who subscribed
- `array $items` - Subscription items included

**Example Use Cases:**
- Send welcome email to customer
- Create customer record in external CRM
- Grant access to premium features
- Log subscription for analytics

```php
use VodoCommerce\Events\CommerceEvents;

add_action(CommerceEvents::SUBSCRIPTION_CREATED, function ($subscription, $plan, $customer, $items) {
    // Send welcome email
    Mail::to($customer->email)->send(new SubscriptionWelcomeEmail($subscription, $plan));

    // Update external CRM
    app(CRMService::class)->createSubscription($customer, $subscription);

    // Grant feature access
    app(FeatureService::class)->grantPlanFeatures($customer, $plan);
}, 10, 4);
```

#### `SUBSCRIPTION_TRIAL_STARTED`
Fired when a subscription trial begins.

**Parameters:**
- `Subscription $subscription` - The subscription in trial
- `Carbon $trialEndsAt` - When the trial ends

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_TRIAL_STARTED, function ($subscription, $trialEndsAt) {
    // Schedule trial ending reminder
    TrialEndingReminderJob::dispatch($subscription)
        ->delay($trialEndsAt->subDays(3));
});
```

#### `SUBSCRIPTION_TRIAL_ENDING`
Fired 3 days before a trial ends (configurable).

**Parameters:**
- `Subscription $subscription` - The subscription with ending trial
- `Carbon $trialEndsAt` - When the trial ends

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_TRIAL_ENDING, function ($subscription, $trialEndsAt) {
    // Send trial ending email
    Mail::to($subscription->customer->email)
        ->send(new TrialEndingEmail($subscription, $trialEndsAt));
});
```

#### `SUBSCRIPTION_TRIAL_ENDED`
Fired when a trial period ends and subscription converts to active.

**Parameters:**
- `Subscription $subscription` - The subscription that ended trial

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_TRIAL_ENDED, function ($subscription) {
    // Log conversion event
    app(AnalyticsService::class)->trackEvent('trial_converted', [
        'subscription_id' => $subscription->id,
        'plan_id' => $subscription->subscription_plan_id,
    ]);
});
```

#### `SUBSCRIPTION_ACTIVATED`
Fired when a subscription becomes active (after trial or immediately).

**Parameters:**
- `Subscription $subscription` - The activated subscription

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_ACTIVATED, function ($subscription) {
    // Provision resources
    app(ResourceProvisioningService::class)->provisionForSubscription($subscription);
});
```

#### `SUBSCRIPTION_UPGRADED`
Fired when a subscription is upgraded to a higher-tier plan.

**Parameters:**
- `Subscription $subscription` - The upgraded subscription
- `SubscriptionPlan $oldPlan` - The previous plan
- `SubscriptionPlan $newPlan` - The new plan

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_UPGRADED, function ($subscription, $oldPlan, $newPlan) {
    // Send upgrade confirmation
    Mail::to($subscription->customer->email)
        ->send(new SubscriptionUpgradedEmail($subscription, $oldPlan, $newPlan));

    // Provision additional resources
    app(ResourceService::class)->upgradeResources($subscription, $oldPlan, $newPlan);
});
```

#### `SUBSCRIPTION_DOWNGRADED`
Fired when a subscription is downgraded to a lower-tier plan.

**Parameters:**
- `Subscription $subscription` - The downgraded subscription
- `SubscriptionPlan $oldPlan` - The previous plan
- `SubscriptionPlan $newPlan` - The new plan

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_DOWNGRADED, function ($subscription, $oldPlan, $newPlan) {
    // Remove features not in new plan
    app(FeatureService::class)->removeUnavailableFeatures($subscription, $oldPlan, $newPlan);

    // Log churn risk
    app(ChurnPredictionService::class)->flagAsRisk($subscription);
});
```

#### `SUBSCRIPTION_PLAN_CHANGED`
Fired when a subscription plan changes (upgrade or downgrade).

**Parameters:**
- `Subscription $subscription` - The subscription with changed plan
- `SubscriptionPlan $oldPlan` - The previous plan
- `SubscriptionPlan $newPlan` - The new plan
- `float $prorationAmount` - The proration amount charged/credited

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_PLAN_CHANGED, function ($subscription, $oldPlan, $newPlan, $prorationAmount) {
    // Log plan change for reporting
    DB::table('subscription_plan_changes')->insert([
        'subscription_id' => $subscription->id,
        'old_plan_id' => $oldPlan->id,
        'new_plan_id' => $newPlan->id,
        'proration_amount' => $prorationAmount,
        'changed_at' => now(),
    ]);
});
```

#### `SUBSCRIPTION_RENEWED`
Fired when a subscription is renewed for the next billing period.

**Parameters:**
- `Subscription $subscription` - The renewed subscription
- `Carbon $newPeriodStart` - Start of new billing period
- `Carbon $newPeriodEnd` - End of new billing period

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_RENEWED, function ($subscription, $newPeriodStart, $newPeriodEnd) {
    // Reset usage limits
    app(UsageLimitService::class)->resetLimits($subscription);

    // Send renewal confirmation
    Mail::to($subscription->customer->email)
        ->send(new SubscriptionRenewedEmail($subscription, $newPeriodEnd));
});
```

#### `SUBSCRIPTION_PAUSED`
Fired when a subscription is paused.

**Parameters:**
- `Subscription $subscription` - The paused subscription
- `?Carbon $resumeAt` - Optional scheduled resume date

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_PAUSED, function ($subscription, $resumeAt) {
    // Suspend access temporarily
    app(AccessControlService::class)->suspendAccess($subscription);

    // Schedule auto-resume if date provided
    if ($resumeAt) {
        ResumeSubscriptionJob::dispatch($subscription)->delay($resumeAt);
    }
});
```

#### `SUBSCRIPTION_RESUMED`
Fired when a paused subscription is resumed.

**Parameters:**
- `Subscription $subscription` - The resumed subscription

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_RESUMED, function ($subscription) {
    // Restore access
    app(AccessControlService::class)->restoreAccess($subscription);

    // Send resume confirmation
    Mail::to($subscription->customer->email)
        ->send(new SubscriptionResumedEmail($subscription));
});
```

#### `SUBSCRIPTION_CANCELLED`
Fired when a subscription is cancelled.

**Parameters:**
- `Subscription $subscription` - The cancelled subscription
- `bool $immediately` - Whether cancelled immediately or at period end
- `?string $reason` - Cancellation reason
- `?string $cancelledByType` - Who cancelled ('customer', 'admin', 'system')
- `?int $cancelledById` - ID of who cancelled

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_CANCELLED, function ($subscription, $immediately, $reason, $cancelledByType, $cancelledById) {
    // Send cancellation survey
    if ($cancelledByType === 'customer') {
        Mail::to($subscription->customer->email)
            ->send(new CancellationSurveyEmail($subscription, $reason));
    }

    // Deprovision resources
    if ($immediately) {
        app(ResourceService::class)->deprovision($subscription);
    } else {
        // Schedule deprovisioning at period end
        DeprovisionResourcesJob::dispatch($subscription)
            ->delay($subscription->current_period_end);
    }

    // Log churn
    app(AnalyticsService::class)->trackChurn($subscription, $reason);
});
```

#### `SUBSCRIPTION_EXPIRED`
Fired when a subscription expires.

**Parameters:**
- `Subscription $subscription` - The expired subscription

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_EXPIRED, function ($subscription) {
    // Remove all access
    app(AccessControlService::class)->revokeAccess($subscription);

    // Send reactivation offer
    Mail::to($subscription->customer->email)
        ->send(new ReactivationOfferEmail($subscription));
});
```

### Payment Events

#### `SUBSCRIPTION_PAYMENT_SUCCEEDED`
Fired when a subscription payment succeeds.

**Parameters:**
- `Subscription $subscription` - The subscription that was paid
- `SubscriptionInvoice $invoice` - The paid invoice
- `Transaction $transaction` - The payment transaction

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_PAYMENT_SUCCEEDED, function ($subscription, $invoice, $transaction) {
    // Send receipt
    Mail::to($subscription->customer->email)
        ->send(new PaymentReceiptEmail($invoice, $transaction));

    // Update accounting system
    app(AccountingService::class)->recordRevenue($transaction);
});
```

#### `SUBSCRIPTION_PAYMENT_FAILED`
Fired when a subscription payment fails.

**Parameters:**
- `Subscription $subscription` - The subscription with failed payment
- `SubscriptionInvoice $invoice` - The unpaid invoice
- `string $error` - The payment error message

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_PAYMENT_FAILED, function ($subscription, $invoice, $error) {
    // Send payment failed notification
    Mail::to($subscription->customer->email)
        ->send(new PaymentFailedEmail($subscription, $invoice, $error));

    // Log for dunning management
    app(DunningService::class)->recordFailedPayment($subscription, $invoice, $error);
});
```

#### `SUBSCRIPTION_PAST_DUE`
Fired when a subscription becomes past due after payment failure.

**Parameters:**
- `Subscription $subscription` - The past due subscription
- `SubscriptionInvoice $invoice` - The unpaid invoice

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_PAST_DUE, function ($subscription, $invoice) {
    // Restrict access but don't revoke completely
    app(AccessControlService::class)->restrictAccess($subscription);

    // Send urgent payment reminder
    Mail::to($subscription->customer->email)
        ->send(new UrgentPaymentReminderEmail($subscription, $invoice));
});
```

### Invoice Events

#### `SUBSCRIPTION_INVOICE_CREATED`
Fired when a subscription invoice is created.

**Parameters:**
- `SubscriptionInvoice $invoice` - The created invoice
- `Subscription $subscription` - The associated subscription

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_INVOICE_CREATED, function ($invoice, $subscription) {
    // Generate PDF
    app(InvoicePDFService::class)->generate($invoice);

    // Send to external accounting system
    app(AccountingService::class)->syncInvoice($invoice);
});
```

#### `SUBSCRIPTION_INVOICE_PAID`
Fired when a subscription invoice is paid.

**Parameters:**
- `SubscriptionInvoice $invoice` - The paid invoice
- `Subscription $subscription` - The associated subscription
- `Transaction $transaction` - The payment transaction

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_INVOICE_PAID, function ($invoice, $subscription, $transaction) {
    // Mark as paid in accounting system
    app(AccountingService::class)->markInvoicePaid($invoice, $transaction);

    // Update business metrics
    app(MetricsService::class)->recordMRR($subscription);
});
```

#### `SUBSCRIPTION_INVOICE_FAILED`
Fired when a subscription invoice payment fails.

**Parameters:**
- `SubscriptionInvoice $invoice` - The failed invoice
- `Subscription $subscription` - The associated subscription
- `string $error` - The error message

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_INVOICE_FAILED, function ($invoice, $subscription, $error) {
    // Notify admin for manual review
    if ($subscription->failed_payment_count >= 3) {
        Mail::to(config('admin.email'))
            ->send(new HighRiskSubscriptionAlert($subscription, $invoice));
    }
});
```

#### `SUBSCRIPTION_INVOICE_RETRY`
Fired before retrying a failed invoice payment.

**Parameters:**
- `SubscriptionInvoice $invoice` - The invoice to retry
- `Subscription $subscription` - The associated subscription

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_INVOICE_RETRY, function ($invoice, $subscription) {
    // Log retry attempt
    Log::info('Retrying invoice payment', [
        'invoice_id' => $invoice->id,
        'attempt' => $invoice->attempt_count + 1,
    ]);
});
```

### Usage & Metered Billing Events

#### `SUBSCRIPTION_USAGE_RECORDED`
Fired when usage is recorded for a metered subscription item.

**Parameters:**
- `Subscription $subscription` - The subscription
- `SubscriptionItem $item` - The metered item
- `SubscriptionUsage $usage` - The recorded usage

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_USAGE_RECORDED, function ($subscription, $item, $usage) {
    // Update real-time usage dashboard
    broadcast(new UsageRecordedEvent($subscription, $item, $usage));

    // Track usage analytics
    app(AnalyticsService::class)->recordUsage($subscription, $item, $usage);
});
```

#### `SUBSCRIPTION_USAGE_THRESHOLD`
Fired when usage reaches a threshold (e.g., 80% of included units).

**Parameters:**
- `Subscription $subscription` - The subscription
- `SubscriptionItem $item` - The metered item
- `int $threshold` - The threshold percentage (e.g., 80)

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_USAGE_THRESHOLD, function ($subscription, $item, $threshold) {
    // Send usage warning email
    Mail::to($subscription->customer->email)
        ->send(new UsageThresholdEmail($subscription, $item, $threshold));
});
```

#### `SUBSCRIPTION_USAGE_OVERAGE`
Fired when usage exceeds included units (overage charges will apply).

**Parameters:**
- `Subscription $subscription` - The subscription
- `SubscriptionItem $item` - The metered item with overage

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_USAGE_OVERAGE, function ($subscription, $item) {
    // Send overage notification
    $overageCharges = $item->calculateOverageCharges();
    Mail::to($subscription->customer->email)
        ->send(new UsageOverageEmail($subscription, $item, $overageCharges));

    // Flag for customer success team
    app(CustomerSuccessService::class)->flagOverage($subscription, $item);
});
```

#### `SUBSCRIPTION_USAGE_RESET`
Fired when usage counters are reset at the start of a new billing period.

**Parameters:**
- `Subscription $subscription` - The subscription
- `Carbon $periodStart` - Start of new billing period

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_USAGE_RESET, function ($subscription, $periodStart) {
    // Reset rate limiters
    app(RateLimiterService::class)->resetLimits($subscription);
});
```

### Subscription Item Events

#### `SUBSCRIPTION_ITEM_ADDED`
Fired when an item is added to a subscription.

**Parameters:**
- `Subscription $subscription` - The subscription
- `SubscriptionItem $item` - The added item

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_ITEM_ADDED, function ($subscription, $item) {
    // Provision new feature
    if ($item->product_id) {
        app(FeatureService::class)->provision($subscription, $item->product);
    }
});
```

#### `SUBSCRIPTION_ITEM_REMOVED`
Fired when an item is removed from a subscription.

**Parameters:**
- `Subscription $subscription` - The subscription
- `SubscriptionItem $item` - The removed item

**Example:**
```php
add_action(CommerceEvents::SUBSCRIPTION_ITEM_REMOVED, function ($subscription, $item) {
    // Deprovision feature
    if ($item->product_id) {
        app(FeatureService::class)->deprovision($subscription, $item->product);
    }
});
```

## Filter Events

Filter events allow your plugin to modify data before it's used. Always return the modified value.

### `FILTER_SUBSCRIPTION_DATA`
Modify subscription data before creation.

**Parameters:**
- `array $data` - Subscription data
- `SubscriptionPlan $plan` - The plan
- `Customer $customer` - The customer

**Example:**
```php
add_filter(CommerceEvents::FILTER_SUBSCRIPTION_DATA, function ($data, $plan, $customer) {
    // Add custom metadata
    $data['meta'] = array_merge($data['meta'] ?? [], [
        'signup_source' => request()->get('source'),
        'referral_code' => session('referral_code'),
    ]);

    return $data;
}, 10, 3);
```

### `FILTER_PRORATION_AMOUNT`
Modify proration amount for plan changes.

**Parameters:**
- `float $amount` - Calculated proration amount
- `SubscriptionPlan $oldPlan` - Previous plan
- `SubscriptionPlan $newPlan` - New plan

**Example:**
```php
add_filter(CommerceEvents::FILTER_PRORATION_AMOUNT, function ($amount, $oldPlan, $newPlan) {
    // Waive proration for loyal customers
    if ($subscription->customer->hasTag('vip')) {
        return 0;
    }

    return $amount;
}, 10, 3);
```

### `FILTER_INVOICE_LINE_ITEMS`
Modify invoice line items before finalization.

**Parameters:**
- `array $lineItems` - Invoice line items
- `Subscription $subscription` - The subscription
- `SubscriptionInvoice $invoice` - The invoice

**Example:**
```php
add_filter(CommerceEvents::FILTER_INVOICE_LINE_ITEMS, function ($lineItems, $subscription, $invoice) {
    // Add loyalty discount
    if ($subscription->customer->loyalty_points >= 1000) {
        $lineItems[] = [
            'description' => 'Loyalty Reward Discount',
            'quantity' => 1,
            'price' => -10.00,
            'total' => -10.00,
        ];
    }

    return $lineItems;
}, 10, 3);
```

### `FILTER_USAGE_CHARGES`
Modify usage charges before adding to invoice.

**Parameters:**
- `float $charges` - Calculated usage charges
- `SubscriptionItem $item` - The metered item
- `Subscription $subscription` - The subscription

**Example:**
```php
add_filter(CommerceEvents::FILTER_USAGE_CHARGES, function ($charges, $item, $subscription) {
    // First month free overage for new customers
    if ($subscription->created_at->isCurrentMonth()) {
        return 0;
    }

    return $charges;
}, 10, 3);
```

### `FILTER_AVAILABLE_PLANS`
Modify which subscription plans are available to a customer.

**Parameters:**
- `Collection $plans` - Available plans
- `Customer $customer` - The customer

**Example:**
```php
add_filter(CommerceEvents::FILTER_AVAILABLE_PLANS, function ($plans, $customer) {
    // Hide enterprise plans for non-business customers
    if (!$customer->is_business) {
        return $plans->filter(fn($plan) => $plan->tier !== 'enterprise');
    }

    return $plans;
}, 10, 2);
```

### `FILTER_RETRY_SCHEDULE`
Modify the retry schedule for failed payments.

**Parameters:**
- `array $schedule` - Retry schedule [1, 3, 7] days
- `SubscriptionInvoice $invoice` - The failed invoice

**Example:**
```php
add_filter(CommerceEvents::FILTER_RETRY_SCHEDULE, function ($schedule, $invoice) {
    // More aggressive retries for high-value subscriptions
    if ($invoice->total >= 1000) {
        return [1, 2, 4, 7]; // Daily for first week
    }

    return $schedule;
}, 10, 2);
```

## Complete Event Reference

### Action Events (27 events)

| Event Constant | Fires When | Parameters |
|---|---|---|
| `SUBSCRIPTION_PLAN_CREATED` | Plan is created | `$plan` |
| `SUBSCRIPTION_PLAN_UPDATED` | Plan is updated | `$plan` |
| `SUBSCRIPTION_PLAN_DELETED` | Plan is deleted | `$plan` |
| `SUBSCRIPTION_CREATED` | Subscription created | `$subscription, $plan, $customer, $items` |
| `SUBSCRIPTION_TRIAL_STARTED` | Trial starts | `$subscription, $trialEndsAt` |
| `SUBSCRIPTION_TRIAL_ENDING` | Trial ending soon | `$subscription, $trialEndsAt` |
| `SUBSCRIPTION_TRIAL_ENDED` | Trial ends | `$subscription` |
| `SUBSCRIPTION_ACTIVATED` | Subscription activates | `$subscription` |
| `SUBSCRIPTION_UPGRADED` | Plan upgraded | `$subscription, $oldPlan, $newPlan` |
| `SUBSCRIPTION_DOWNGRADED` | Plan downgraded | `$subscription, $oldPlan, $newPlan` |
| `SUBSCRIPTION_PLAN_CHANGED` | Plan changed | `$subscription, $oldPlan, $newPlan, $prorationAmount` |
| `SUBSCRIPTION_RENEWED` | Subscription renewed | `$subscription, $newPeriodStart, $newPeriodEnd` |
| `SUBSCRIPTION_PAUSED` | Subscription paused | `$subscription, $resumeAt` |
| `SUBSCRIPTION_RESUMED` | Subscription resumed | `$subscription` |
| `SUBSCRIPTION_CANCELLED` | Subscription cancelled | `$subscription, $immediately, $reason, $cancelledByType, $cancelledById` |
| `SUBSCRIPTION_EXPIRED` | Subscription expired | `$subscription` |
| `SUBSCRIPTION_PAYMENT_SUCCEEDED` | Payment succeeds | `$subscription, $invoice, $transaction` |
| `SUBSCRIPTION_PAYMENT_FAILED` | Payment fails | `$subscription, $invoice, $error` |
| `SUBSCRIPTION_PAST_DUE` | Becomes past due | `$subscription, $invoice` |
| `SUBSCRIPTION_ITEM_ADDED` | Item added | `$subscription, $item` |
| `SUBSCRIPTION_ITEM_REMOVED` | Item removed | `$subscription, $item` |
| `SUBSCRIPTION_INVOICE_CREATED` | Invoice created | `$invoice, $subscription` |
| `SUBSCRIPTION_INVOICE_PAID` | Invoice paid | `$invoice, $subscription, $transaction` |
| `SUBSCRIPTION_INVOICE_FAILED` | Invoice fails | `$invoice, $subscription, $error` |
| `SUBSCRIPTION_INVOICE_RETRY` | Invoice retried | `$invoice, $subscription` |
| `SUBSCRIPTION_USAGE_RECORDED` | Usage recorded | `$subscription, $item, $usage` |
| `SUBSCRIPTION_USAGE_THRESHOLD` | Threshold reached | `$subscription, $item, $threshold` |
| `SUBSCRIPTION_USAGE_OVERAGE` | Overage occurs | `$subscription, $item` |

### Filter Events (8 events)

| Filter Constant | Purpose | Parameters | Return Type |
|---|---|---|---|
| `FILTER_SUBSCRIPTION_DATA` | Modify subscription data | `$data, $plan, $customer` | `array` |
| `FILTER_SUBSCRIPTION_FEATURES` | Filter plan features | `$features, $subscription` | `array` |
| `FILTER_PRORATION_AMOUNT` | Modify proration | `$amount, $oldPlan, $newPlan` | `float` |
| `FILTER_INVOICE_LINE_ITEMS` | Modify line items | `$lineItems, $subscription, $invoice` | `array` |
| `FILTER_INVOICE_TOTALS` | Modify totals | `$totals, $invoice` | `array` |
| `FILTER_USAGE_CHARGES` | Modify usage charges | `$charges, $item, $subscription` | `float` |
| `FILTER_AVAILABLE_PLANS` | Filter available plans | `$plans, $customer` | `Collection` |
| `FILTER_RETRY_SCHEDULE` | Modify retry schedule | `$schedule, $invoice` | `array` |

## Common Use Cases

### 1. Custom Email Notifications

```php
namespace MyPlugin;

use VodoCommerce\Events\CommerceEvents;

class SubscriptionEmailNotifications
{
    public function boot(): void
    {
        // Welcome email on subscription creation
        add_action(CommerceEvents::SUBSCRIPTION_CREATED, [$this, 'sendWelcomeEmail']);

        // Payment receipt
        add_action(CommerceEvents::SUBSCRIPTION_PAYMENT_SUCCEEDED, [$this, 'sendReceipt']);

        // Payment failure notification
        add_action(CommerceEvents::SUBSCRIPTION_PAYMENT_FAILED, [$this, 'sendPaymentFailedEmail']);
    }

    public function sendWelcomeEmail($subscription, $plan, $customer): void
    {
        Mail::to($customer->email)->send(
            new WelcomeEmail($subscription, $plan)
        );
    }

    public function sendReceipt($subscription, $invoice, $transaction): void
    {
        Mail::to($subscription->customer->email)->send(
            new PaymentReceiptEmail($invoice, $transaction)
        );
    }

    public function sendPaymentFailedEmail($subscription, $invoice, $error): void
    {
        Mail::to($subscription->customer->email)->send(
            new PaymentFailedEmail($subscription, $invoice, $error)
        );
    }
}
```

### 2. External CRM Integration

```php
namespace MyPlugin;

use VodoCommerce\Events\CommerceEvents;

class CRMIntegration
{
    public function boot(): void
    {
        add_action(CommerceEvents::SUBSCRIPTION_CREATED, [$this, 'createInCRM']);
        add_action(CommerceEvents::SUBSCRIPTION_CANCELLED, [$this, 'updateCRM']);
        add_action(CommerceEvents::SUBSCRIPTION_PLAN_CHANGED, [$this, 'updateCRM']);
    }

    public function createInCRM($subscription, $plan, $customer): void
    {
        $this->getCRMClient()->subscriptions->create([
            'customer_id' => $customer->external_crm_id,
            'plan' => $plan->name,
            'mrr' => $this->calculateMRR($subscription),
            'status' => $subscription->status,
        ]);
    }

    public function updateCRM($subscription): void
    {
        $this->getCRMClient()->subscriptions->update(
            $subscription->external_crm_id,
            ['status' => $subscription->status]
        );
    }

    protected function calculateMRR($subscription): float
    {
        // Monthly Recurring Revenue calculation
        $amount = (float) $subscription->amount;

        return match($subscription->billing_interval) {
            'monthly' => $amount,
            'yearly' => $amount / 12,
            'weekly' => $amount * 4.33,
            'daily' => $amount * 30,
            default => $amount,
        };
    }
}
```

### 3. Feature Provisioning

```php
namespace MyPlugin;

use VodoCommerce\Events\CommerceEvents;

class FeatureProvisioning
{
    public function boot(): void
    {
        add_action(CommerceEvents::SUBSCRIPTION_CREATED, [$this, 'provisionFeatures']);
        add_action(CommerceEvents::SUBSCRIPTION_UPGRADED, [$this, 'upgradeFeatures']);
        add_action(CommerceEvents::SUBSCRIPTION_DOWNGRADED, [$this, 'downgradeFeatures']);
        add_action(CommerceEvents::SUBSCRIPTION_CANCELLED, [$this, 'deprovisionFeatures']);
    }

    public function provisionFeatures($subscription, $plan, $customer): void
    {
        foreach ($plan->features as $feature) {
            app(FeatureService::class)->enable($customer, $feature);
        }
    }

    public function upgradeFeatures($subscription, $oldPlan, $newPlan): void
    {
        $newFeatures = collect($newPlan->features)
            ->diff($oldPlan->features);

        foreach ($newFeatures as $feature) {
            app(FeatureService::class)->enable($subscription->customer, $feature);
        }
    }

    public function downgradeFeatures($subscription, $oldPlan, $newPlan): void
    {
        $removedFeatures = collect($oldPlan->features)
            ->diff($newPlan->features);

        foreach ($removedFeatures as $feature) {
            app(FeatureService::class)->disable($subscription->customer, $feature);
        }
    }

    public function deprovisionFeatures($subscription, $immediately): void
    {
        if ($immediately) {
            app(FeatureService::class)->disableAll($subscription->customer);
        } else {
            // Schedule for end of period
            DeprovisionFeaturesJob::dispatch($subscription)
                ->delay($subscription->current_period_end);
        }
    }
}
```

### 4. Usage Monitoring & Alerts

```php
namespace MyPlugin;

use VodoCommerce\Events\CommerceEvents;

class UsageMonitoring
{
    public function boot(): void
    {
        add_action(CommerceEvents::SUBSCRIPTION_USAGE_THRESHOLD, [$this, 'sendThresholdWarning']);
        add_action(CommerceEvents::SUBSCRIPTION_USAGE_OVERAGE, [$this, 'sendOverageAlert']);
    }

    public function sendThresholdWarning($subscription, $item, $threshold): void
    {
        $percentUsed = $item->getUsagePercentage();

        Mail::to($subscription->customer->email)->send(
            new UsageThresholdWarningEmail($subscription, $item, $percentUsed)
        );

        // Notify customer success team for high-value accounts
        if ($subscription->amount >= 500) {
            Mail::to(config('team.customer_success_email'))->send(
                new HighValueAccountUsageWarning($subscription, $item, $percentUsed)
            );
        }
    }

    public function sendOverageAlert($subscription, $item): void
    {
        $overageCharges = $item->calculateOverageCharges();

        Mail::to($subscription->customer->email)->send(
            new UsageOverageAlert($subscription, $item, $overageCharges)
        );
    }
}
```

### 5. Custom Pricing Rules

```php
namespace MyPlugin;

use VodoCommerce\Events\CommerceEvents;

class CustomPricingRules
{
    public function boot(): void
    {
        add_filter(CommerceEvents::FILTER_PRORATION_AMOUNT, [$this, 'applyProrationRules'], 10, 3);
        add_filter(CommerceEvents::FILTER_USAGE_CHARGES, [$this, 'applyUsageDiscounts'], 10, 3);
    }

    public function applyProrationRules($amount, $oldPlan, $newPlan): float
    {
        $customer = request()->user()->customer;

        // Waive proration for VIP customers
        if ($customer->hasTag('vip')) {
            return 0;
        }

        // 50% proration discount for loyal customers (2+ years)
        if ($customer->subscriptions()->where('started_at', '<', now()->subYears(2))->exists()) {
            return $amount * 0.5;
        }

        return $amount;
    }

    public function applyUsageDiscounts($charges, $item, $subscription): float
    {
        // Volume discount: 10% off if overage > 1000 units
        if ($item->getOverageUnits() >= 1000) {
            return $charges * 0.9;
        }

        // First overage forgiveness
        $hasHadOverageBefore = SubscriptionUsage::where('subscription_item_id', $item->id)
            ->where('amount', '>', 0)
            ->where('created_at', '<', now()->subMonth())
            ->exists();

        if (!$hasHadOverageBefore) {
            return 0; // First month overage is free
        }

        return $charges;
    }
}
```

### 6. Analytics & Metrics

```php
namespace MyPlugin;

use VodoCommerce\Events\CommerceEvents;

class SubscriptionAnalytics
{
    public function boot(): void
    {
        add_action(CommerceEvents::SUBSCRIPTION_CREATED, [$this, 'trackNewSubscription']);
        add_action(CommerceEvents::SUBSCRIPTION_CANCELLED, [$this, 'trackChurn']);
        add_action(CommerceEvents::SUBSCRIPTION_UPGRADED, [$this, 'trackExpansion']);
        add_action(CommerceEvents::SUBSCRIPTION_PAYMENT_SUCCEEDED, [$this, 'trackRevenue']);
    }

    public function trackNewSubscription($subscription, $plan, $customer): void
    {
        app(AnalyticsService::class)->track('subscription_created', [
            'subscription_id' => $subscription->id,
            'plan_id' => $plan->id,
            'plan_name' => $plan->name,
            'mrr' => $this->calculateMRR($subscription),
            'customer_id' => $customer->id,
            'trial' => $subscription->is_trial,
        ]);
    }

    public function trackChurn($subscription, $immediately, $reason): void
    {
        app(AnalyticsService::class)->track('subscription_churned', [
            'subscription_id' => $subscription->id,
            'plan_id' => $subscription->subscription_plan_id,
            'lost_mrr' => $this->calculateMRR($subscription),
            'reason' => $reason,
            'lifetime_value' => $subscription->invoices()->paid()->sum('total'),
            'days_subscribed' => $subscription->started_at->diffInDays(now()),
        ]);
    }

    public function trackExpansion($subscription, $oldPlan, $newPlan): void
    {
        $oldMRR = $this->calculateMRR($subscription, $oldPlan);
        $newMRR = $this->calculateMRR($subscription, $newPlan);

        app(AnalyticsService::class)->track('subscription_expanded', [
            'subscription_id' => $subscription->id,
            'old_plan_id' => $oldPlan->id,
            'new_plan_id' => $newPlan->id,
            'mrr_expansion' => $newMRR - $oldMRR,
        ]);
    }

    public function trackRevenue($subscription, $invoice, $transaction): void
    {
        app(AnalyticsService::class)->track('subscription_revenue', [
            'subscription_id' => $subscription->id,
            'invoice_id' => $invoice->id,
            'amount' => $transaction->amount,
            'currency' => $transaction->currency,
            'mrr' => $this->calculateMRR($subscription),
        ]);
    }

    protected function calculateMRR($subscription, $plan = null): float
    {
        $plan = $plan ?? $subscription->plan;
        $amount = (float) $plan->price;

        return match($plan->billing_interval) {
            'monthly' => $amount,
            'yearly' => $amount / 12,
            'weekly' => $amount * 4.33,
            'daily' => $amount * 30,
            default => $amount,
        };
    }
}
```

## Best Practices

1. **Priority Management**: Use appropriate priority values (default 10) to control execution order
2. **Type Hints**: Always type-hint parameters for IDE support and clarity
3. **Error Handling**: Wrap event handlers in try-catch to prevent breaking the subscription flow
4. **Performance**: Keep event handlers lightweight; use queued jobs for heavy operations
5. **Documentation**: Document your event hooks for other developers on your team
6. **Testing**: Write tests for your event handlers to ensure they work correctly

## Support

For questions or issues related to subscription events:
- Documentation: `/docs/SUBSCRIPTION_EVENTS.md`
- API Reference: `/docs/api/subscriptions.md`
- Examples: `/docs/examples/subscription-hooks.md`
