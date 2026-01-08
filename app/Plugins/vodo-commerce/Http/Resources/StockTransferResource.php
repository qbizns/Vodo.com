<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StockTransferResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'transfer_number' => $this->resource->transfer_number,
            'from_location_id' => $this->resource->from_location_id,
            'from_location' => new InventoryLocationResource($this->whenLoaded('fromLocation')),
            'to_location_id' => $this->resource->to_location_id,
            'to_location' => new InventoryLocationResource($this->whenLoaded('toLocation')),
            'status' => $this->resource->status,
            'items' => StockTransferItemResource::collection($this->whenLoaded('items')),
            'notes' => $this->resource->notes,
            'requested_by_type' => $this->resource->requested_by_type,
            'requested_by_id' => $this->resource->requested_by_id,
            'requested_at' => $this->resource->requested_at,
            'approved_by_type' => $this->resource->approved_by_type,
            'approved_by_id' => $this->resource->approved_by_id,
            'approved_at' => $this->resource->approved_at,
            'shipped_at' => $this->resource->shipped_at,
            'received_at' => $this->resource->received_at,
            'cancelled_at' => $this->resource->cancelled_at,
            'cancellation_reason' => $this->resource->cancellation_reason,
            'tracking_number' => $this->resource->tracking_number,
            'carrier' => $this->resource->carrier,
            'can_be_shipped' => $this->resource->canBeShipped(),
            'can_be_received' => $this->resource->canBeReceived(),
            'can_be_cancelled' => $this->resource->canBeCancelled(),
            'meta' => $this->resource->meta,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
