<?php

namespace App\Listeners;

use App\Jobs\SendOrderStatusNotification;
use App\Models\Order;
use App\Services\ComponentManager;
use App\Services\OrderWorkflow;
use Spatie\ModelStates\Events\StateChanged;

class DispatchOrderStatusNotification
{
    /**
     * Notify the orderer and the material responsibles about the transitions that are
     * configured to send a mail.
     */
    public function handle(StateChanged $event): void
    {
        if (! $event->model instanceof Order) {
            return;
        }

        $initialState = $event->initialState;
        $finalState = $event->finalState;

        if ($initialState === null || $finalState === null || ! app(ComponentManager::class)->isEnabled('order_notifications')) {
            return;
        }

        if (app(OrderWorkflow::class)->sendsMail($initialState::getMorphClass(), $finalState::getMorphClass())) {
            SendOrderStatusNotification::dispatch($event->model, $finalState::getMorphClass());
        }
    }
}
