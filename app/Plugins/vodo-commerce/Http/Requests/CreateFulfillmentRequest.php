<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateFulfillmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.order_item_id' => 'required|integer|exists:commerce_order_items,id',
            'items.*.quantity' => 'required|integer|min:1',
            'tracking_number' => 'sometimes|string|max:255',
            'carrier' => 'sometimes|string|max:255',
            'tracking_url' => 'sometimes|url|max:500',
            'estimated_delivery' => 'sometimes|date',
            'notes' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required for fulfillment',
            'items.min' => 'At least one item is required for fulfillment',
            'items.*.order_item_id.exists' => 'Invalid order item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
        ];
    }
}
