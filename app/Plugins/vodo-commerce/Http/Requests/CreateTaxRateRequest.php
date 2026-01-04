<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateTaxRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tax_zone_id' => 'required|integer|exists:commerce_tax_zones,id',
            'name' => 'required|string|max:255',
            'code' => 'sometimes|nullable|string|max:50',
            'rate' => 'required|numeric|min:0',
            'type' => 'required|in:percentage,fixed',
            'compound' => 'sometimes|boolean',
            'shipping_taxable' => 'sometimes|boolean',
            'priority' => 'sometimes|integer|min:0',
            'is_active' => 'sometimes|boolean',
            'category_id' => 'sometimes|nullable|integer|exists:commerce_categories,id',
        ];
    }

    public function messages(): array
    {
        return [
            'tax_zone_id.required' => 'The tax zone is required.',
            'tax_zone_id.exists' => 'The selected tax zone does not exist.',
            'name.required' => 'The tax rate name is required.',
            'rate.required' => 'The tax rate is required.',
            'rate.min' => 'The tax rate must be at least 0.',
            'type.required' => 'The tax type is required.',
            'type.in' => 'Tax type must be either percentage or fixed.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
