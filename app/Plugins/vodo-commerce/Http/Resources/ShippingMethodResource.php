<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'calculation_type' => $this->calculation_type,
            'base_cost' => (float) $this->base_cost,
            'min_delivery_days' => $this->min_delivery_days,
            'max_delivery_days' => $this->max_delivery_days,
            'delivery_estimate' => $this->getDeliveryEstimate(),
            'is_active' => $this->is_active,
            'requires_address' => $this->requires_address,
            'min_order_amount' => $this->min_order_amount ? (float) $this->min_order_amount : null,
            'max_order_amount' => $this->max_order_amount ? (float) $this->max_order_amount : null,
            'settings' => $this->settings,
            'rates' => ShippingRateResource::collection($this->whenLoaded('rates')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
