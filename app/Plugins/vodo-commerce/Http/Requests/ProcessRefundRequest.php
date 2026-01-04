<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProcessRefundRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:approve,reject,process',
            'reason' => 'required_if:action,reject|string|max:500',
            'notes' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Refund action is required',
            'action.in' => 'Invalid refund action',
            'reason.required_if' => 'Reason is required when rejecting a refund',
        ];
    }
}
