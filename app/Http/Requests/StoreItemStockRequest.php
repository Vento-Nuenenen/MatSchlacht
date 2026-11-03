<?php

namespace App\Http\Requests;

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreItemStockRequest extends FormRequest
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
            'storage_unit_id' => ['required', 'integer', 'exists:storage_units,id'],
            'item_id' => [
                'required',
                'integer',
                'exists:items,id',
                Rule::unique('item_stocks')->where('storage_unit_id', $this->integer('storage_unit_id')),
            ],
            'quantity' => ['required', 'integer', 'min:0', 'max:999999'],
        ];
    }

    /**
     * Stock can only be stored in bins (Lagerplätze).
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $storageUnit = StorageUnit::find($this->integer('storage_unit_id'));

                if ($storageUnit !== null && $storageUnit->level !== StorageLevel::Bin) {
                    $validator->errors()->add('storage_unit_id', 'Material kann nur auf einem Lagerplatz eingelagert werden.');
                }
            },
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'storage_unit_id' => 'Lagerplatz',
            'item_id' => 'Material',
            'quantity' => 'Menge',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'item_id.unique' => 'Dieses Material liegt bereits auf diesem Lagerplatz. Bitte die bestehende Menge anpassen.',
        ];
    }
}
