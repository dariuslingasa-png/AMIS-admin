<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Backup Failed Alert</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 40px 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: #ffffff;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #e11d48;
            padding: 24px;
            text-align: center;
            color: #ffffff;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .content {
            padding: 32px 24px;
        }
        .alert-box {
            background-color: #fff1f2;
            border: 1px solid #fecdd3;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
        }
        .alert-title {
            color: #be123c;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .error-message {
            font-family: monospace;
            background-color: #1e293b;
            color: #fda4af;
            padding: 12px;
            border-radius: 8px;
            overflow-x: auto;
            white-space: pre-wrap;
            margin: 8px 0 0 0;
            font-size: 13px;
        }
        .meta-list {
            margin: 20px 0;
            padding: 0;
            list-style: none;
        }
        .meta-item {
            padding: 8px 0;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
        }
        .meta-label {
            font-weight: bold;
            color: #64748b;
            width: 150px;
            display: inline-block;
        }
        .footer {
            background-color: #f8fafc;
            padding: 16px 24px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #f1f5f9;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>Backup Failure Alert</h1>
    </div>
    
    <div class="content">
        <p>Assalamu Alaikum,</p>
        <p>This is an automated alert informing you that the scheduled database and storage backup process for the **AMIS Admin Portal** has failed.</p>
        
        <div class="alert-box">
            <div class="alert-title">Failure Reason / Trace Log:</div>
            <pre class="error-message">{{ $errorMsg }}</pre>
        </div>
        
        <ul class="meta-list">
            <li class="meta-item">
                <span class="meta-label">Event Timestamp:</span>
                <span>{{ date('Y-m-d H:i:s') }}</span>
            </li>
            <li class="meta-item">
                <span class="meta-label">Execution Time:</span>
                <span>{{ $executionTime }} seconds</span>
            </li>
            <li class="meta-item">
                <span class="meta-label">Target Remote:</span>
                <span>Google Drive (rclone gdrive:AMIS-Backups/)</span>
            </li>
        </ul>
        
        <h4 style="color: #475569; margin-top: 24px;">Recommended Troubleshooting Steps:</h4>
        <ol style="font-size: 14px; line-height: 1.6; padding-left: 20px;">
            <li>Check if your cPanel disk quota is full, as the zipping process requires temporary local storage space.</li>
            <li>Verify that your Google Cloud OAuth credentials (client secret/refresh token) are still valid.</li>
            <li>Run the backup command manually in the cPanel terminal via: <code style="background-color: #f1f5f9; padding: 2px 6px; border-radius: 4px;">php artisan amis:backup</code> to inspect realtime error logs.</li>
        </ol>
    </div>
    
    <div class="footer">
        This is an automated system email. Please do not reply directly to this message.
    </div>
</div>

</body>
</html>
