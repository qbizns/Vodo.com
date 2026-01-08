<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'location_id' => $this->resource->location_id,
            'location' => new InventoryLocationResource($this->whenLoaded('location')),
            'product_id' => $this->resource->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant_id' => $this->resource->variant_id,
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'quantity' => $this->resource->quantity,
            'reserved_quantity' => $this->resource->reserved_quantity,
            'available_quantity' => $this->resource->available_quantity,
            'reorder_point' => $this->resource->reorder_point,
            'reorder_quantity' => $this->resource->reorder_quantity,
            'bin_location' => $this->resource->bin_location,
            'unit_cost' => $this->resource->unit_cost,
            'is_low_stock' => $this->resource->isLowStock(),
            'needs_reorder' => $this->resource->needsReorder(),
            'reorder_suggestion' => $this->resource->getReorderSuggestion(),
            'last_counted_at' => $this->resource->last_counted_at,
            'meta' => $this->resource->meta,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
