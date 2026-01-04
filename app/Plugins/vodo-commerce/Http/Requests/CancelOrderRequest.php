<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CancelOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => 'sometimes|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'Cancellation reason cannot exceed 1000 characters',
        ];
    }
}
