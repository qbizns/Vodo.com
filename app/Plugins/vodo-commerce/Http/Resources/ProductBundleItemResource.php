<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBundleItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'bundle_id' => $this->bundle_id,
            'product_id' => $this->product_id,
            'product_variant_id' => $this->product_variant_id,

            // Configuration
            'quantity' => $this->quantity,
            'is_required' => $this->is_required,
            'is_default' => $this->is_default,

            // Pricing
            'price_override' => $this->price_override ? (float) $this->price_override : null,
            'discount_amount' => $this->discount_amount ? (float) $this->discount_amount : null,
            'discount_type' => $this->discount_type,
            'effective_price' => $this->getEffectivePrice(),
            'total_price' => $this->getTotalPrice(),

            // Display
            'sort_order' => $this->sort_order,
            'description' => $this->description,

            // Stock
            'is_in_stock' => $this->when(
                $this->relationLoaded('product') || $this->relationLoaded('variant'),
                fn() => $this->isInStock()
            ),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'bundle' => new ProductBundleResource($this->whenLoaded('bundle')),
            // Note: ProductResource not yet created, will be added in future phase
            // 'product' => new ProductResource($this->whenLoaded('product')),
            // 'variant' => new ProductVariantResource($this->whenLoaded('variant')),
        ];
    }
}
