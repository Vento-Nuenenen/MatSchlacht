<?php

namespace App\States\Order;

use App\Models\Order;
use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

/**
 * @extends State<Order>
 */
abstract class OrderState extends State
{
    /**
     * The states in which an order blocks its material for its period.
     *
     * @var array<int, class-string<OrderState>>
     */
    public const array RESERVING_STATES = [Committed::class, Accepted::class, Delivered::class];

    /**
     * The states in which the material has been handed out, the positions are fixed then.
     *
     * @var array<int, class-string<OrderState>>
     */
    public const array HANDED_OUT_STATES = [Delivered::class, Returned::class, Archived::class];

    abstract public function display(): string;

    abstract public function color(): string;

    public function reservesStock(): bool
    {
        return in_array(static::class, self::RESERVING_STATES, true);
    }

    public function isHandedOut(): bool
    {
        return in_array(static::class, self::HANDED_OUT_STATES, true);
    }

    public static function config(): StateConfig
    {
        return parent::config()
            ->default(Stored::class)
            ->allowTransition(Stored::class, Committed::class)
            ->allowTransition(Committed::class, Stored::class) // Undo Committed (Rejection)
            ->allowTransition(Committed::class, Accepted::class)
            ->allowTransition(Accepted::class, Committed::class) // Undo Accepted
            ->allowTransition(Accepted::class, Delivered::class)
            ->allowTransition(Delivered::class, Accepted::class) // Undo Delivered
            ->allowTransition(Delivered::class, Returned::class)
            ->allowTransition(Returned::class, Delivered::class) // Undo Returned
            ->allowTransition(Returned::class, Archived::class)
            ->allowTransition([Stored::class, Committed::class, Accepted::class], Archived::class); // Allow archiving if cancelled
    }
}
