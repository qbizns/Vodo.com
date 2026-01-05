<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DiscountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'value' => $this->value,
            'minimum_order' => $this->minimum_order,
            'usage_limit' => $this->usage_limit,
            'per_customer_limit' => $this->per_customer_limit,
            'current_usage' => $this->current_usage,
            'starts_at' => $this->starts_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'is_active' => $this->is_active,

            // Phase 4.2: Advanced promotion fields
            'applies_to' => $this->applies_to,
            'promotion_type' => $this->promotion_type,
            'target_config' => $this->target_config,
            'included_product_ids' => $this->included_product_ids,
            'excluded_product_ids' => $this->excluded_product_ids,
            'included_category_ids' => $this->included_category_ids,
            'excluded_category_ids' => $this->excluded_category_ids,
            'included_brand_ids' => $this->included_brand_ids,
            'excluded_brand_ids' => $this->excluded_brand_ids,
            'customer_eligibility' => $this->customer_eligibility,
            'allowed_customer_group_ids' => $this->allowed_customer_group_ids,
            'allowed_customer_ids' => $this->allowed_customer_ids,
            'first_order_only' => $this->first_order_only,
            'min_items_quantity' => $this->min_items_quantity,
            'max_items_quantity' => $this->max_items_quantity,
            'is_stackable' => $this->is_stackable,
            'priority' => $this->priority,
            'is_automatic' => $this->is_automatic,
            'stop_further_rules' => $this->stop_further_rules,
            'display_message' => $this->display_message,
            'badge_text' => $this->badge_text,
            'badge_color' => $this->badge_color,

            // Relationships
            'usages' => $this->when($this->relationLoaded('usages'), CouponUsageResource::collection($this->usages)),
            'rules' => $this->when($this->relationLoaded('rules'), PromotionRuleResource::collection($this->rules)),

            'conditions' => $this->conditions,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
