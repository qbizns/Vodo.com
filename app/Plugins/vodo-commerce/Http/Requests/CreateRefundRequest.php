<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateRefundRequest extends FormRequest
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
            'items.*.amount' => 'required|numeric|min:0',
            'amount' => 'sometimes|numeric|min:0',
            'reason' => 'sometimes|string|max:500',
            'refund_method' => 'sometimes|in:original_payment,store_credit,manual',
            'notes' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'At least one item is required for refund',
            'items.min' => 'At least one item is required for refund',
            'items.*.order_item_id.exists' => 'Invalid order item',
            'items.*.quantity.min' => 'Quantity must be at least 1',
            'items.*.amount.min' => 'Amount must be positive',
            'refund_method.in' => 'Invalid refund method',
        ];
    }
}
