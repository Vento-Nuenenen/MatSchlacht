<?php

namespace App\Observers;

use App\Jobs\SendOrderStatusNotification;
use App\Mail\OrderCreated;
use App\Models\Order;
use App\Models\User;
use App\States\Order\Accepted;
use App\States\Order\Committed;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class OrderObserver
{
    /**
     * Handle the Order "created" event.
     */
    public function created(Order $order): void
    {
        // Only send if the order is already committed upon creation (unlikely but possible)
        // or if we want to notify about ANY new order creation (e.g. Stored state).
        // The original code was in OrderController::store, so we'll mirror that logic here.
        $this->notifyRecipients($order);
    }

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

    /**
     * Notify relevant users about the order.
     */
    protected function notifyRecipients(Order $order): void
    {
        try {
            $role = Role::find($order->role_id);
            $recipients = $role ? User::role($role->name)->get() : collect();

            if ($recipients->isEmpty() && $order->user) {
                $recipients = collect([$order->user]);
            }

            foreach ($recipients as $user) {
                if (! empty($user->email)) {
                    Mail::to($user->email)->send(new OrderCreated($order));
                }
            }
        } catch (\Throwable $e) {
            \Log::error($e);
        }
    }
}
