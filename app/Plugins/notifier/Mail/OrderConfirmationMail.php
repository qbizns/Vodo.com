<?php

declare(strict_types=1);

namespace App\Plugins\Notifier\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderConfirmationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying.
     */
    public int $backoff = 30;

    /**
     * Email data (order details, discount info, etc.)
     */
    public array $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Order Confirmation - ' . ($this->data['order_number'] ?? 'Order'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'notifier::order-confirmation',
            with: ['data' => $this->data],
        );
    }
}
