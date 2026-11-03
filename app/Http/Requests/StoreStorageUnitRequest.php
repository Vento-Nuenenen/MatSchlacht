<?php

namespace App\Http\Requests;

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use App\Services\StorageCodeGenerator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Unique;
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
     * Codes are always stored in capital letters.
     */
    protected function prepareForValidation(): void
    {
        if (is_string($this->input('code'))) {
            $this->merge(['code' => Str::upper(trim($this->input('code')))]);
        }
    }

    /**
     * Get the validation rules that apply to the request. The name and code of bins are generated.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $isBin = fn (): bool => (bool) $this->storageLevel()?->holdsStock();

        return [
            'level' => ['required', Rule::enum(StorageLevel::class)],
            'parent_id' => ['nullable', 'integer', 'exists:storage_units,id'],
            'name' => [Rule::excludeIf($isBin), 'required', 'string', 'max:255'],
            'code' => [Rule::excludeIf($isBin), 'nullable', 'string', 'max:'.StorageCodeGenerator::MAX_LENGTH, 'regex:/^[A-Z0-9]+$/', $this->uniqueCodeRule()],
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
            'code' => 'Code',
            'description' => 'Beschreibung',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.regex' => 'Der Code darf nur Buchstaben von A bis Z und Ziffern enthalten.',
            'code.unique' => 'Dieser Code wird in derselben übergeordneten Lagereinheit schon verwendet.',
        ];
    }

    protected function storageLevel(): ?StorageLevel
    {
        return StorageLevel::tryFrom((string) $this->input('level'));
    }

    /**
     * The unit that keeps its own code, none for a new unit.
     */
    protected function ignoredStorageUnitId(): ?int
    {
        return null;
    }

    /**
     * A code only has to be unique among the units of the same parent.
     */
    protected function uniqueCodeRule(): Unique
    {
        $parentId = $this->filled('parent_id') ? $this->integer('parent_id') : null;

        return Rule::unique('storage_units', 'code')
            ->where(fn ($query) => $query->where('parent_id', $parentId))
            ->ignore($this->ignoredStorageUnitId());
    }
}
