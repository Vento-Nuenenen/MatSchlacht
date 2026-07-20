<?php

namespace App\Policies;

use App\Models\ItemCollection;
use App\Models\User;

class ItemCollectionPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view itemcollections');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, ItemCollection $itemCollection): bool
    {
        return $user->can('view itemcollections');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create itemcollections');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, ItemCollection $itemCollection): bool
    {
        return $user->can('edit itemcollections');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, ItemCollection $itemCollection): bool
    {
        return $user->can('delete itemcollections');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, ItemCollection $itemCollection): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, ItemCollection $itemCollection): bool
    {
        return false;
    }
}
