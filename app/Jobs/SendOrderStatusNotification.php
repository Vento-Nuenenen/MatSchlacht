<?php

namespace App\Jobs;

use App\Mail\OrderStatusChanged;
use App\Models\Order;
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

        // Add material responsibles
        $responsibles = env('MAIL_MATERIAL_RESPONSIBLES');
        if ($responsibles) {
            foreach (explode(',', $responsibles) as $email) {
                $recipients->push(trim($email));
            }
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
