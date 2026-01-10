<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductBadgeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'product_id' => $this->product_id,

            // Badge Details
            'label' => $this->label,
            'slug' => $this->slug,
            'type' => $this->type,

            // Visual Configuration
            'color' => $this->color,
            'background_color' => $this->background_color,
            'icon' => $this->icon,
            'position' => $this->position,
            'css_styles' => $this->getCssStyles(),

            // Display Rules
            'is_active' => $this->is_active,
            'priority' => $this->priority,
            'start_date' => $this->start_date?->toIso8601String(),
            'end_date' => $this->end_date?->toIso8601String(),

            // Status
            'should_display' => $this->shouldDisplay(),
            'is_currently_active' => $this->isCurrentlyActive(),
            'has_expired' => $this->hasExpired(),
            'is_scheduled' => $this->isScheduled(),
            'days_until_expiry' => $this->getDaysUntilExpiry(),

            // Auto-Application
            'auto_apply' => $this->auto_apply,
            'conditions' => $this->conditions,

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            // Note: ProductResource not yet created
            // 'product' => new ProductResource($this->whenLoaded('product')),
        ];
    }
}
