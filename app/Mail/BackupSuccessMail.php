<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class BackupSuccessMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $filename,
        public string $formattedSize,
        public float $executionTime,
        public string $timestamp,
        public array $includedItems = []
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✅ AMIS Portal - Automated System Backup SUCCESS',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.backup-success',
        );
    }
}
