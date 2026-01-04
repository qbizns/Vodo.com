<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tracking_number' => 'sometimes|string|max:255',
            'carrier' => 'sometimes|string|max:255',
            'tracking_url' => 'sometimes|url|max:500',
            'status' => 'sometimes|in:pending,in_transit,out_for_delivery,delivered,failed',
            'estimated_delivery' => 'sometimes|date',
            'notes' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'status.in' => 'Invalid fulfillment status',
            'tracking_url.url' => 'Tracking URL must be a valid URL',
        ];
    }
}
