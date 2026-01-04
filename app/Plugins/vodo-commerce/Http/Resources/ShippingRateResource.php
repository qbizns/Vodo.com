<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ShippingRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'shipping_method_id' => $this->shipping_method_id,
            'shipping_zone_id' => $this->shipping_zone_id,
            'rate' => (float) $this->rate,
            'per_item_rate' => (float) $this->per_item_rate,
            'weight_rate' => (float) $this->weight_rate,
            'min_weight' => $this->min_weight ? (float) $this->min_weight : null,
            'max_weight' => $this->max_weight ? (float) $this->max_weight : null,
            'min_price' => $this->min_price ? (float) $this->min_price : null,
            'max_price' => $this->max_price ? (float) $this->max_price : null,
            'is_free_shipping' => $this->is_free_shipping,
            'free_shipping_threshold' => $this->free_shipping_threshold ? (float) $this->free_shipping_threshold : null,
            'shipping_method' => new ShippingMethodResource($this->whenLoaded('shippingMethod')),
            'shipping_zone' => new ShippingZoneResource($this->whenLoaded('shippingZone')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
