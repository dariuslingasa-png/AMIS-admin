<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AMIS Student Payment Update</title>
</head>
<body style="margin:0;padding:24px;background:#f1f5f9;font-family:Arial,'Segoe UI',sans-serif;color:#0f172a;">
@php
    $allocationRows = collect($transaction->allocation_snapshot);
    $settledByStudent = $allocationRows
        ->filter(fn ($row) => (float) ($row['remaining_after'] ?? 0) <= 0)
        ->groupBy('student_id');
    $studentsById = $transaction->allocations->pluck('student')->filter()->keyBy('id');
@endphp
<table role="presentation" width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;overflow:hidden;border:1px solid #dbe5e1;border-radius:18px;background:#ffffff;box-shadow:0 8px 28px rgba(15,23,42,.08);">
        <tr><td style="padding:32px 36px;text-align:center;background:#047857;">
            @if(file_exists(public_path('images/AMIS_Logo_email.png')))
                <img src="cid:amis-logo@amis.edu.ph" width="64" height="64" alt="AMIS Logo" style="display:inline-block;width:64px;height:64px;margin:0 0 13px;border:3px solid rgba(255,255,255,.85);border-radius:50%;background:#ffffff;object-fit:contain;">
            @endif
            <p style="margin:0 0 8px;color:#a7f3d0;font-size:12px;font-weight:800;letter-spacing:.14em;text-transform:uppercase;">Al Munawwara Islamic School</p>
            <h1 style="margin:0;color:#ffffff;font-size:25px;line-height:1.25;font-weight:900;">STUDENT PAYMENT SETTLED</h1>
            <p style="margin:9px 0 0;color:#d1fae5;font-size:13px;font-weight:800;">Billing status confirmed by AMIS Support Staff</p>
        </td></tr>
        <tr><td style="padding:32px 36px;">
            <p style="margin:0 0 8px;color:#047857;font-size:16px;font-weight:800;">Assalamu Alaikum, Class Adviser</p>
            <p style="margin:0;color:#475569;font-size:14px;line-height:1.65;">The following student billing {{ Str::plural('record', $settledByStudent->count()) }} {{ $settledByStudent->count() === 1 ? 'is' : 'are' }} now fully settled.</p>

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:22px 0 14px;border-collapse:collapse;">
                <tr>
                    <td style="padding-bottom:8px;color:#64748b;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;">Student</td>
                    <td align="right" style="padding-bottom:8px;color:#64748b;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;">Payment status</td>
                </tr>
            </table>

            @foreach($settledByStudent as $studentId => $studentRows)
                @php
                    $student = $studentsById->get((int) $studentId);
                    $studentName = $student?->applicant?->full_name ?: $student?->user?->name ?: 'Student';
                    $months = $studentRows->pluck('billing_month')->filter()->map(fn ($month) => strtoupper((string) $month))->unique()->implode(', ');
                @endphp
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;border:1px solid #a7f3d0;border-radius:12px;background:#ecfdf5;">
                    <tr>
                        <td style="padding:16px 18px;">
                            <p style="margin:0;color:#0f172a;font-size:15px;font-weight:900;">{{ strtoupper($studentName) }}</p>
                            <p style="margin:6px 0 0;color:#64748b;font-size:12px;font-weight:700;">{{ strtoupper($student?->grade_level ?: 'Grade not recorded') }} · {{ $months ?: 'BILLING' }}</p>
                        </td>
                        <td align="right" style="padding:16px 18px;color:#047857;font-size:12px;font-weight:900;white-space:nowrap;">SETTLED</td>
                    </tr>
                </table>
            @endforeach

            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;border-radius:12px;background:#f8fafc;">
                <tr><td style="padding:17px;color:#475569;font-size:13px;"><strong>Updated:</strong> {{ now()->format('F d, Y · h:i A') }}</td></tr>
            </table>

            <p style="margin:22px 0 0;color:#64748b;font-size:12px;line-height:1.65;">No action is required. This adviser notice contains only fully settled student billing records. Parent contact details, siblings, partial allocations, payment amount, family balance, receipt image, and reference remain private.</p>
        </td></tr>
        <tr><td style="padding:20px 36px;border-top:1px solid #e2e8f0;background:#f8fafc;text-align:center;color:#94a3b8;font-size:11px;">Sent automatically by AMIS Support Staff. Please do not reply to this email.</td></tr>
    </table>
</td></tr>
</table>
</body>
</html>
