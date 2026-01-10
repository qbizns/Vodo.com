<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'vendor_id' => $this->vendor_id,
            'customer_id' => $this->customer_id,
            'order_id' => $this->order_id,

            // Review Content
            'rating' => $this->rating,
            'title' => $this->title,
            'comment' => $this->comment,

            // Sub-Ratings
            'detailed_ratings' => [
                'product_quality' => $this->product_quality_rating,
                'shipping_speed' => $this->shipping_speed_rating,
                'communication' => $this->communication_rating,
                'customer_service' => $this->customer_service_rating,
                'average' => $this->getAverageDetailedRating(),
            ],

            // Verification
            'is_verified_purchase' => $this->is_verified_purchase,

            // Status
            'status' => $this->status,
            'is_approved' => $this->is_approved,
            'is_featured' => $this->is_featured,
            'is_flagged' => $this->is_flagged,
            'flag_reason' => $this->when($this->is_flagged, $this->flag_reason),

            // Helpfulness
            'helpful_count' => $this->helpful_count,
            'unhelpful_count' => $this->unhelpful_count,
            'helpfulness_score' => $this->getHelpfulnessScore(),

            // Responses
            'vendor_response' => $this->when($this->vendor_response, [
                'content' => $this->vendor_response,
                'responded_at' => $this->vendor_response_at?->toISOString(),
            ]),
            'admin_response' => $this->when($this->admin_response, [
                'content' => $this->admin_response,
                'responded_at' => $this->admin_response_at?->toISOString(),
            ]),

            // Relationships
            'vendor' => new VendorResource($this->whenLoaded('vendor')),
            'customer' => new CustomerResource($this->whenLoaded('customer')),

            // Metadata
            'meta' => $this->meta,

            // Timestamps
            'approved_at' => $this->approved_at?->toISOString(),
            'flagged_at' => $this->flagged_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),
            'deleted_at' => $this->when($this->deleted_at, $this->deleted_at?->toISOString()),
        ];
    }
}
