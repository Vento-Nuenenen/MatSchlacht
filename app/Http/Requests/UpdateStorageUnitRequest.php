<?php

namespace App\Http\Requests;

use App\Enums\StorageLevel;
use App\Models\StorageUnit;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * The level of a storage unit is fixed, but it can be renamed and moved to another parent of the same level.
 */
class UpdateStorageUnitRequest extends StoreStorageUnitRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return collect(parent::rules())->except('level')->all();
    }

    protected function storageLevel(): ?StorageLevel
    {
        /** @var StorageUnit $storageUnit */
        $storageUnit = $this->route('storage_unit');

        return $storageUnit->level;
    }
}
