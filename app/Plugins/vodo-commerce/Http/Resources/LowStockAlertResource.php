<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LowStockAlertResource extends JsonResource
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
            'threshold' => $this->resource->threshold,
            'current_quantity' => $this->resource->current_quantity,
            'severity' => $this->resource->getSeverity(),
            'is_resolved' => $this->resource->is_resolved,
            'resolved_at' => $this->resource->resolved_at,
            'resolved_by_type' => $this->resource->resolved_by_type,
            'resolved_by_id' => $this->resource->resolved_by_id,
            'resolution_notes' => $this->resource->resolution_notes,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
