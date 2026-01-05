<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponUsageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'discount_code' => $this->discount_code,
            'discount_amount' => $this->discount_amount,
            'order_subtotal' => $this->order_subtotal,
            'applied_to_items' => $this->applied_to_items,
            'discount' => $this->when($this->relationLoaded('discount'), new DiscountResource($this->discount)),
            'customer' => $this->when($this->relationLoaded('customer'), new CustomerResource($this->customer)),
            'order' => $this->when($this->relationLoaded('order'), new OrderResource($this->order)),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
