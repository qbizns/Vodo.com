<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeValueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->product_id,
            'attribute_id' => $this->attribute_id,

            // Values (all formats)
            'value' => $this->value,
            'value_text' => $this->value_text,
            'value_numeric' => $this->value_numeric ? (float) $this->value_numeric : null,
            'value_date' => $this->value_date?->format('Y-m-d'),
            'value_boolean' => $this->value_boolean,

            // Computed
            'formatted_value' => $this->when(
                $this->relationLoaded('attribute'),
                fn() => $this->getFormattedValue()
            ),
            'typed_value' => $this->when(
                $this->relationLoaded('attribute'),
                fn() => $this->getTypedValue()
            ),

            // Display
            'sort_order' => $this->sort_order,

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            // Note: ProductResource not yet created
            // 'product' => new ProductResource($this->whenLoaded('product')),
            'attribute' => new ProductAttributeResource($this->whenLoaded('attribute')),
        ];
    }
}
