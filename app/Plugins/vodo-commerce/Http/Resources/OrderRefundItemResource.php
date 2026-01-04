<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRefundItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'refund_id' => $this->refund_id,
            'order_item_id' => $this->order_item_id,
            'quantity' => $this->quantity,
            'amount' => (float) $this->amount,
            'order_item' => new OrderItemResource($this->whenLoaded('orderItem')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
