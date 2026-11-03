<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreHitobitoAccessRuleRequest extends FormRequest
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
            'hitobito_person_id' => ['nullable', 'required_without_all:hitobito_group_id,hitobito_role_class,hitobito_permission', 'integer', 'min:1'],
            'hitobito_group_id' => ['nullable', 'integer', 'min:1'],
            'hitobito_role_class' => ['nullable', 'string', 'max:255'],
            'hitobito_permission' => ['nullable', 'string', 'max:255'],
            'role_name' => ['nullable', 'string', 'exists:roles,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'hitobito_person_id.required_without_all' => 'Gib mindestens eine Personen-ID, Gruppen-ID, Rolle oder Berechtigung an.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hitobito_person_id' => 'Personen-ID',
            'hitobito_group_id' => 'Gruppen-ID',
            'hitobito_role_class' => 'Hitobito-Rolle',
            'hitobito_permission' => 'Hitobito-Berechtigung',
            'role_name' => 'App-Rolle',
            'description' => 'Beschreibung',
        ];
    }
}
