<?php

namespace App\Observers;

use App\Jobs\SendOrderStatusNotification;
use App\Models\Order;
use App\States\Order\Accepted;
use App\States\Order\Committed;

class OrderObserver
{
    /**
     * Handle the Order "updated" event.
     */
    public function updated(Order $order): void
    {
        if ($order->isDirty('state')) {
            $newState = $order->state;

            if ($newState instanceof Committed || $newState instanceof Accepted) {
                SendOrderStatusNotification::dispatch($order, $newState::$name);
            }
        }
    }
}
