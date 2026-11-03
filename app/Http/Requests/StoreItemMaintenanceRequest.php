<?php

namespace App\Http\Requests;

use App\Enums\MaintenanceType;
use App\Rules\MaxServerFileSize;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreItemMaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return (bool) $this->user()?->can('manage maintenance');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'performed_on' => ['required', 'date', 'before_or_equal:today'],
            'type' => ['required', Rule::enum(MaintenanceType::class)],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:999999'],
            'performed_by' => ['nullable', 'string', 'max:255'],
            'cost' => ['nullable', 'numeric', 'min:0', 'max:999999'],
            'description' => ['nullable', 'string', 'max:2000'],
            'files' => ['nullable', 'array'],
            'files.*' => ['file', 'mimes:jpg,jpeg,png,gif,webp,pdf,svg', new MaxServerFileSize],
        ];
    }

    /**
     * The validated maintenance fields, with the cost converted to cents.
     *
     * @return array<string, mixed>
     */
    public function maintenanceData(): array
    {
        $cost = $this->validated('cost');

        return [
            ...collect($this->validated())->except(['cost', 'files', 'remove_attachments'])->all(),
            'cost_cents' => $cost === null ? null : (int) round((float) $cost * 100),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'performed_on' => 'Datum',
            'type' => 'Art',
            'quantity' => 'Betroffene Menge',
            'performed_by' => 'Durchgeführt von',
            'cost' => 'Kosten',
            'description' => 'Beschreibung',
            'files' => 'Dateien',
            'files.*' => 'Datei',
        ];
    }
}
