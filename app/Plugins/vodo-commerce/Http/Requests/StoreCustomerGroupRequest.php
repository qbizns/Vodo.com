<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCustomerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:commerce_customer_groups,slug,NULL,id,store_id,' . $this->getCurrentStore()->id,
            'description' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The group name is required.',
            'discount_percentage.max' => 'Discount percentage cannot exceed 100%.',
        ];
    }

    protected function getCurrentStore()
    {
        return request()->attributes->get('current_store');
    }
}
