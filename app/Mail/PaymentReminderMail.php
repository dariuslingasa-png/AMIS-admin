<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct()
    {
        // No parameters required
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(config('mail.from.address'), config('mail.from.name', 'Al Munawwara Islamic School')),
            subject: 'Monthly Payment Reminder - Al Munawwara Islamic School',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_reminder',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
