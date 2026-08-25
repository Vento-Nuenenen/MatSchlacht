<?php

namespace App\Http\Requests;

use App\Rules\CanAssignRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
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
        return [
            'firstname' => 'required',
            'lastname' => 'required',
            'scoutname' => 'nullable|string|max:255',
            'email' => 'required|email|unique:users,email,'.$this->user->id,
            'aktiv' => 'boolean',

            'roles' => 'array',
            'roles.*' => ['exists:roles,name', new CanAssignRole],
        ];
    }
}
