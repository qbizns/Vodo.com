<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductOptionTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'options' => 'required|array|min:1',
            'options.*.name' => 'required|string|max:255',
            'options.*.type' => 'required|in:select,radio,checkbox,text',
            'options.*.required' => 'boolean',
            'options.*.values' => 'required|array|min:1',
            'options.*.values.*.label' => 'required|string|max:255',
            'options.*.values.*.value' => 'required|string|max:255',
            'options.*.values.*.price_adjustment' => 'nullable|numeric',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Template name is required',
            'options.required' => 'At least one option is required',
            'options.*.name.required' => 'Option name is required',
            'options.*.type.required' => 'Option type is required',
            'options.*.values.required' => 'Option values are required',
        ];
    }
}
