<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ExportOrdersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'format' => 'sometimes|in:csv',
            'status' => 'sometimes|string',
            'payment_status' => 'sometimes|string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'not_exported' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'format.in' => 'Only CSV format is currently supported',
            'date_to.after_or_equal' => 'End date must be after or equal to start date',
        ];
    }
}
