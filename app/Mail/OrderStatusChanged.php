<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderStatusChanged extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Order $order,
        public string $statusName
    ) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = match ($this->statusName) {
            'committed' => 'Bestellung eingereicht: '.$this->order->event,
            'accepted' => 'Bestellung bestätigt: '.$this->order->event,
        };

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = match ($this->statusName) {
            'committed' => 'mail.order-committed',
            'accepted' => 'mail.order-accepted',
        };

        return new Content(
            view: $view,
            with: [
                'order' => $this->order,
                'statusName' => $this->statusName,
                'role' => $this->order->role,
                'user' => $this->order->user,
                'items' => $this->order->orderedItems()->with('item')->get(),
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
