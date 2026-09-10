<?php

namespace App\Http\Requests;

use App\Models\AzureGroupRoleMapping;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateAzureGroupRoleMapping extends FormRequest
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
        $mapping = $this->route('azure_group_role_mapping');
        $mappingId = $mapping instanceof AzureGroupRoleMapping ? $mapping->id : $mapping;

        return [
            'azure_group_id' => 'required|string|unique:azure_group_role_mappings,azure_group_id,'.$mappingId,
            'role_name' => 'required|string|exists:roles,name',
        ];
    }
}
