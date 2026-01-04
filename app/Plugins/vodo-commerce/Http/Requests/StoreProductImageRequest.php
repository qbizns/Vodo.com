<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductImageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'url' => 'required|string|max:255',
            'alt_text' => 'nullable|string|max:255',
            'position' => 'nullable|integer|min:0',
            'is_primary' => 'boolean',
            'variant_id' => 'nullable|integer|exists:commerce_product_variants,id',
            'meta' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'url.required' => 'Image URL is required',
        ];
    }
}
