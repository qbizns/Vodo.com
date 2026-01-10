<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,

            // Type & Description
            'type' => $this->type,
            'description' => $this->description,

            // Pricing
            'price' => (float) $this->price,
            'quantity' => $this->quantity,
            'total' => (float) $this->total,

            // Metered Billing
            'is_metered' => $this->is_metered,
            'price_per_unit' => $this->price_per_unit ? (float) $this->price_per_unit : null,
            'included_units' => $this->included_units,
            'current_usage' => $this->current_usage,
            'usage_percentage' => $this->is_metered && $this->included_units
                ? $this->getUsagePercentage()
                : null,

            // Overage Information
            'has_overage' => $this->is_metered && $this->included_units && $this->current_usage > $this->included_units,
            'overage_units' => $this->when(
                $this->is_metered && $this->included_units,
                fn() => $this->getOverageUnits()
            ),
            'overage_charges' => $this->when(
                $this->is_metered && $this->included_units,
                fn() => $this->calculateOverageCharges()
            ),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            // TODO: Uncomment when ProductResource and ProductVariantResource are created
            // 'product' => $this->when(
            //     $this->relationLoaded('product'),
            //     fn() => new ProductResource($this->product)
            // ),
            // 'variant' => $this->when(
            //     $this->relationLoaded('variant'),
            //     fn() => new ProductVariantResource($this->variant)
            // ),
            'usage_records' => SubscriptionUsageResource::collection($this->whenLoaded('usageRecords')),
        ];
    }
}
