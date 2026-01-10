<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductAttributeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'display_name' => $this->getDisplayName(),

            // Type
            'type' => $this->type,
            'is_select_type' => $this->isSelectType(),
            'is_numeric_type' => $this->isNumericType(),
            'is_boolean_type' => $this->isBooleanType(),
            'is_date_type' => $this->isDateType(),

            // Display Configuration
            'is_visible' => $this->is_visible,
            'is_filterable' => $this->is_filterable,
            'is_comparable' => $this->is_comparable,
            'is_required' => $this->is_required,

            // Validation
            'validation_rules' => $this->validation_rules,
            'validation_rules_array' => $this->getValidationRules(),
            'unit' => $this->unit,

            // Display
            'sort_order' => $this->sort_order,
            'icon' => $this->icon,
            'group' => $this->group,

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            'values' => ProductAttributeValueResource::collection($this->whenLoaded('values')),
            'values_count' => $this->when(
                isset($this->values_count),
                $this->values_count
            ),
        ];
    }
}
