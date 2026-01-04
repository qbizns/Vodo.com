<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateShippingRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shipping_method_id' => 'required|integer|exists:commerce_shipping_methods,id',
            'shipping_zone_id' => 'required|integer|exists:commerce_shipping_zones,id',
            'rate' => 'required|numeric|min:0',
            'per_item_rate' => 'sometimes|numeric|min:0',
            'weight_rate' => 'sometimes|numeric|min:0',
            'min_weight' => 'sometimes|nullable|numeric|min:0',
            'max_weight' => 'sometimes|nullable|numeric|min:0|gte:min_weight',
            'min_price' => 'sometimes|nullable|numeric|min:0',
            'max_price' => 'sometimes|nullable|numeric|min:0|gte:min_price',
            'is_free_shipping' => 'sometimes|boolean',
            'free_shipping_threshold' => 'sometimes|nullable|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'shipping_method_id.required' => 'The shipping method is required.',
            'shipping_method_id.exists' => 'The selected shipping method does not exist.',
            'shipping_zone_id.required' => 'The shipping zone is required.',
            'shipping_zone_id.exists' => 'The selected shipping zone does not exist.',
            'rate.required' => 'The shipping rate is required.',
            'rate.min' => 'The shipping rate must be at least 0.',
            'max_weight.gte' => 'Maximum weight must be greater than or equal to minimum weight.',
            'max_price.gte' => 'Maximum price must be greater than or equal to minimum price.',
        ];
    }
}
