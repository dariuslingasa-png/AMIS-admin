<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>AMIS System Backup Successful</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f8fafc; color: #1e293b; margin: 0; padding: 24px; }
        .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 16px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); }
        .header { background: linear-gradient(135deg, #0f172a 0%, #064e3b 100%); color: #ffffff; padding: 32px 24px; text-align: center; }
        .header h1 { margin: 0; font-size: 24px; font-weight: 800; tracking-tight: -0.025em; }
        .header p { margin: 8px 0 0 0; color: #a7f3d0; font-size: 14px; font-weight: 600; }
        .content { padding: 32px 24px; }
        .badge { display: inline-block; background: #dcfce7; color: #166534; font-weight: 800; font-size: 12px; padding: 4px 12px; border-radius: 9999px; text-transform: uppercase; letter-spacing: 0.05em; }
        .stats-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .stats-table td { padding: 12px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .stats-table td.label { color: #64748b; font-weight: 600; width: 40%; }
        .stats-table td.value { color: #0f172a; font-weight: 700; word-break: break-all; }
        .footer { background: #f8fafc; padding: 20px 24px; border-top: 1px solid #f1f5f9; text-align: center; font-size: 12px; color: #94a3b8; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <span class="badge">Success</span>
            <h1>Automated System Backup Complete</h1>
            <p>AMIS Portal Disaster Recovery System</p>
        </div>
        <div class="content">
            <p style="font-size: 15px; color: #334155; line-height: 1.6;">
                The scheduled system backup completed successfully. A full timestamped snapshot of the MySQL database and application assets has been verified and stored securely.
            </p>

            <table class="stats-table">
                <tr>
                    <td class="label">Backup File</td>
                    <td class="value">{{ $filename }}</td>
                </tr>
                <tr>
                    <td class="label">Archive Size</td>
                    <td class="value">{{ $formattedSize }}</td>
                </tr>
                <tr>
                    <td class="label">Execution Duration</td>
                    <td class="value">{{ $executionTime }} seconds</td>
                </tr>
                <tr>
                    <td class="label">Timestamp</td>
                    <td class="value">{{ $timestamp }}</td>
                </tr>
                <tr>
                    <td class="label">Integrity Status</td>
                    <td class="value" style="color: #15803d;">✔ Passed (ZIP & SQL Validated)</td>
                </tr>
            </table>

            @if(!empty($includedItems))
                <div style="margin-top: 24px; padding: 16px; background: #f8fafc; border-radius: 12px; border: 1px solid #e2e8f0;">
                    <strong style="font-size: 13px; color: #475569; text-transform: uppercase; letter-spacing: 0.05em;">Included Assets in Archive:</strong>
                    <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 13px; color: #334155;">
                        @foreach($includedItems as $item)
                            <li>{{ $item }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
        <div class="footer">
            Automated Notification System • AMIS Portal Admin • recipient: darius.lingasa@gmail.com
        </div>
    </div>
</body>
</html>
