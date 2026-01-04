<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderFulfillmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'tracking_number' => $this->tracking_number,
            'carrier' => $this->carrier,
            'status' => $this->status,
            'tracking_url' => $this->tracking_url,
            'shipped_at' => $this->shipped_at?->toIso8601String(),
            'delivered_at' => $this->delivered_at?->toIso8601String(),
            'estimated_delivery' => $this->estimated_delivery?->toIso8601String(),
            'notes' => $this->notes,
            'meta' => $this->meta,
            'items_count' => $this->getItemsCount(),
            'total_quantity' => $this->getTotalQuantity(),
            'items' => OrderFulfillmentItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
