<?php

namespace App\Http\Requests;

use App\Rules\MaxServerFileSize;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateItemRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'stock' => 'required|integer|min:0',
            'lot' => 'required|integer|min:1',
            'repair' => 'integer',
            'maintenance_interval_months' => 'nullable|integer|min:1|max:240',
            'item_group_id' => 'required|integer',
            'files' => 'nullable|array',
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,svg', new MaxServerFileSize],
            'remove_attachments' => 'nullable|array',
            'remove_attachments.*' => 'integer|exists:attachments,id',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name' => __('Name'),
            'stock' => __('Bestand'),
            'lot' => __('Gebinde'),
            'repair' => __('Reparatur'),
            'maintenance_interval_months' => __('Wartungsintervall'),
            'item_group_id' => __('Materialgruppe'),
            'files' => __('Dateien'),
            'files.*' => __('Datei'),
            'remove_attachments' => __('Anhänge entfernen'),
        ];
    }
}
