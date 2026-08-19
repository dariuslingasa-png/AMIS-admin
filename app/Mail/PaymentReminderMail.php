<?php

namespace App\Mail;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PaymentReminderMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $image1Path;
    public string $image2Path;
    public string $image3Path;
    public string $logoPath;

    public function __construct(
        public ?string $recipientName = null,
        public ?string $billingMonth = null,
        public ?string $dispatchRef = null,
    ) {
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

        // Explicitly purge any threading headers from the Symfony MIME message
        $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message) {
            $headers = $message->getHeaders();
            if ($headers->has('In-Reply-To')) {
                $headers->remove('In-Reply-To');
            }
            if ($headers->has('References')) {
                $headers->remove('References');
            }
            if ($headers->has('Thread-Topic')) {
                $headers->remove('Thread-Topic');
            }
            if ($headers->has('Thread-Index')) {
                $headers->remove('Thread-Index');
            }
        });
    }

    /**
     * Build the unique recipient-specific subject line to prevent Gmail conversation threading.
     * Format: AMIS Payment Reminder – Monthly School Fees – [Family/Student Name] – [Month Year]
     * Example: AMIS Payment Reminder – Monthly School Fees – ABDULRAHEEM BAULO – August 2026
     */
    public function resolveSubject(): string
    {
        $name = trim((string) $this->recipientName);
        if (empty($name)) {
            $name = 'Valued Family';
        } else {
            $name = mb_strtoupper($name);
        }

        $monthYear = null;
        if (!empty($this->billingMonth)) {
            try {
                $rawMonth = trim($this->billingMonth);
                $dateStr = strlen($rawMonth) === 7 ? $rawMonth . '-01' : $rawMonth;
                $monthYear = Carbon::parse($dateStr)->format('F Y');
            } catch (\Throwable) {
                $monthYear = $this->billingMonth;
            }
        }
        if (empty($monthYear)) {
            $monthYear = Carbon::now()->format('F Y');
        }

        $refSuffix = !empty($this->dispatchRef) ? " [{$this->dispatchRef}]" : '';

        return "AMIS Payment Reminder – Monthly School Fees – {$name} – {$monthYear}{$refSuffix}";
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
            subject: $this->resolveSubject(),
        );
    }

    /**
     * Configure unique message headers to strictly prevent Gmail conversation grouping.
     * Guarantees:
     * 1. Unique Message-ID for every single email sent.
     * 2. Empty references array (no In-Reply-To or References header).
     * 3. Unique X-Entity-Ref-ID and X-AMIS-Reminder-ID tracking headers.
     */
    public function headers(): Headers
    {
        $uniqueToken = bin2hex(random_bytes(16)) . '.' . microtime(true);
        $domain = parse_url(config('app.url', 'http://amis.edu.ph'), PHP_URL_HOST) ?: 'amis.edu.ph';

        return new Headers(
            messageId: "reminder.{$uniqueToken}@{$domain}",
            references: [],
            text: [
                'X-Entity-Ref-ID'    => (string) Str::uuid(),
                'X-AMIS-Reminder-ID' => (string) Str::uuid(),
            ],
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
                'image1Path'    => $this->image1Path,
                'image2Path'    => $this->image2Path,
                'image3Path'    => $this->image3Path,
                'logoPath'      => $this->logoPath,
                'recipientName' => $this->recipientName,
                'billingMonth'  => $this->billingMonth,
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
