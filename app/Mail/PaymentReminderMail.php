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

    public function __construct()
    {
        $this->image1Path = public_path('images/reminder/image1_due_soon.png');
        if (!file_exists($this->image1Path)) {
            $this->image1Path = public_path('images/reminder/poster_payment_reminder.png');
        }

        $this->image2Path = public_path('images/reminder/image2_payment_info.png');
        if (!file_exists($this->image2Path)) {
            $this->image2Path = public_path('images/reminder/poster_payment_info.png');
        }

        $this->image3Path = public_path('images/reminder/image3_automated_reminder.jpg');
        if (!file_exists($this->image3Path)) {
            $this->image3Path = public_path('images/reminder/banner_already_paid.jpg');
        }

        $this->logoPath = public_path('images/AMIS_Logo.png');
    }

    /**
     * Get the message envelope.
     * Uses dedicated REMINDER_MAIL_FROM_NAME ("AMIS Support Staff") and REMINDER_MAIL_FROM_ADDRESS
     */
    public function envelope(): Envelope
    {
        $fromName    = env('REMINDER_MAIL_FROM_NAME', 'AMIS Support Staff');
        $fromAddress = env('REMINDER_MAIL_FROM_ADDRESS', config('mail.from.address', 'amisonlinesupport@gmail.com'));

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
                'image1Path' => $this->image1Path,
                'image2Path' => $this->image2Path,
                'image3Path' => $this->image3Path,
                'logoPath'   => $this->logoPath,
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
