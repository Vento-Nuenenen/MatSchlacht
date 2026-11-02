<?php

namespace App\Listeners;

use App\Jobs\SendOrderStatusNotification;
use App\Models\Order;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use Spatie\ModelStates\Events\StateChanged;

class DispatchOrderStatusNotification
{
    /**
     * Notify the orderer and the material responsibles when an order was committed or accepted.
     */
    public function handle(StateChanged $event): void
    {
        if (! $event->model instanceof Order) {
            return;
        }

        $finalState = $event->finalState;

        if ($finalState instanceof Committed || $finalState instanceof Accepted) {
            SendOrderStatusNotification::dispatch($event->model, $finalState::getMorphClass());
        }
    }
}
