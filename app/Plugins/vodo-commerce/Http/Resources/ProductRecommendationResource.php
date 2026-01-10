<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductRecommendationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'source_product_id' => $this->source_product_id,
            'recommended_product_id' => $this->recommended_product_id,

            // Type & Source
            'type' => $this->type,
            'source' => $this->source,

            // Scoring
            'relevance_score' => (float) $this->relevance_score,
            'relevance_percentage' => $this->getRelevancePercentage(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,

            // Performance Metrics
            'impression_count' => $this->impression_count,
            'click_count' => $this->click_count,
            'conversion_count' => $this->conversion_count,
            'conversion_rate' => (float) $this->conversion_rate,
            'click_through_rate' => $this->getClickThroughRate(),
            'is_performing' => $this->isPerforming(),

            // Display
            'display_context' => $this->display_context,
            'custom_message' => $this->custom_message,

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),

            // Relationships
            // Note: ProductResource not yet created, will be added in future phase
            // 'source_product' => new ProductResource($this->whenLoaded('sourceProduct')),
            // 'recommended_product' => new ProductResource($this->whenLoaded('recommendedProduct')),
        ];
    }
}
