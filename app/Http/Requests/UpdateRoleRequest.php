<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateRoleRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $roleId = $this->route('role');

        return [
            'name' => 'required|string|unique:roles,name,'.$roleId['id'],
            'guard_name' => 'nullable|string|max:255',
            'permissions' => 'array',
            'permissions.*' => 'exists:permissions,name',
        ];
    }
}
