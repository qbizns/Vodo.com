<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_number' => $this->subscription_number,
            'store_id' => $this->store_id,
            'customer_id' => $this->customer_id,
            'subscription_plan_id' => $this->subscription_plan_id,
            'payment_method_id' => $this->payment_method_id,

            // Status
            'status' => $this->status,
            'is_active' => $this->isActive(),
            'is_trial' => $this->is_trial,
            'is_paused' => $this->isPaused(),
            'is_cancelled' => $this->isCancelled(),

            // Pricing
            'currency' => $this->currency,
            'amount' => (float) $this->amount,
            'billing_interval' => $this->billing_interval,
            'billing_interval_count' => $this->billing_interval_count,

            // Billing Periods
            'current_period_start' => $this->current_period_start?->toIso8601String(),
            'current_period_end' => $this->current_period_end?->toIso8601String(),
            'next_billing_date' => $this->next_billing_date?->toIso8601String(),
            'days_remaining_in_period' => $this->getDaysRemainingInPeriod(),

            // Trial Information
            'trial_ends_at' => $this->trial_ends_at?->toIso8601String(),
            'trial_days_remaining' => $this->is_trial && $this->trial_ends_at
                ? max(0, now()->diffInDays($this->trial_ends_at, false))
                : null,

            // Cancellation
            'cancel_at_period_end' => $this->cancel_at_period_end,
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'cancellation_reason' => $this->cancellation_reason,
            'cancelled_by_type' => $this->cancelled_by_type,
            'cancelled_by_id' => $this->cancelled_by_id,

            // Pause Information
            'paused_at' => $this->paused_at?->toIso8601String(),
            'resume_at' => $this->resume_at?->toIso8601String(),

            // Payment Failure
            'failed_payment_count' => $this->failed_payment_count,
            'last_payment_attempt' => $this->last_payment_attempt?->toIso8601String(),

            // Lifecycle Dates
            'started_at' => $this->started_at?->toIso8601String(),
            'ended_at' => $this->ended_at?->toIso8601String(),

            // Metadata
            'meta' => $this->meta,
            'notes' => $this->notes,

            // Statistics
            'lifetime_value' => $this->when(
                isset($this->lifetime_value),
                fn() => (float) $this->lifetime_value
            ),
            'invoices_count' => $this->when(
                isset($this->invoices_count),
                $this->invoices_count
            ),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'plan' => new SubscriptionPlanResource($this->whenLoaded('plan')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'payment_method' => $this->when(
                $this->relationLoaded('paymentMethod'),
                fn() => new PaymentMethodResource($this->paymentMethod)
            ),
            'items' => SubscriptionItemResource::collection($this->whenLoaded('items')),
            'invoices' => SubscriptionInvoiceResource::collection($this->whenLoaded('invoices')),
            'events' => SubscriptionEventResource::collection($this->whenLoaded('events')),
            'usage_records' => SubscriptionUsageResource::collection($this->whenLoaded('usageRecords')),
        ];
    }
}
