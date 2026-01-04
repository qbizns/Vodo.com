<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TaxRateResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tax_zone_id' => $this->tax_zone_id,
            'name' => $this->name,
            'code' => $this->code,
            'rate' => (float) $this->rate,
            'rate_display' => $this->getRatePercentage(),
            'type' => $this->type,
            'compound' => $this->compound,
            'shipping_taxable' => $this->shipping_taxable,
            'priority' => $this->priority,
            'is_active' => $this->is_active,
            'category_id' => $this->category_id,
            'tax_zone' => new TaxZoneResource($this->whenLoaded('taxZone')),
            'category' => new CategoryResource($this->whenLoaded('category')),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
