<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderTimelineEventResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'event_type' => $this->event_type,
            'title' => $this->title,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'created_by_type' => $this->created_by_type,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
