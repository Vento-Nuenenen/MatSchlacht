<?php

namespace App\States\Order;

use Spatie\ModelStates\State;
use Spatie\ModelStates\StateConfig;

abstract class OrderState extends State
{
    abstract public function display(): string;

    abstract public function color(): string;

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
