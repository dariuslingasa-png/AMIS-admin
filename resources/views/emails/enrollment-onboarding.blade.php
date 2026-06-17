<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Inter,Arial,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;padding:40px 20px;">
<tr><td align="center">
<table width="540" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.08);">
    <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:36px 40px;text-align:center;">
        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" width="64" height="64" style="margin-bottom:14px;border-radius:12px;">
        <h1 style="color:#fff;font-size:22px;margin:0 0 4px;font-weight:800;">Al Munawwara Islamic School</h1>
        <p style="color:rgba(255,255,255,0.85);font-size:13px;margin:0;">AMIS Enrollment Office</p>
    </td></tr>
    <tr><td style="padding:36px 40px;">
        <p style="font-size:18px;font-weight:700;color:#059669;margin:0 0 6px;">Assalamualaikum Warahmatullahi Wabarakatuh,</p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;">Dear Parent/Guardian of <strong>{{ $studentName }}</strong>,</p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;line-height:1.7;">
            Alhamdulillah, the enrollment application of your <strong>{{ $genderWord }}</strong>, <strong>{{ $studentName }}</strong>, has been
            <span style="color:#059669;font-weight:700;">officially approved</span> for <strong>School Year {{ $applicant->school_year }}</strong>.
            We warmly welcome {{ $pronoun }} to the AMIS family.
        </p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;line-height:1.7;">Below are the school credentials for Microsoft 365 and the Student Portal:</p>
        <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:20px 24px;margin-bottom:24px;">
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;width:160px;">Student Number</td><td style="padding:7px 0;font-size:15px;font-weight:800;color:#059669;">{{ $student->student_number }}</td></tr>
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">Grade Level</td><td style="padding:7px 0;font-size:14px;font-weight:600;color:#111827;">{{ $student->grade_level }}</td></tr>
                <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">School Email</td><td style="padding:7px 0;font-size:14px;font-weight:600;color:#111827;">{{ $student->school_email }}</td></tr>
                @if ($msError)
                    <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">Password</td><td style="padding:7px 0;font-size:14px;font-weight:700;color:#92400e;background:#fef3c7;padding:4px 8px;border-radius:6px;">Pending school reset</td></tr>
                @else
                    <tr><td style="padding:7px 0;font-size:13px;color:#6b7280;">Temp Password</td><td style="padding:7px 0;font-size:14px;font-weight:700;color:#111827;letter-spacing:0.08em;background:#fef9c3;padding:4px 8px;border-radius:6px;">{{ $tempPassword }}</td></tr>
                @endif
            </table>
        </div>
        <div style="background:#fff7ed;border:1px solid #fed7aa;border-radius:10px;padding:14px 18px;margin-bottom:20px;">
            <p style="font-size:13px;color:#9a3412;margin:0;font-weight:600;">Important reminders:</p>
            <ul style="font-size:13px;color:#9a3412;margin:8px 0 0;padding-left:18px;line-height:1.8;">
                <li>Please change the temporary password upon first login.</li>
                <li>Use the school email to sign in to Microsoft Teams for online classes.</li>
                <li>Keep these credentials safe and do not share them.</li>
            </ul>
        </div>
        <div style="background:#f0f5ff;border:1px solid #bfdbfe;border-radius:12px;padding:18px 22px;margin-bottom:20px;">
            <p style="font-size:14px;font-weight:800;color:#1e40af;margin:0 0 4px;text-transform:uppercase;letter-spacing:0.04em;">📚 For Online Class</p>
            <p style="font-size:12px;color:#374151;margin:0 0 14px;line-height:1.6;">Download Microsoft Teams and sign in with your school email to join online classes.</p>
            <table role="presentation" cellspacing="0" cellpadding="0" border="0" width="100%">
                <tr>
                    <td style="padding:0 0 8px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" valign="middle" style="background:#2563eb;border-radius:8px;">
                                    <a href="https://www.microsoft.com/en-us/microsoft-teams/download-app" target="_blank" style="display:block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:8px;padding:10px 20px;font-size:13px;font-weight:700;font-family:Arial,Helvetica,sans-serif;border:1px solid #2563eb;">
                                        💻 Download for Desktop
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding:0 0 12px;">
                        <table role="presentation" cellspacing="0" cellpadding="0" border="0">
                            <tr>
                                <td align="center" valign="middle" style="background:#111827;border-radius:8px;">
                                    <a href="https://apps.apple.com/app/microsoft-teams/id1113153706" target="_blank" style="display:block;background:#111827;color:#ffffff;text-decoration:none;border-radius:8px;padding:10px 16px;font-size:12px;font-weight:700;font-family:Arial,Helvetica,sans-serif;border:1px solid #111827;">
                                        🍎 App Store (iPhone/iPad)
                                    </a>
                                </td>
                                <td style="width:8px;"></td>
                                <td align="center" valign="middle" style="background:#16a34a;border-radius:8px;">
                                    <a href="https://play.google.com/store/apps/details?id=com.microsoft.teams" target="_blank" style="display:block;background:#16a34a;color:#ffffff;text-decoration:none;border-radius:8px;padding:10px 16px;font-size:12px;font-weight:700;font-family:Arial,Helvetica,sans-serif;border:1px solid #16a34a;">
                                        ▶️ Google Play (Android)
                                    </a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div style="border-top:1px solid #dbeafe;padding-top:12px;">
                <p style="font-size:12px;color:#6b7280;margin:0;line-height:1.6;">
                    Or sign in directly from your browser without downloading an app:<br>
                    <a href="https://portal.office.com" target="_blank" style="color:#2563eb;font-weight:700;font-size:13px;text-decoration:underline;">portal.office.com</a>
                </p>
            </div>
        </div>
        @if ($msError)
            <p style="color:#dc2626;font-size:12px;background:#fff1f2;padding:10px 14px;border-radius:8px;margin-top:12px;">Note: Microsoft account setup is still in progress. The school will notify you once it is ready.</p>
        @endif
        <p style="font-size:14px;color:#374151;margin:24px 0 0;line-height:1.7;">May Allah bless your {{ $genderWord }}'s journey of learning. We look forward to a fruitful school year together.</p>
        <p style="font-size:14px;color:#374151;margin:8px 0 0;font-weight:600;">Wassalamualaikum Warahmatullahi Wabarakatuh.</p>
    </td></tr>
    <tr><td style="background:#f9fafb;padding:20px 40px;text-align:center;border-top:1px solid #e5e7eb;">
        <p style="color:#9ca3af;font-size:11px;margin:0 0 4px;font-weight:600;">Al Munawwara Islamic School</p>
        <p style="color:#9ca3af;font-size:11px;margin:0;">&copy; {{ date('Y') }} All rights reserved.</p>
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
