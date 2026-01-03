<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AttachDigitalFileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'file' => 'required|file|max:512000', // 500MB max
            'name' => 'nullable|string|max:255',
            'download_limit' => 'nullable|integer|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'File is required',
            'file.max' => 'File size must not exceed 500MB',
        ];
    }
}
