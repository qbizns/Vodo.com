<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubscriptionEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subscription_id' => $this->subscription_id,

            // Event Details
            'event_type' => $this->event_type,
            'description' => $this->description,
            'event_data' => $this->event_data,

            // Triggered By
            'triggered_by_type' => $this->triggered_by_type,
            'triggered_by_id' => $this->triggered_by_id,

            // Timestamp
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),

            // Relationships
            'subscription' => new SubscriptionResource($this->whenLoaded('subscription')),
        ];
    }
}
