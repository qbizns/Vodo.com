<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => 'nullable|exists:users,id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:commerce_employees,email',
            'phone' => 'nullable|string|max:50',
            'role' => 'required|in:admin,manager,staff,support',
            'permissions' => 'nullable|array',
            'permissions.*' => 'string',
            'is_active' => 'nullable|boolean',
            'hired_at' => 'nullable|date',
            'meta' => 'nullable|array',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'The employee name is required.',
            'email.required' => 'The employee email is required.',
            'email.unique' => 'This email is already registered to another employee.',
            'role.required' => 'The employee role is required.',
            'role.in' => 'The role must be admin, manager, staff, or support.',
        ];
    }
}
