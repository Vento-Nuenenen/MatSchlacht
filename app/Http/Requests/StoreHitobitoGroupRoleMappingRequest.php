<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHitobitoGroupRoleMappingRequest extends FormRequest
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
            'hitobito_group_id' => ['required', 'integer', 'min:1'],
            'hitobito_role_class' => ['nullable', 'string', 'max:255'],
            'role_name' => ['required', 'string', 'exists:roles,name'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hitobito_group_id' => 'Gruppen-ID',
            'hitobito_role_class' => 'Hitobito-Rolle',
            'role_name' => 'App-Rolle',
        ];
    }
}
