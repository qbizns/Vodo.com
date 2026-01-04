<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddOrderNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => 'required|string|max:5000',
            'is_customer_visible' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Note content is required',
            'content.max' => 'Note content cannot exceed 5000 characters',
        ];
    }
}
