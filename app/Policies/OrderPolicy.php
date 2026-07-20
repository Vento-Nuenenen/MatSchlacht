<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;
use App\States\Order\Stored;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->can('view orders');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Order $order): bool
    {
        if ($user->can('view orders')) {
            return true;
        }

        return $user->can('view own orders') && $order->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->can('create orders');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Order $order): bool
    {
        if ($user->can('edit orders')) {
            return true;
        }

        if ($user->can('edit own orders') && $order->user_id === $user->id) {
            // Usually only drafts can be edited by regular users
            return $order->state->equals(Stored::class);
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Order $order): bool
    {
        if ($user->can('delete orders')) {
            return true;
        }

        if ($user->can('delete own orders') && $order->user_id === $user->id) {
            // Usually only drafts can be deleted by regular users
            return $order->state->equals(Stored::class);
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->can('edit orders');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->can('delete orders');
    }

    /**
     * Determine whether the user can manage the order (via OrderManagementController).
     */
    public function manage(User $user): bool
    {
        return $user->can('edit orders');
    }

    /**
     * Determine whether the user can export the order as PDF.
     */
    public function export(User $user, Order $order): bool
    {
        return $user->can('edit orders');
    }

    /**
     * Determine whether the user can export the return list as PDF.
     */
    public function exportReturn(User $user, Order $order): bool
    {
        return $user->can('edit orders');
    }
}
