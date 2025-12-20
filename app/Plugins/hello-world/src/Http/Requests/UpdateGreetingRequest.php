<?php

namespace HelloWorld\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGreetingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'message' => ['sometimes', 'required', 'string', 'max:255'],
            'author' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'message.required' => 'A greeting message is required.',
            'message.max' => 'The greeting message must not exceed 255 characters.',
            'author.max' => 'The author name must not exceed 100 characters.',
        ];
    }
}
