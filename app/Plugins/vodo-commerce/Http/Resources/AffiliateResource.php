<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AffiliateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => $this->when($this->relationLoaded('customer'), new CustomerResource($this->customer)),
            'code' => $this->code,
            'commission_rate' => $this->commission_rate,
            'commission_type' => $this->commission_type,
            'total_earnings' => $this->total_earnings,
            'pending_balance' => $this->pending_balance,
            'paid_balance' => $this->paid_balance,
            'total_clicks' => $this->total_clicks,
            'total_conversions' => $this->total_conversions,
            'conversion_rate' => $this->when(isset($this->total_clicks), $this->getConversionRate()),
            'is_active' => $this->is_active,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
