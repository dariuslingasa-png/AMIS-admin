<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class GenericComposerMailable extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $customSubject,
        public string $bodyHtml,
        public array $attachmentPaths = [],
        public string $senderName = 'AMIS Information Technology',
        public string $senderEmail = 'info@amis.edu.ph',
        public array $ccEmails = [],
        public array $bccEmails = []
    ) {}

    public function envelope(): Envelope
    {
        $ccAddresses = [];
        foreach ($this->ccEmails as $email) {
            if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                $ccAddresses[] = new Address(trim($email));
            }
        }

        $bccAddresses = [];
        foreach ($this->bccEmails as $email) {
            if (filter_var(trim($email), FILTER_VALIDATE_EMAIL)) {
                $bccAddresses[] = new Address(trim($email));
            }
        }

        return new Envelope(
            from: new Address(
                $this->senderEmail ?: (config('mail.from.address') ?: 'info@amis.edu.ph'),
                $this->senderName ?: 'AMIS Information Technology'
            ),
            subject: $this->customSubject,
            cc: $ccAddresses,
            bcc: $bccAddresses,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.composer-generic',
        );
    }

    public function attachments(): array
    {
        $attachments = [];
        foreach ($this->attachmentPaths as $path) {
            if (file_exists($path)) {
                $attachments[] = Attachment::fromPath($path);
            }
        }
        return $attachments;
    }
}
