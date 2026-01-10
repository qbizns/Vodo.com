<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'store_id' => $this->store_id,
            'user_id' => $this->user_id,

            // Business Information
            'business_name' => $this->business_name,
            'legal_name' => $this->legal_name,
            'slug' => $this->slug,
            'description' => $this->description,
            'logo' => $this->logo,
            'banner' => $this->banner,

            // Contact Information
            'email' => $this->email,
            'phone' => $this->phone,
            'website' => $this->website,

            // Address
            'address' => [
                'line1' => $this->address_line1,
                'line2' => $this->address_line2,
                'city' => $this->city,
                'state' => $this->state,
                'postal_code' => $this->postal_code,
                'country' => $this->country,
                'full_address' => $this->getFullAddress(),
            ],

            // Commission Settings
            'commission' => [
                'type' => $this->commission_type,
                'value' => (float) $this->commission_value,
                'tiers' => $this->commission_tiers,
            ],

            // Payout Settings
            'payout' => [
                'method' => $this->payout_method,
                'schedule' => $this->payout_schedule,
                'minimum_amount' => (float) $this->minimum_payout_amount,
                'can_request' => $this->canRequestPayout(),
                'pending_total' => $this->getPendingCommissionsTotal(),
                'approved_total' => $this->getApprovedCommissionsTotal(),
            ],

            // Status & Verification
            'status' => $this->status,
            'is_verified' => $this->is_verified,
            'verified_at' => $this->verified_at?->toISOString(),
            'rejection_reason' => $this->when($this->rejection_reason, $this->rejection_reason),

            // Ratings & Reviews
            'rating' => [
                'average' => (float) $this->average_rating,
                'total_reviews' => $this->total_reviews,
            ],

            // Performance Metrics
            'metrics' => [
                'total_products' => $this->total_products,
                'total_sales' => $this->total_sales,
                'total_revenue' => (float) $this->total_revenue,
            ],

            // Policies
            'policies' => [
                'shipping' => $this->shipping_policy,
                'return' => $this->return_policy,
                'terms_and_conditions' => $this->terms_and_conditions,
            ],

            // Relationships
            'products' => ProductResource::collection($this->whenLoaded('products')),
            'approved_products' => ProductResource::collection($this->whenLoaded('approvedProducts')),
            'reviews' => VendorReviewResource::collection($this->whenLoaded('reviews')),
            'payouts' => VendorPayoutResource::collection($this->whenLoaded('payouts')),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
        ];
    }
}
