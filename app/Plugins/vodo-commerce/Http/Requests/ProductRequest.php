<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('id');

        $rules = [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'sku' => 'nullable|string|max:100',
            'description' => 'nullable|string',
            'short_description' => 'nullable|string|max:500',
            'price' => 'required|numeric|min:0',
            'compare_at_price' => 'nullable|numeric|min:0',
            'cost_price' => 'nullable|numeric|min:0',
            'category_id' => 'nullable|exists:commerce_categories,id',
            'images' => 'nullable|array',
            'images.*' => 'string|max:255',
            'stock_quantity' => 'integer|min:0',
            'stock_status' => 'in:in_stock,out_of_stock,backorder',
            'weight' => 'nullable|numeric|min:0',
            'dimensions' => 'nullable|array',
            'dimensions.length' => 'nullable|numeric|min:0',
            'dimensions.width' => 'nullable|numeric|min:0',
            'dimensions.height' => 'nullable|numeric|min:0',
            'is_virtual' => 'boolean',
            'is_downloadable' => 'boolean',
            'status' => 'in:draft,active,archived',
            'featured' => 'boolean',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'meta' => 'nullable|array',

            // Variants
            'variants' => 'nullable|array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required_with:variants|string|max:255',
            'variants.*.sku' => 'nullable|string|max:100',
            'variants.*.price' => 'nullable|numeric|min:0',
            'variants.*.compare_at_price' => 'nullable|numeric|min:0',
            'variants.*.stock_quantity' => 'integer|min:0',
            'variants.*.options' => 'nullable|array',
            'variants.*.image' => 'nullable|string|max:255',
            'variants.*.weight' => 'nullable|numeric|min:0',
            'variants.*.is_active' => 'boolean',
        ];

        // Unique SKU validation
        if ($productId) {
            $rules['sku'] .= "|unique:commerce_products,sku,{$productId}";
        } else {
            $rules['sku'] .= '|unique:commerce_products,sku';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required',
            'price.required' => 'Product price is required',
            'price.numeric' => 'Product price must be a number',
            'price.min' => 'Product price must be at least 0',
            'sku.unique' => 'This SKU is already in use',
            'category_id.exists' => 'Selected category does not exist',
        ];
    }
}
