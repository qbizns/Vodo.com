<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockMovementResource extends JsonResource
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
            'type' => $this->resource->type,
            'quantity' => $this->resource->quantity,
            'display_quantity' => $this->resource->getDisplayQuantity(),
            'quantity_before' => $this->resource->quantity_before,
            'quantity_after' => $this->resource->quantity_after,
            'reference_type' => $this->resource->reference_type,
            'reference_id' => $this->resource->reference_id,
            'reason' => $this->resource->reason,
            'performed_by_type' => $this->resource->performed_by_type,
            'performed_by_id' => $this->resource->performed_by_id,
            'unit_cost' => $this->resource->unit_cost,
            'is_inbound' => $this->resource->isInbound(),
            'is_outbound' => $this->resource->isOutbound(),
            'meta' => $this->resource->meta,
            'created_at' => $this->resource->created_at,
        ];
    }
}
