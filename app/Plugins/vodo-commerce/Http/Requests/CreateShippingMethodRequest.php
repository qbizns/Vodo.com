<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShippingMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:commerce_shipping_methods,code',
            'description' => 'sometimes|nullable|string|max:1000',
            'calculation_type' => 'required|in:flat_rate,per_item,weight_based,price_based',
            'base_cost' => 'required|numeric|min:0',
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
            'name.required' => 'The shipping method name is required.',
            'code.required' => 'The shipping method code is required.',
            'code.unique' => 'This shipping method code is already in use.',
            'calculation_type.required' => 'The calculation type is required.',
            'calculation_type.in' => 'Invalid calculation type.',
            'base_cost.required' => 'The base cost is required.',
            'base_cost.min' => 'The base cost must be at least 0.',
            'max_delivery_days.gte' => 'Maximum delivery days must be greater than or equal to minimum delivery days.',
            'max_order_amount.gte' => 'Maximum order amount must be greater than or equal to minimum order amount.',
        ];
    }
}
