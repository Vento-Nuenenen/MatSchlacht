<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreRoleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|unique:roles,name',
            'guard_name' => 'nullable|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ];
    }
}
