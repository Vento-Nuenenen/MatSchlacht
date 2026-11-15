<?php

namespace App\Enums;

use App\Models\Order;
use App\Models\User;

/**
 * Which orders a unit (role) may move through a state transition.
 */
enum TransitionScope: string
{
    case Own = 'own';
    case Unit = 'unit';
    case All = 'all';

    public function label(): string
    {
        return match ($this) {
            self::Own => __('Eigene Bestellungen'),
            self::Unit => __('Bestellungen der Einheit'),
            self::All => __('Alle Bestellungen'),
        };
    }

    /**
     * Whether a member of the unit with the given role id may move the order with this scope.
     */
    public function covers(Order $order, User $user, int $roleId): bool
    {
        return match ($this) {
            self::Own => (int) $order->user_id === (int) $user->id,
            self::Unit => (int) $order->role_id === $roleId,
            self::All => true,
        };
    }
}
