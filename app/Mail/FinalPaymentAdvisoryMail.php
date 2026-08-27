<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Headers;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class FinalPaymentAdvisoryMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $fromAddress;

    public function __construct(
        public ?string $recipientName = null,
        public ?string $dispatchRef = null,
        public ?string $fromName = null,
    ) {
        $this->fromAddress = (string) config('mail.from.address');

        $this->withSymfonyMessage(function (\Symfony\Component\Mime\Email $message): void {
            $message->embedFromPath($this->logoPath(), 'amis-logo', 'image/png');
            $message->embedFromPath($this->pageOnePreviewPath(), 'advisory-page-1', 'image/jpeg');
            $message->embedFromPath($this->pageTwoPreviewPath(), 'advisory-page-2', 'image/jpeg');
        });
    }

    public function envelope(): Envelope
    {
        $name = mb_strtoupper(trim((string) $this->recipientName));
        $name = $name !== '' ? $name : 'VALUED FAMILY';
        $reference = $this->dispatchRef ? " [{$this->dispatchRef}]" : '';

        return new Envelope(
            from: new Address($this->fromAddress, $this->fromName ?: 'Al Munawwara Islamic School'),
            replyTo: [new Address('amisfinance2324@gmail.com', 'AMIS Finance')],
            subject: "ADVISORY — Final Payment Reminder Before the First Term Examination — {$name}{$reference}",
        );
    }

    public function headers(): Headers
    {
        $domain = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'amis.edu.ph';

        return new Headers(
            messageId: 'final-advisory.'.Str::uuid()."@{$domain}",
            references: [],
            text: [
                'X-Entity-Ref-ID' => (string) Str::uuid(),
                'X-AMIS-Advisory-ID' => $this->dispatchRef ?: (string) Str::uuid(),
            ],
        );
    }

    public function content(): Content
    {
        return new Content(htmlString: $this->htmlBody());
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pageOneOriginalPath())
                ->as('AMIS-Advisory-SM-2627-018-Page-1.png')
                ->withMime('image/png'),
            Attachment::fromPath($this->pageTwoOriginalPath())
                ->as('AMIS-Advisory-SM-2627-018-Page-2.jpg')
                ->withMime('image/jpeg'),
        ];
    }

    private function htmlBody(): string
    {
        return <<<'HTML'
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Final Payment Reminder Before the First Term Examination</title>
</head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
  <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f1f5f9;padding:24px 12px;">
    <tr><td align="center">
      <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:680px;background:#ffffff;border:1px solid #dbe5df;border-radius:14px;overflow:hidden;">
        <tr>
          <td style="background:#086b2b;padding:24px 28px;color:#ffffff;text-align:center;">
            <img src="cid:amis-logo" alt="AMIS Logo" width="82" height="82" style="display:inline-block;width:82px;height:82px;border-radius:50%;background:#ffffff;padding:4px;margin:0 0 10px;">
            <div dir="rtl" style="font-family:Tahoma,Arial,sans-serif;font-size:21px;font-weight:700;line-height:1.45;color:#ffffff;">المدرسة المنورة الإسلامية</div>
            <div style="font-size:14px;font-weight:800;letter-spacing:1.3px;color:#bbf7d0;margin-top:2px;">AL MUNAWWARA ISLAMIC SCHOOL</div>
            <div style="font-size:22px;font-weight:800;margin-top:14px;line-height:1.25;">FINAL PAYMENT REMINDER</div>
            <div style="font-size:14px;font-weight:600;margin-top:5px;color:#dcfce7;">Before the First Term Examination</div>
          </td>
        </tr>
        <tr>
          <td style="padding:28px;font-size:15px;line-height:1.7;">
            <p style="margin:0 0 18px;font-weight:700;">Dear Parents and Students,</p>
            <p style="margin:0 0 18px;">Assalaamu alaikum wa rahmatullahi wa barakatuh.</p>
            <p style="margin:0 0 18px;">As the First Term Examination is approaching, we humbly remind everyone with outstanding school balances to settle their accounts before the examination dates.</p>
            <p style="margin:0 0 18px;">For those who have already made their payment but have not yet received an updated Statement of Account, please follow up with the Finance at <a href="mailto:amisfinance2324@gmail.com" style="color:#08762e;font-weight:700;">amisfinance2324@gmail.com</a> or visit the Admin Office.</p>
            <p style="margin:0 0 18px;">Please see the attached advisory for complete details regarding the payment requirements, examination permits, and promissory letter arrangements.</p>
            <p style="margin:0 0 18px;">We highly encourage all parents and students to read the advisory carefully and take the necessary action before the examination.</p>
            <p style="margin:24px 0 0;">Shukran. BarakAllahu feekum.</p>
            <p style="margin:18px 0 0;font-weight:700;">— School Administration</p>
          </td>
        </tr>
        <tr>
          <td style="padding:4px 20px 24px;background:#ffffff;text-align:center;">
            <div style="font-size:16px;font-weight:800;color:#086b2b;margin:0 0 14px;">Payment Advisory</div>
            <img src="cid:advisory-page-1" alt="Payment Advisory Page 1" width="624" style="display:block;width:100%;max-width:624px;height:auto;margin:0 auto 18px;border:1px solid #d1d5db;border-radius:8px;">
            <img src="cid:advisory-page-2" alt="Payment Advisory Page 2" width="624" style="display:block;width:100%;max-width:624px;height:auto;margin:0 auto;border:1px solid #d1d5db;border-radius:8px;">
          </td>
        </tr>
        <tr>
          <td style="background:#f0fdf4;border-top:1px solid #d1fae5;padding:16px 28px;text-align:center;color:#4b5563;font-size:12px;line-height:1.5;">
            The two-page payment advisory is included as downloadable attachments.<br>
            For payment concerns, contact <a href="mailto:amisfinance2324@gmail.com" style="color:#08762e;">amisfinance2324@gmail.com</a>.
          </td>
        </tr>
      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    private function assetDirectory(): string
    {
        return storage_path('app/private/payment-advisory-20260827');
    }

    private function logoPath(): string
    {
        return public_path('images/AMIS_Logo_email.png');
    }

    private function pageOneOriginalPath(): string
    {
        return $this->assetDirectory().'/MEMORANDUM1.png';
    }

    private function pageTwoOriginalPath(): string
    {
        return $this->assetDirectory().'/MEMORANDUM2.jpg';
    }

    private function pageOnePreviewPath(): string
    {
        return $this->assetDirectory().'/MEMORANDUM1_PREVIEW.jpg';
    }

    private function pageTwoPreviewPath(): string
    {
        return $this->assetDirectory().'/MEMORANDUM2_PREVIEW.jpg';
    }
}
