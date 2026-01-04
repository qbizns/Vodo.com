<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaxExemptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'type' => 'required|in:customer,product,category,customer_group',
            'entity_id' => 'required|integer|min:1',
            'certificate_number' => 'sometimes|nullable|string|max:255',
            'valid_from' => 'sometimes|nullable|date',
            'valid_until' => 'sometimes|nullable|date|after:valid_from',
            'country_code' => 'sometimes|nullable|string|size:2|regex:/^[A-Z]{2}$/',
            'state_code' => 'sometimes|nullable|string|max:10',
            'is_active' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The exemption name is required.',
            'type.required' => 'The exemption type is required.',
            'type.in' => 'Invalid exemption type.',
            'entity_id.required' => 'The entity ID is required.',
            'valid_until.after' => 'Valid until date must be after valid from date.',
            'country_code.size' => 'Country code must be exactly 2 characters.',
            'country_code.regex' => 'Country code must be 2 uppercase letters.',
        ];
    }
}
