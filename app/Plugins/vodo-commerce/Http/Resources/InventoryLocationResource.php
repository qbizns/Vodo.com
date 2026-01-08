<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryLocationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'code' => $this->resource->code,
            'type' => $this->resource->type,
            'address' => $this->resource->address,
            'city' => $this->resource->city,
            'state' => $this->resource->state,
            'postal_code' => $this->resource->postal_code,
            'country' => $this->resource->country,
            'full_address' => $this->resource->getFullAddress(),
            'contact' => [
                'name' => $this->resource->contact_name,
                'email' => $this->resource->contact_email,
                'phone' => $this->resource->contact_phone,
            ],
            'priority' => $this->resource->priority,
            'is_active' => $this->resource->is_active,
            'is_default' => $this->resource->is_default,
            'settings' => $this->resource->settings,
            'meta' => $this->resource->meta,
            'created_at' => $this->resource->created_at,
            'updated_at' => $this->resource->updated_at,
        ];
    }
}
