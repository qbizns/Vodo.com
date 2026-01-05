<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'type' => $this->type,
            'provider' => $this->provider,
            'logo' => $this->logo,
            'description' => $this->description,

            // Configuration (sanitized - no secrets)
            'has_configuration' => !empty($this->configuration),
            'is_configured' => $this->isConfigured(),

            // Support & Restrictions
            'supported_currencies' => $this->supported_currencies,
            'supported_countries' => $this->supported_countries,
            'supported_payment_types' => $this->supported_payment_types,

            // Fee Structure
            'fees' => $this->fees,
            'minimum_amount' => $this->minimum_amount,
            'maximum_amount' => $this->maximum_amount,

            // Banks (for display)
            'supported_banks' => $this->supported_banks,

            // Status & Display
            'is_active' => $this->is_active,
            'is_default' => $this->is_default,
            'display_order' => $this->display_order,
            'requires_shipping_address' => $this->requires_shipping_address,
            'requires_billing_address' => $this->requires_billing_address,

            // Statistics
            'transaction_count' => $this->when(
                $this->relationLoaded('transactions'),
                fn() => $this->transactions->count()
            ),

            // Metadata
            'meta' => $this->meta,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
