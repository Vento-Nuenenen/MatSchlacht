<?php

namespace App\Mail;

use App\Models\Item;
use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MaintenanceDueReminder extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  Collection<int, Item>  $items
     */
    public function __construct(public Collection $items) {}

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('Fällige Wartungen: ').$this->items->count().' '.__('Material'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'mail.maintenance-due',
            with: ['items' => $this->items],
        );
    }
}
