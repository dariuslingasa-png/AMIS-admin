<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupFailedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $errorMsg,
        public float $executionTime
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '⚠️ AMIS Portal - Automated Backup Process FAILED',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backup-failed',
        );
    }
}
