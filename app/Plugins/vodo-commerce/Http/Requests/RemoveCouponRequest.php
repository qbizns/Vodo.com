<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RemoveCouponRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Coupon code is required',
            'cart_id.required' => 'Cart ID is required',
            'cart_id.exists' => 'Cart not found',
        ];
    }
}
