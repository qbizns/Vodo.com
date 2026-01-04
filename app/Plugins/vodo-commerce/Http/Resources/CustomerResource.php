<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'email' => $this->email,
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'company' => $this->company,
            'accepts_marketing' => $this->accepts_marketing,
            'total_orders' => $this->total_orders,
            'total_spent' => $this->total_spent,
            'is_banned' => $this->is_banned,
            'banned_at' => $this->banned_at?->toIso8601String(),
            'ban_reason' => $this->when($this->is_banned, $this->ban_reason),
            'groups' => $this->when($this->relationLoaded('groups'), CustomerGroupResource::collection($this->groups)),
            'wallet' => $this->when($this->relationLoaded('wallet'), new CustomerWalletResource($this->wallet)),
            'loyalty_points' => $this->when($this->relationLoaded('loyaltyPoints'), new LoyaltyPointResource($this->loyaltyPoints)),
            'tags' => $this->tags,
            'notes' => $this->notes,
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
