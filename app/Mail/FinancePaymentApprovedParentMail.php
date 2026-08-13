<?php

namespace App\Mail;

use App\Models\FinanceTransaction;
use App\Services\Finance\FamilyPaymentReceiptService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\Part\DataPart;

class FinancePaymentApprovedParentMail extends Mailable
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
        $receiptNumber = FamilyPaymentReceiptService::numberFor($this->transaction);

        return new Envelope(
            from: new Address(config('mail.from.address'), 'AMIS Support Staff'),
            subject: 'AMIS FAMILY PAYMENT RECEIPT — '.$receiptNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.finance-payment-approved-parent',
        );
    }

    public function attachments(): array
    {
        $receiptNumber = FamilyPaymentReceiptService::numberFor($this->transaction);

        return [
            Attachment::fromData(
                fn () => app(FamilyPaymentReceiptService::class)->render($this->transaction),
                $receiptNumber.'.pdf'
            )->withMime('application/pdf'),
        ];
    }
}
