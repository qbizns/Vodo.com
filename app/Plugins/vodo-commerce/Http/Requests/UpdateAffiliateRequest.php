<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAffiliateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $affiliateId = $this->route('affiliate');

        return [
            'code' => 'nullable|string|max:50|unique:commerce_affiliates,code,' . $affiliateId,
            'commission_rate' => 'sometimes|required|numeric|min:0|max:100',
            'commission_type' => 'sometimes|required|in:percentage,fixed',
            'is_active' => 'nullable|boolean',
            'meta' => 'nullable|array',
        ];
    }
}
