<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $customSubject }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 640px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #312e81 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 900; letter-spacing: -0.02em; }
        .header p { margin: 6px 0 0 0; color: #c7d2fe; font-size: 13px; font-weight: 600; }
        .content { padding: 32px 28px; font-size: 15px; line-height: 1.7; color: #334155; }
        .footer { background: #f8fafc; padding: 20px 24px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8; }
        a { color: #4338ca; text-decoration: underline; font-weight: 600; }
        img { max-width: 100%; height: auto; border-radius: 8px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Al-Munawwara Islamic School</h1>
            <p>Official Institutional Communication</p>
        </div>
        <div class="content">
            {!! $bodyHtml !!}
        </div>
        <div class="footer">
            AMIS System Notification • Al-Munawwara Islamic School • info@amis.edu.ph
        </div>
    </div>
</body>
</html>
