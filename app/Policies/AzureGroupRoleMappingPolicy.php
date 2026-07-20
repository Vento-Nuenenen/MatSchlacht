<?php

namespace App\Policies;

use App\Models\AzureGroupRoleMapping;
use App\Models\User;

class AzureGroupRoleMappingPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('manage azure mappings');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AzureGroupRoleMapping $azureGroupRoleMapping): bool
    {
        return $user->can('manage azure mappings');
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('manage azure mappings');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AzureGroupRoleMapping $azureGroupRoleMapping): bool
    {
        return $user->can('manage azure mappings');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AzureGroupRoleMapping $azureGroupRoleMapping): bool
    {
        return $user->can('manage azure mappings');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, AzureGroupRoleMapping $azureGroupRoleMapping): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, AzureGroupRoleMapping $azureGroupRoleMapping): bool
    {
        return false;
    }
}
