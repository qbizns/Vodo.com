<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderFulfillmentItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fulfillment_id' => $this->fulfillment_id,
            'order_item_id' => $this->order_item_id,
            'quantity' => $this->quantity,
            'order_item' => new OrderItemResource($this->whenLoaded('orderItem')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
