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

    public string $image1Path;
    public string $image2Path;
    public string $image3Path;
    public string $logoPath;
    public ?string $parentName;
    public ?string $studentNames;

    public function __construct(?string $parentName = null, ?string $studentNames = null)
    {
        $this->parentName = $parentName;
        $this->studentNames = $studentNames;

        $this->image1Path = public_path('images/reminder/poster_payment_reminder.png');
        if (!file_exists($this->image1Path)) {
            $this->image1Path = public_path('images/reminder/poster_payment_reminder.jpg');
        }

        $this->image2Path = public_path('images/reminder/poster_payment_info.png');
        $this->image3Path = public_path('images/reminder/banner_already_paid.jpg');
        $this->logoPath   = public_path('images/AMIS_Logo.png');
    }

    /**
     * Get the message envelope.
     * Uses dedicated REMINDER_MAIL_FROM_NAME ("AMIS Support Staff") so other system emails are untouched.
     */
    public function envelope(): Envelope
    {
        $fromName    = env('REMINDER_MAIL_FROM_NAME', 'AMIS Support Staff');
        $fromAddress = env('REMINDER_MAIL_FROM_ADDRESS', config('mail.from.address'));

        return new Envelope(
            from: new Address($fromAddress, $fromName),
            subject: 'AMIS Payment Reminder – Monthly School Fees',
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.payment_reminder',
            with: [
                'image1Path'   => $this->image1Path,
                'image2Path'   => $this->image2Path,
                'image3Path'   => $this->image3Path,
                'logoPath'     => $this->logoPath,
                'parentName'   => $this->parentName,
                'studentNames' => $this->studentNames,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
