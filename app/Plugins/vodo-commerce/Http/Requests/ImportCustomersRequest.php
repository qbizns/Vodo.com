<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportCustomersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'customers' => 'required|array|min:1|max:1000',
            'customers.*.email' => 'required|email',
            'customers.*.first_name' => 'required|string|max:255',
            'customers.*.last_name' => 'required|string|max:255',
            'customers.*.phone' => 'nullable|string|max:50',
            'customers.*.company' => 'nullable|string|max:255',
            'customers.*.accepts_marketing' => 'nullable|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'customers.required' => 'Customer data is required.',
            'customers.max' => 'Cannot import more than 1000 customers at once.',
            'customers.*.email.required' => 'Each customer must have an email.',
            'customers.*.email.email' => 'Each customer email must be valid.',
        ];
    }
}
