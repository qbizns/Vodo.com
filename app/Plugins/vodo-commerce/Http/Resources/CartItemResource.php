<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property \VodoCommerce\Models\CartItem $resource
 */
class CartItemResource extends JsonResource
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
            'cart_id' => $this->resource->cart_id,
            'product_id' => $this->resource->product_id,
            'variant_id' => $this->resource->variant_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'name' => $this->resource->getName(),
            'sku' => $this->resource->getSku(),
            'image' => $this->resource->getImage(),
            'quantity' => $this->resource->quantity,
            'unit_price' => $this->resource->unit_price,
            'line_total' => $this->resource->getLineTotal(),
            'options' => $this->resource->options,
            'meta' => $this->resource->meta,
            'in_stock' => $this->resource->isInStock(),
            'available_quantity' => $this->resource->getAvailableQuantity(),
            'created_at' => $this->resource->created_at?->toIso8601String(),
            'updated_at' => $this->resource->updated_at?->toIso8601String(),
        ];
    }
}
