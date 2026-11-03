<?php

namespace App\Observers;

use App\Models\StorageUnit;
use App\Services\StorageCodeGenerator;

class StorageUnitObserver
{
    public function __construct(protected StorageCodeGenerator $generator) {}

    /**
     * Give a new unit its code. Bins are numbered and named after the codes of their ancestors.
     */
    public function creating(StorageUnit $storageUnit): void
    {
        if ($storageUnit->level->holdsStock()) {
            $storageUnit->code = $this->generator->nextBinNumber($storageUnit->parent_id);
            $storageUnit->name = filled($storageUnit->name) ? $storageUnit->name : $this->generator->binName($storageUnit);

            return;
        }

        if (blank($storageUnit->code)) {
            $storageUnit->code = $this->generator->uniqueCode($this->generator->fromName($storageUnit->name), $storageUnit->parent_id);
        }
    }

    /**
     * An emptied code is derived from the name again, a moved bin gets the next number of its new tier.
     */
    public function updating(StorageUnit $storageUnit): void
    {
        if ($storageUnit->level->holdsStock()) {
            if ($storageUnit->isDirty('parent_id')) {
                $storageUnit->unsetRelation('parent');
                $storageUnit->code = $this->generator->nextBinNumber($storageUnit->parent_id);
                $storageUnit->name = $this->generator->binName($storageUnit);
            }

            return;
        }

        if (blank($storageUnit->code)) {
            $storageUnit->code = $this->generator->uniqueCode($this->generator->fromName($storageUnit->name), $storageUnit->parent_id, $storageUnit->id);
        }
    }

    /**
     * Keep the names of the bins below a unit in line with its code and position.
     */
    public function updated(StorageUnit $storageUnit): void
    {
        if ($storageUnit->level->holdsStock() || ! $storageUnit->wasChanged(['code', 'parent_id'])) {
            return;
        }

        $storageUnit->fresh()?->withDescendants()
            ->filter(fn (StorageUnit $unit): bool => $unit->level->holdsStock())
            ->each(function (StorageUnit $bin): void {
                $bin->name = $this->generator->binName($bin);
                $bin->saveQuietly();
            });
    }
}
