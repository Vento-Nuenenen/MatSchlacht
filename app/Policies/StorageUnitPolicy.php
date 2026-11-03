<?php

namespace App\Policies;

use App\Models\StorageUnit;
use App\Models\User;

class StorageUnitPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view storage');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, StorageUnit $storageUnit): bool
    {
        return $user->can('view storage');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create storage');
    }

    /**
     * Determine whether the user can update the model, including the stock stored in it.
     */
    public function update(User $user, StorageUnit $storageUnit): bool
    {
        return $user->can('edit storage');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, StorageUnit $storageUnit): bool
    {
        return $user->can('delete storage');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, StorageUnit $storageUnit): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, StorageUnit $storageUnit): bool
    {
        return false;
    }
}
