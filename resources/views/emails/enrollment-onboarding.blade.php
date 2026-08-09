<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome to AMIS! Official Student Microsoft 365 Account Credentials</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f3f4f6; margin: 0; padding: 20px;">
<table width="100%" cellpadding="0" cellspacing="0">
<tr><td align="center">
<table width="580" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 14px rgba(0,0,0,0.08);border:1px solid #e5e7eb;">
    
    <!-- HEADER -->
    <tr><td style="background:linear-gradient(135deg,#059669,#047857);padding:36px 40px;text-align:center;">
        <img src="{{ asset('images/AMIS_Logo.png') }}" alt="AMIS" width="68" height="68" style="margin-bottom:14px;border-radius:12px;">
        <h1 style="color:#ffffff;font-size:22px;margin:0 0 4px;font-weight:800;font-family:'Segoe UI',Arial,sans-serif;">Al Munawwara Islamic School</h1>
        <p style="color:rgba(255,255,255,0.9);font-size:13px;margin:0;font-weight:500;">Office of Admissions & Student Registry</p>
    </td></tr>

    <!-- BODY -->
    <tr><td style="padding:36px 40px;">
        <p style="font-size:16px;font-weight:700;color:#059669;margin:0 0 8px;">Assalamu Alaikum Wa Rahmatullah Wa Barakatuh,</p>
        <p style="font-size:14px;color:#374151;margin:0 0 20px;">Dear Parent / Guardian of <strong>{{ $studentName }}</strong>,</p>
        
        <p style="font-size:14px;color:#374151;margin:0 0 24px;line-height:1.7;">
            We are pleased to inform you that the enrollment application for <strong>{{ $studentName }}</strong> (Grade Level: <strong>{{ $student->grade_level }}</strong>) has been 
            <span style="color:#059669;font-weight:700;background:#dcfce7;padding:3px 8px;border-radius:6px;">APPROVED</span> for <strong>School Year {{ $applicant->school_year }}</strong>.
        </p>

        <!-- CREDENTIALS BOX -->
        <div style="background:#f0fdf4;border:1.5px solid #bbf7d0;border-radius:14px;padding:22px 26px;margin-bottom:24px;">
            <p style="font-size:13px;font-weight:800;color:#065f46;margin:0 0 14px;text-transform:uppercase;letter-spacing:0.05em;">
                🔑 OFFICIAL STUDENT ACCOUNTS & CREDENTIALS
            </p>
            <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                <tr>
                    <td style="padding:8px 0;font-size:13px;color:#4b5563;width:150px;font-weight:600;">Student Name:</td>
                    <td style="padding:8px 0;font-size:14px;font-weight:800;color:#111827;">{{ $studentName }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-size:13px;color:#4b5563;font-weight:600;">Student ID:</td>
                    <td style="padding:8px 0;font-size:14px;font-weight:800;color:#059669;">{{ $student->student_number }}</td>
                </tr>
                <tr>
                    <td style="padding:8px 0;font-size:13px;color:#4b5563;font-weight:600;">M365 Email:</td>
                    <td style="padding:8px 0;font-size:14px;font-weight:700;color:#0369a1;font-family:monospace;">{{ $student->school_email }}</td>
                </tr>
                @if ($msError)
                    <tr>
                        <td style="padding:8px 0;font-size:13px;color:#4b5563;font-weight:600;">Temporary Pass:</td>
                        <td style="padding:8px 0;">
                            <span style="font-size:13px;font-weight:700;color:#92400e;background:#fef3c7;padding:5px 10px;border-radius:6px;">Pending school account reset</span>
                        </td>
                    </tr>
                @else
                    <tr>
                        <td style="padding:8px 0;font-size:13px;color:#4b5563;font-weight:600;">Temporary Pass:</td>
                        <td style="padding:8px 0;">
                            <span style="font-size:14px;font-weight:800;color:#1e293b;font-family:monospace;background:#fef08a;padding:5px 10px;border-radius:6px;letter-spacing:0.05em;border:1px solid #fde047;">{{ $tempPassword }}</span>
                        </td>
                    </tr>
                @endif
            </table>
        </div>

        <!-- LOGIN INSTRUCTIONS -->
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:20px;margin-bottom:24px;">
            <p style="font-size:14px;font-weight:800;color:#1e293b;margin:0 0 10px;">📌 INSTRUCTIONS TO LOG IN:</p>
            <ol style="font-size:13px;color:#334155;margin:0;padding-left:20px;line-height:1.8;">
                <li style="margin-bottom:8px;"><strong>Microsoft 365 Portal:</strong> Go to <a href="https://portal.office.com" target="_blank" style="color:#0284c7;font-weight:700;text-decoration:underline;">https://portal.office.com</a> and log in using the email & password above. You will be prompted to set a new password on your first login.</li>
                <li><strong>AMIS Student Portal:</strong> You can also use this email to log in directly at <a href="https://aes.amis.edu.ph/login" target="_blank" style="color:#0284c7;font-weight:700;text-decoration:underline;">https://aes.amis.edu.ph/login</a> via <strong>"Sign in with Microsoft"</strong>.</li>
            </ol>
        </div>

        <!-- TEAMS VIDEO TUTORIAL CARD -->
        <div style="background:#eff6ff;border:1.5px solid #bfdbfe;border-radius:14px;padding:20px 24px;margin-bottom:24px;">
            <p style="font-size:14px;font-weight:800;color:#1e40af;margin:0 0 6px;">🎥 TEAMS APP VIDEO TUTORIAL GUIDE</p>
            <p style="font-size:13px;color:#334155;margin:0 0 14px;line-height:1.6;">Need help logging in or navigating Microsoft Teams for online classes? Watch our step-by-step video walkthrough:</p>
            <div style="margin-bottom:12px;">
                <a href="https://admin.amis.edu.ph/tutorial/Team%20App%20Tutorial.mp4" target="_blank" style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;border-radius:999px;padding:11px 24px;font-size:13px;font-weight:700;box-shadow:0 4px 10px rgba(37,99,235,0.25);">
                    ▶️ Watch Teams App Video Tutorial
                </a>
            </div>
            <p style="font-size:12px;color:#64748b;margin:0;line-height:1.5;">Download Teams App: 
                <a href="https://www.microsoft.com/en-us/microsoft-teams/download-app" target="_blank" style="color:#2563eb;font-weight:600;">Desktop</a> | 
                <a href="https://apps.apple.com/app/microsoft-teams/id1113153706" target="_blank" style="color:#2563eb;font-weight:600;">iOS (iPhone)</a> | 
                <a href="https://play.google.com/store/apps/details?id=com.microsoft.teams" target="_blank" style="color:#2563eb;font-weight:600;">Android</a>
            </p>
        </div>

        <!-- NEED HELP / FACEBOOK CARD -->
        <div style="background:#f0fdfa;border:1.5px solid #99f6e4;border-radius:14px;padding:20px 24px;margin-bottom:24px;">
            <p style="font-size:14px;font-weight:800;color:#0f766e;margin:0 0 6px;">💬 NEED HELP OR HAVE QUESTIONS?</p>
            <p style="font-size:13px;color:#334155;margin:0 0 14px;line-height:1.6;">For technical support, account concerns, or enrollment assistance, message <strong>Sir Mohaymen Unos</strong> directly on Facebook:</p>
            <a href="https://web.facebook.com/sirmo.amis" target="_blank" rel="noopener" style="display:inline-block;background:#0d9488;color:#ffffff;text-decoration:none;border-radius:999px;padding:11px 24px;font-size:13px;font-weight:700;box-shadow:0 4px 10px rgba(13,148,136,0.25);">
                🔵 Message Sir Mohaymen on Facebook
            </a>
        </div>

        <!-- SIGN OFF -->
        <p style="font-size:14px;color:#374151;margin:28px 0 0;line-height:1.7;">May Allah bless your student's learning journey at Al Munawwara Islamic School.</p>
        
        <div style="margin-top:20px;padding-top:16px;border-top:1px solid #f1f5f9;">
            <p style="font-size:14px;color:#1e293b;margin:0;font-weight:700;">Warm regards,</p>
            <p style="font-size:14px;color:#059669;margin:2px 0 0;font-weight:800;">Al Munawwara Islamic School (AMIS)</p>
            <p style="font-size:13px;color:#64748b;margin:2px 0 0;font-weight:600;">- IT Staff Mon Zhairel</p>
        </div>
    </td></tr>

    <!-- FOOTER -->
    <tr><td style="background:#f8fafc;padding:20px 40px;text-align:center;border-top:1px solid #e2e8f0;">
        <p style="color:#cbd5e1;font-size:11px;margin:0;font-style:italic;">[ This is an automated system-generated email ]</p>
    </td></tr>

</table>
</td></tr>
</table>
</body>
</html>
