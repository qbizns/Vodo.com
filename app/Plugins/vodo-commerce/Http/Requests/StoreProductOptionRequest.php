<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'type' => 'required|in:select,radio,checkbox,text',
            'required' => 'boolean',
            'position' => 'nullable|integer|min:0',
            'template_id' => 'nullable|integer|exists:commerce_product_option_templates,id',
            'values' => 'required|array|min:1',
            'values.*.label' => 'required|string|max:255',
            'values.*.value' => 'required|string|max:255',
            'values.*.price_adjustment' => 'nullable|numeric',
            'values.*.position' => 'nullable|integer|min:0',
            'values.*.is_default' => 'boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Option name is required',
            'type.required' => 'Option type is required',
            'type.in' => 'Option type must be one of: select, radio, checkbox, text',
            'values.required' => 'At least one option value is required',
            'values.*.label.required' => 'Option value label is required',
            'values.*.value.required' => 'Option value is required',
        ];
    }
}
