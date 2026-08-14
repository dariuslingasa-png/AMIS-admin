<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Family Payment Receipt</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,'Segoe UI',sans-serif;color:#0f172a;">
@php
    $familyReceiptNumber = \App\Services\Finance\FamilyPaymentReceiptService::numberFor($transaction);
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;overflow:hidden;border:1px solid #dbe5e1;border-radius:18px;background:#ffffff;box-shadow:0 8px 28px rgba(15,23,42,.08);">
        <tr>
            <td style="padding:30px 36px;text-align:center;background:#047857;">
                @if(file_exists(public_path('images/AMIS_Logo_email.png')))
                    <img src="cid:amis-logo@amis.edu.ph" width="62" height="62" alt="AMIS Logo" style="display:inline-block;width:62px;height:62px;margin:0 0 12px;border:3px solid rgba(255,255,255,.88);border-radius:50%;background:#ffffff;object-fit:contain;">
                @endif
                <p style="margin:0 0 8px;color:#a7f3d0;font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;">Al Munawwara Islamic School</p>
                <h1 style="margin:0;color:#ffffff;font-size:26px;line-height:1.2;font-weight:900;">PAYMENT APPROVED</h1>
            </td>
        </tr>
        <tr>
            <td style="padding:32px 36px;">
                <p style="margin:0 0 8px;color:#047857;font-size:16px;font-weight:800;">Assalamu Alaikum,</p>
                <p style="margin:0 0 20px;color:#475569;font-size:14px;line-height:1.7;">Dear <strong style="color:#0f172a;">{{ strtoupper($transaction->family?->name ?: 'Parent / Guardian') }}</strong>, AMIS Support Staff has verified and approved your family payment.</p>

                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:22px;border:1px solid #a7f3d0;border-radius:14px;background:#ecfdf5;">
                    <tr>
                        <td style="padding:18px 20px;">
                            <p style="margin:0;color:#047857;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;">Family Payment Receipt</p>
                            <p style="margin:5px 0 0;color:#065f46;font-size:18px;font-weight:900;">{{ $familyReceiptNumber }}</p>
                        </td>
                        <td align="right" style="padding:18px 20px;">
                            <p style="margin:0;color:#047857;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;">Amount Received</p>
                            <p style="margin:5px 0 0;color:#065f46;font-size:22px;font-weight:900;">₱{{ number_format((float) $transaction->amount, 2) }}</p>
                        </td>
                    </tr>
                </table>

                <p style="margin:0;color:#334155;font-size:14px;line-height:1.7;"><strong>Your official Family Payment Receipt is attached as a PDF.</strong> It contains the billing month, amount due, amount paid, remaining balance, and payment status for every affected child.</p>

                <p style="margin:20px 0 0;padding:14px 16px;border-radius:10px;background:#f8fafc;color:#64748b;font-size:12px;line-height:1.65;">If any payment detail, student information, allocation, or balance looks incorrect, please contact <strong style="color:#0f172a;">AMIS Support Staff</strong> for verification and correction.</p>
            </td>
        </tr>
        <tr><td style="padding:18px 36px;border-top:1px solid #e2e8f0;background:#f8fafc;text-align:center;color:#94a3b8;font-size:11px;">System-generated notification from AMIS Support Staff. No manual signature is required.</td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
