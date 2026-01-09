<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'transfer_id' => $this->resource->transfer_id,
            'product_id' => $this->resource->product_id,
            'product' => new ProductResource($this->whenLoaded('product')),
            'variant_id' => $this->resource->variant_id,
            'variant' => new ProductVariantResource($this->whenLoaded('variant')),
            'quantity_requested' => $this->resource->quantity_requested,
            'quantity_shipped' => $this->resource->quantity_shipped,
            'quantity_received' => $this->resource->quantity_received,
            'has_discrepancy' => $this->resource->hasDiscrepancy(),
            'discrepancy' => $this->resource->getDiscrepancy(),
            'is_fully_received' => $this->resource->isFullyReceived(),
            'is_partially_received' => $this->resource->isPartiallyReceived(),
            'notes' => $this->resource->notes,
            'meta' => $this->resource->meta,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
