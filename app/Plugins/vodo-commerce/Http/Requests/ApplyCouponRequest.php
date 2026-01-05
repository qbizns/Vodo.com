<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApplyCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50',
            'cart_id' => 'required|integer|exists:commerce_carts,id',
            'customer_id' => 'nullable|integer|exists:commerce_customers,id',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Coupon code is required',
            'cart_id.required' => 'Cart ID is required',
            'cart_id.exists' => 'Cart not found',
            'customer_id.exists' => 'Customer not found',
        ];
    }
}
