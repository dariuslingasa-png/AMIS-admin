<?php

namespace App\Mail;

use App\Models\FinanceTransaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class FinancePaymentApprovedAdviserMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public FinanceTransaction $transaction) {}

    public function build(): static
    {
        $logoPath = public_path('images/AMIS_Logo_email.png');
        if (is_file($logoPath)) {
            $this->withSymfonyMessage(function (Email $message) use ($logoPath): void {
                $logo = DataPart::fromPath($logoPath, 'AMIS_Logo.png', 'image/png')
                    ->asInline()
                    ->setContentId('amis-logo@amis.edu.ph');
                $message->addPart($logo);
            });
        }

        return $this;
    }

    public function envelope(): Envelope
    {
        $studentNames = collect($this->transaction->allocation_snapshot)
            ->filter(fn ($row) => (float) ($row['remaining_after'] ?? 0) <= 0)
            ->pluck('student_name')
            ->filter()
            ->unique()
            ->take(2)
            ->implode(', ');

        return new Envelope(
            from: new Address(config('mail.from.address'), 'AMIS Support Staff'),
            subject: 'AMIS STUDENT PAYMENT UPDATE'.($studentNames !== '' ? ' — '.$studentNames : ''),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.finance-payment-approved-adviser',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
