<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTaxZoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'sometimes|string|max:255',
            'description' => 'sometimes|nullable|string|max:1000',
            'is_active' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'locations' => 'sometimes|array',
            'locations.*.country_code' => 'required|string|size:2|regex:/^[A-Z]{2}$/',
            'locations.*.state_code' => 'sometimes|nullable|string|max:10',
            'locations.*.city' => 'sometimes|nullable|string|max:255',
            'locations.*.postal_code_pattern' => 'sometimes|nullable|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'The tax zone name must not exceed 255 characters.',
            'locations.*.country_code.required' => 'Each location must have a country code.',
            'locations.*.country_code.size' => 'Country code must be exactly 2 characters.',
            'locations.*.country_code.regex' => 'Country code must be 2 uppercase letters.',
        ];
    }
}
