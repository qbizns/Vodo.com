<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $groupId = $this->route('group') ?? $this->route('customer_group');

        return [
            'name' => 'sometimes|required|string|max:255',
            'slug' => 'nullable|string|max:255|unique:commerce_customer_groups,slug,' . $groupId . ',id,store_id,' . $this->getCurrentStore()->id,
            'description' => 'nullable|string',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ];
    }

    protected function getCurrentStore()
    {
        return request()->attributes->get('current_store');
    }
}
