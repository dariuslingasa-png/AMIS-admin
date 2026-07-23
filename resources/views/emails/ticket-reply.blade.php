<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $replySubject }}</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #3b82f6 100%); color: #ffffff; padding: 28px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 20px; font-weight: 800; }
        .header p { margin: 6px 0 0 0; color: #93c5fd; font-size: 13px; font-weight: 600; }
        .content { padding: 32px 24px; font-size: 14px; line-height: 1.6; color: #334155; }
        .message-box { background: #f8fafc; border-left: 4px solid #3b82f6; border-radius: 8px; padding: 16px; margin: 16px 0; white-space: pre-wrap; word-break: break-word; font-size: 14px; color: #0f172a; }
        .footer { background: #f8fafc; padding: 20px 24px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>AMIS Support Response</h1>
            <p>Ticket Reference: {{ $referenceNumber }}</p>
        </div>
        <div class="content">
            <p>Assalamu Alaikum / Greetings <strong>{{ $recipientName }}</strong>,</p>
            
            <p>Below is the response from the AMIS Support Team regarding your inquiry:</p>

            <div class="message-box">{{ $replyMessage }}</div>

            @if($attachmentPath)
                <p style="font-size: 12px; color: #64748b; margin-top: 16px;">
                    📎 <em>An image/document attachment has been included with this email.</em>
                </p>
            @endif

            <p style="margin-top: 24px;">If you have further questions or require additional assistance, please reply directly to this email message.</p>
        </div>
        <div class="footer">
            AMIS Support Team • Al-Munawwara Islamic School • support@amis.edu.ph
        </div>
    </div>
</body>
</html>
