<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,
            'subscription_item_id' => $this->subscription_item_id,
            'subscription_invoice_id' => $this->subscription_invoice_id,

            // Usage Details
            'quantity' => $this->quantity,
            'metric' => $this->metric,
            'action' => $this->action,

            // Billing Status
            'is_billed' => $this->is_billed,
            'billed_at' => $this->billed_at?->toIso8601String(),

            // Pricing (if calculated)
            'unit_price' => $this->unit_price ? (float) $this->unit_price : null,
            'amount' => $this->amount ? (float) $this->amount : null,

            // Timestamp
            'recorded_at' => $this->recorded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
            'subscription_item' => new SubscriptionItemResource($this->whenLoaded('subscriptionItem')),
            'invoice' => $this->when(
                $this->relationLoaded('invoice'),
                fn() => new SubscriptionInvoiceResource($this->invoice)
            ),
        ];
    }
}
