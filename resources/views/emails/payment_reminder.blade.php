<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Payment Reminder</title>
</head>
<body style="margin:0; padding:0; background-color:#f1f5f9; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; -webkit-font-smoothing:antialiased;">
    <table border="0" cellpadding="0" cellspacing="0" width="100%" style="background-color:#f1f5f9; padding:20px 0;">
        <tr>
            <td align="center">
                <table border="0" cellpadding="0" cellspacing="0" width="600" style="background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 12px rgba(0, 0, 0, 0.05); border:1px solid #e2e8f0;">
                    <!-- Top header color bar -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #064e3b 0%, #047857 100%); height:8px;"></td>
                    </tr>
                    
                    <!-- Content -->
                    <tr>
                        <td style="padding:40px 32px 24px 32px; color:#334155;">
                            <h2 style="margin-top:0; color:#064e3b; font-size:20px; font-weight:800; border-bottom:1px solid #f1f5f9; padding-bottom:16px;">
                                Al Munawwara Islamic School
                            </h2>
                            
                            <p style="font-size:15px; line-height:1.6; font-weight:600; color:#0f172a; margin-top:20px;">
                                Dear Parents & Guardians,
                            </p>
                            
                            <p style="font-size:15px; line-height:1.6; font-weight:700; color:#047857; margin-bottom:20px;">
                                Assalamu Alaikum wa rahmatullahi wa barakatuh.
                            </p>
                            
                            <p style="font-size:15px; line-height:1.6; color:#334155; margin-bottom:16px;">
                                This is a friendly reminder that we are now <strong>7 days</strong> before the monthly payment deadline.
                            </p>
                            
                            <p style="font-size:15px; line-height:1.6; color:#334155; margin-bottom:24px;">
                                Kindly prepare and settle your monthly payment on or before the due date to avoid any delays or concerns.
                            </p>
                            
                            <!-- Embedded flyer image -->
                            <div style="text-align:center; margin:32px 0; background-color:#fafafa; padding:16px; border-radius:8px; border:1px solid #f1f5f9;">
                                <img src="{{ $message->embed(resource_path('images/payment_reminder.png')) }}" alt="Monthly Payment Reminder Flyer" style="max-width:100%; height:auto; border-radius:6px; box-shadow:0 4px 6px rgba(0,0,0,0.03); display:inline-block; border:1px solid #e2e8f0;">
                            </div>
                            
                            <p style="font-size:15px; line-height:1.6; color:#334155; margin-bottom:4px;">
                                Thank you for your cooperation.
                            </p>
                            
                            <p style="font-size:15px; line-height:1.6; font-weight:700; color:#064e3b; margin-top:0;">
                                Shukran.
                            </p>
                        </td>
                    </tr>
                    
                    <!-- Footer -->
                    <tr>
                        <td style="background-color:#f8fafc; padding:24px 32px; border-top:1px solid #f1f5f9; text-align:center; font-size:12px; color:#64748b;">
                            <p style="margin:0 0 8px 0; font-weight:600; color:#475569;">Al Munawwara Islamic School Inc.</p>
                            <p style="margin:0 0 16px 0;">Woodlane Diversion Road, Davao City, Philippines</p>
                            <p style="margin:0; font-size:11px;">This is an automated reminder. Please email your proof of payment to <a href="mailto:amisfinance2342@gmail.com" style="color:#047857; text-decoration:none; font-weight:600;">amisfinance2342@gmail.com</a>.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
