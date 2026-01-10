<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionPlanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,

            // Pricing
            'price' => (float) $this->price,
            'currency' => $this->currency,
            'billing_interval' => $this->billing_interval,
            'billing_interval_count' => $this->billing_interval_count,
            'billing_cycle_description' => $this->getBillingCycleDescription(),
            'price_per_day' => $this->getPricePerDay(),

            // Trial Configuration
            'trial_days' => $this->trial_days,
            'has_trial' => $this->trial_days > 0,

            // Metered Billing
            'is_metered' => $this->is_metered,
            'metered_units' => $this->metered_units,
            'price_per_unit' => $this->price_per_unit ? (float) $this->price_per_unit : null,

            // Plan Configuration
            'allow_proration' => $this->allow_proration,
            'is_public' => $this->is_public,
            'is_active' => $this->is_active,
            'sort_order' => $this->sort_order,

            // Features & Limits
            'features' => $this->features,
            'limits' => $this->limits,

            // Metadata
            'meta' => $this->meta,

            // Statistics
            'active_subscriptions_count' => $this->when(
                isset($this->active_subscriptions_count),
                $this->active_subscriptions_count
            ),

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'subscriptions' => SubscriptionResource::collection($this->whenLoaded('subscriptions')),
        ];
    }
}
