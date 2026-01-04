<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AdjustLoyaltyPointsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'points' => 'required|integer',
            'description' => 'required|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'points.required' => 'The points amount is required.',
            'points.integer' => 'The points must be an integer.',
            'description.required' => 'A description is required for point adjustments.',
        ];
    }
}
