<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $methodId = $this->route('shipping_method');

        return [
            'name' => 'sometimes|string|max:255',
            'code' => 'sometimes|string|max:50|unique:commerce_shipping_methods,code,' . $methodId,
            'description' => 'sometimes|nullable|string|max:1000',
            'calculation_type' => 'sometimes|in:flat_rate,per_item,weight_based,price_based',
            'base_cost' => 'sometimes|numeric|min:0',
            'min_delivery_days' => 'sometimes|nullable|integer|min:0',
            'max_delivery_days' => 'sometimes|nullable|integer|min:0|gte:min_delivery_days',
            'is_active' => 'sometimes|boolean',
            'requires_address' => 'sometimes|boolean',
            'min_order_amount' => 'sometimes|nullable|numeric|min:0',
            'max_order_amount' => 'sometimes|nullable|numeric|min:0|gte:min_order_amount',
            'settings' => 'sometimes|nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'code.unique' => 'This shipping method code is already in use.',
            'calculation_type.in' => 'Invalid calculation type.',
            'base_cost.min' => 'The base cost must be at least 0.',
            'max_delivery_days.gte' => 'Maximum delivery days must be greater than or equal to minimum delivery days.',
            'max_order_amount.gte' => 'Maximum order amount must be greater than or equal to minimum order amount.',
        ];
    }
}
