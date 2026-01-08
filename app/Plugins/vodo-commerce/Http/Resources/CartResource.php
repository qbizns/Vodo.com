<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \VodoCommerce\Models\Cart $resource
 */
class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'store_id' => $this->resource->store_id,
            'customer_id' => $this->resource->customer_id,
            'session_id' => $this->resource->session_id,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'item_count' => $this->resource->getItemCount(),
            'is_empty' => $this->resource->isEmpty(),
            'currency' => $this->resource->currency,
            'subtotal' => $this->resource->subtotal,
            'discount_total' => $this->resource->discount_total,
            'discount_codes' => $this->resource->discount_codes ?? [],
            'shipping_total' => $this->resource->shipping_total,
            'shipping_method' => $this->resource->shipping_method,
            'tax_total' => $this->resource->tax_total,
            'total' => $this->resource->total,
            'billing_address' => $this->resource->billing_address,
            'shipping_address' => $this->resource->shipping_address,
            'notes' => $this->resource->notes,
            'meta' => $this->resource->meta,
            'is_expired' => $this->resource->isExpired(),
            'expires_at' => $this->resource->expires_at?->toIso8601String(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
