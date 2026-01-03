<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductOptionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'string|max:255',
            'type' => 'in:select,radio,checkbox,text',
            'required' => 'boolean',
            'position' => 'nullable|integer|min:0',
            'values' => 'nullable|array',
            'values.*.id' => 'nullable|integer',
            'values.*.label' => 'required_with:values|string|max:255',
            'values.*.value' => 'required_with:values|string|max:255',
            'values.*.price_adjustment' => 'nullable|numeric',
            'values.*.position' => 'nullable|integer|min:0',
            'values.*.is_default' => 'boolean',
        ];
    }
}
