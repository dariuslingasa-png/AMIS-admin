<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $customSubject }}</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            margin: 0;
            padding: 24px 12px;
            -webkit-font-smoothing: antialiased;
        }
        .email-wrapper {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 24px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 10px 25px -5px rgba(15, 23, 42, 0.08);
        }
        .email-header {
            background: linear-gradient(135deg, #022c22 0%, #064e3b 50%, #0f172a 100%);
            padding: 32px 28px;
            text-align: center;
            color: #ffffff;
            border-bottom: 4px solid #10b981;
        }
        .header-logo {
            width: 72px;
            height: 72px;
            margin: 0 auto 14px auto;
            display: block;
            border-radius: 50%;
            background: #ffffff;
            padding: 4px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
        }
        .header-badge {
            display: inline-block;
            background: rgba(16, 185, 129, 0.2);
            color: #6ee7b7;
            font-size: 11px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            padding: 4px 14px;
            border-radius: 50px;
            border: 1px solid rgba(52, 211, 153, 0.3);
            margin-bottom: 10px;
        }
        .header-title {
            margin: 0;
            font-size: 22px;
            font-weight: 900;
            letter-spacing: -0.02em;
            color: #ffffff;
        }
        .header-subtitle {
            margin: 6px 0 0 0;
            font-size: 12px;
            font-weight: 600;
            color: #a7f3d0;
            letter-spacing: 0.05em;
        }
        .email-body {
            padding: 36px 32px;
            font-size: 15px;
            line-height: 1.75;
            color: #334155;
            background: #ffffff;
        }
        .email-body h1, .email-body h2, .email-body h3 {
            color: #0f172a;
            font-weight: 800;
            margin-top: 0;
        }
        .email-body p {
            margin-bottom: 16px;
        }
        .email-body a {
            color: #059669;
            text-decoration: underline;
            font-weight: 700;
        }
        .email-body img {
            max-width: 100%;
            height: auto;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin: 16px 0;
        }
        .institutional-footer {
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
            padding: 24px 28px;
            text-align: center;
            font-size: 12px;
            color: #64748b;
        }
        .footer-org {
            font-size: 13px;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 4px;
        }
        .footer-dept {
            font-size: 11px;
            font-weight: 700;
            color: #059669;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-bottom: 12px;
        }
        .footer-contact {
            line-height: 1.6;
            margin-bottom: 12px;
        }
        .footer-notice {
            font-size: 11px;
            color: #94a3b8;
            border-top: 1px dashed #cbd5e1;
            padding-top: 12px;
            margin-top: 12px;
        }
    </style>
</head>
<body>
    <div class="email-wrapper">
        <!-- Header -->
        <div class="email-header">
            <img src="https://admin.amis.edu.ph/images/logo.png" alt="AMIS Logo" class="header-logo">
            <div class="header-badge">AMIS Information Technology</div>
            <h1 class="header-title">Al-Munawwara Islamic School</h1>
            <p class="header-subtitle">Official Institutional Communication</p>
        </div>

        <!-- Body Content -->
        <div class="email-body">
            {!! $bodyHtml !!}
        </div>

        <!-- Footer -->
        <div class="institutional-footer">
            <div class="footer-org">Al-Munawwara Islamic School</div>
            <div class="footer-dept">AMIS Information Technology & System Administration</div>
            <div class="footer-contact">
                Bugac Ma-a Road, Davao City, Philippines<br>
                Official Email: <a href="mailto:info@amis.edu.ph" style="color:#059669; text-decoration:none; font-weight:700;">info@amis.edu.ph</a> • Website: <a href="https://amis.edu.ph" style="color:#059669; text-decoration:none; font-weight:700;">amis.edu.ph</a>
            </div>
            <div class="footer-notice">
                This is an automated institutional notification dispatched securely by AMIS IT System. Please do not reply directly to this notice unless instructed.
            </div>
        </div>
    </div>
</body>
</html>
