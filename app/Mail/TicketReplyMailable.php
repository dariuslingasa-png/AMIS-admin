<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TicketReplyMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $replySubject,
        public string $replyMessage,
        public string $recipientName,
        public string $referenceNumber,
        public ?string $attachmentPath = null
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "[{$this->referenceNumber}] {$this->replySubject}",
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.ticket-reply',
        );
    }

    public function attachments(): array
    {
        if ($this->attachmentPath && file_exists($this->attachmentPath)) {
            return [
                Attachment::fromPath($this->attachmentPath),
            ];
        }

        return [];
    }
}
