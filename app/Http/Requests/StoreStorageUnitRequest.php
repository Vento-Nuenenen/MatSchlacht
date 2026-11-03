<?php

namespace App\Http\Requests;

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreStorageUnitRequest extends FormRequest
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
            'level' => ['required', Rule::enum(StorageLevel::class)],
            'parent_id' => ['nullable', 'integer', 'exists:storage_units,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * Ensure the unit is placed one level below its parent, e.g. a Lagerplatz in a Lagerregal.
     *
     * @return array<int, Closure(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $level = $this->storageLevel();

                if ($level === null || $validator->errors()->isNotEmpty()) {
                    return;
                }

                $parent = $this->filled('parent_id') ? StorageUnit::find($this->integer('parent_id')) : null;
                $expectedParentLevel = $level->parentLevel();

                if ($expectedParentLevel === null && $parent !== null) {
                    $validator->errors()->add('parent_id', "Ein {$level->label()} kann nicht in einer anderen Lagereinheit liegen.");
                } elseif ($expectedParentLevel !== null && $parent?->level !== $expectedParentLevel) {
                    $validator->errors()->add('parent_id', "Ein {$level->label()} muss in einem {$expectedParentLevel->label()} liegen.");
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
            'level' => 'Ebene',
            'parent_id' => 'Übergeordnete Lagereinheit',
            'name' => 'Bezeichnung',
            'description' => 'Beschreibung',
        ];
    }

    protected function storageLevel(): ?StorageLevel
    {
        return StorageLevel::tryFrom((string) $this->input('level'));
    }
}
