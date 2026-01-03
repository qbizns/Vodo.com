<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportDigitalCodesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'codes' => 'required|array|min:1|max:10000',
            'codes.*' => 'required|string|max:100',
        ];
    }

    public function messages(): array
    {
        return [
            'codes.required' => 'Codes array is required',
            'codes.min' => 'At least one code is required',
            'codes.max' => 'Cannot import more than 10,000 codes at once',
            'codes.*.required' => 'Each code must be a non-empty string',
        ];
    }
}
