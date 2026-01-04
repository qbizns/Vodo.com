<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderRefundResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'refund_number' => $this->refund_number,
            'amount' => (float) $this->amount,
            'reason' => $this->reason,
            'status' => $this->status,
            'refund_method' => $this->refund_method,
            'processed_at' => $this->processed_at?->toIso8601String(),
            'notes' => $this->notes,
            'items_count' => $this->getItemsCount(),
            'items' => OrderRefundItemResource::collection($this->whenLoaded('items')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
