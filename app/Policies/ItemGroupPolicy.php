<?php

namespace App\Policies;

use App\Models\ItemGroup;
use App\Models\User;

class ItemGroupPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view itemgroups');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ItemGroup $itemGroup): bool
    {
        return $user->can('view itemgroups');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create itemgroups');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ItemGroup $itemGroup): bool
    {
        return $user->can('edit itemgroups');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ItemGroup $itemGroup): bool
    {
        return $user->can('delete itemgroups');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ItemGroup $itemGroup): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ItemGroup $itemGroup): bool
    {
        return false;
    }
}
