<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BulkOrderActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_ids' => 'required|array|min:1',
            'order_ids.*' => 'required|integer|exists:commerce_orders,id',
            'action' => 'required|in:export,cancel,mark_as_exported',
            'reason' => 'required_if:action,cancel|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'order_ids.required' => 'At least one order must be selected',
            'order_ids.min' => 'At least one order must be selected',
            'order_ids.*.exists' => 'One or more selected orders are invalid',
            'action.required' => 'Action is required',
            'action.in' => 'Invalid bulk action',
            'reason.required_if' => 'Reason is required when cancelling orders',
        ];
    }
}
