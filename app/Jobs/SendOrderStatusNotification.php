<?php

namespace App\Jobs;

use App\Mail\OrderStatusChanged;
use App\Models\Order;
use App\Services\ComponentManager;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrderStatusNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Order $order,
        public string $statusName
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $recipients = collect();

        // Add ordering user
        if ($this->order->user && ! empty($this->order->user->email)) {
            $recipients->push($this->order->user->email);
        }

        // Add material responsibles: settings first, then MAIL_MATERIAL_RESPONSIBLES
        $responsibles = app(ComponentManager::class)->settingList('order_notifications', 'material_responsibles');
        $configuredResponsibles = config('mail.material_responsibles');
        if (empty($responsibles) && is_string($configuredResponsibles)) {
            $responsibles = array_filter(array_map('trim', explode(',', $configuredResponsibles)));
        }

        if (! empty($responsibles)) {
            $recipients->push(...$responsibles);
        } else {
            // Fallback to config from address if no responsibles are set
            $recipients->push(config('mail.from.address'));
        }

        $recipients = $recipients->unique()->filter();

        if ($recipients->isNotEmpty()) {
            Mail::to($recipients)->send(new OrderStatusChanged($this->order, $this->statusName));
        }
    }
}
