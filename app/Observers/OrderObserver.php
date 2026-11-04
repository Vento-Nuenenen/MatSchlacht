<?php

namespace App\Observers;

use App\Mail\OrderCreated;
use App\Models\Order;
use App\Models\User;
use App\Services\ComponentManager;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Role;

class OrderObserver
{
    /**
     * Notify the members of the ordering unit (or the orderer as fallback) about a new order.
     * The mail is queued, so a failing mail transport never breaks the request.
     */
    public function created(Order $order): void
    {
        $components = app(ComponentManager::class);

        if (! $components->isEnabled('order_notifications') || ! $components->setting('order_notifications', 'notify_on_created')) {
            return;
        }

        $role = Role::find($order->role_id);
        $recipients = $role ? User::role($role->name)->get() : collect();

        if ($recipients->isEmpty() && $order->user) {
            $recipients = collect([$order->user]);
        }

        foreach ($recipients as $user) {
            if (! empty($user->email)) {
                Mail::to($user)->queue(new OrderCreated($order));
            }
        }
    }
}
