<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBundleResource extends JsonResource
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
            'pricing_type' => $this->pricing_type,
            'fixed_price' => $this->fixed_price ? (float) $this->fixed_price : null,
            'discount_amount' => (float) $this->discount_amount,
            'discount_type' => $this->discount_type,
            'calculated_price' => $this->calculatePrice(),
            'savings' => $this->getSavings(),
            'savings_percentage' => $this->getSavingsPercentage(),

            // Configuration
            'allow_partial_purchase' => $this->allow_partial_purchase,
            'is_active' => $this->is_active,
            'min_items' => $this->min_items,
            'max_items' => $this->max_items,

            // Inventory
            'track_inventory' => $this->track_inventory,
            'stock_quantity' => $this->stock_quantity,
            'is_in_stock' => $this->isInStock(),
            'has_items_in_stock' => $this->when(
                $this->relationLoaded('items'),
                fn() => $this->hasItemsInStock()
            ),

            // Display
            'image_url' => $this->image_url,
            'sort_order' => $this->sort_order,

            // SEO
            'meta_title' => $this->meta_title,
            'meta_description' => $this->meta_description,

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            // Relationships
            'items' => ProductBundleItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->when(
                isset($this->items_count),
                $this->items_count
            ),
        ];
    }
}
