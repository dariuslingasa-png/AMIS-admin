<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="color-scheme" content="light dark">
  <meta name="supported-color-schemes" content="light dark">
  <title>AMIS Payment Reminder – Monthly School Fees</title>
  <style>
    /* Reset styles */
    body, table, td, p, a, li, blockquote {
      -webkit-text-size-adjust: 100%;
      -ms-text-size-adjust: 100%;
    }
    table, td {
      mso-table-lspace: 0pt;
      mso-table-rspace: 0pt;
    }
    img {
      -ms-interpolation-mode: bicubic;
      border: 0;
      height: auto;
      line-height: 100%;
      outline: none;
      text-decoration: none;
      display: block;
      max-width: 100%;
    }
    body {
      height: 100% !important;
      margin: 0 !important;
      padding: 0 !important;
      width: 100% !important;
      background-color: #f1f5f9;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #1e293b;
      -webkit-font-smoothing: antialiased;
    }
    .poster-img {
      width: 100% !important;
      max-width: 640px !important;
      height: auto !important;
      display: block !important;
      margin: 0 auto !important;
      border-radius: 8px;
    }
    @media only screen and (max-width: 640px) {
      .email-container {
        width: 100% !important;
        margin: auto !important;
        border-radius: 0 !important;
      }
      .mobile-padding {
        padding-left: 16px !important;
        padding-right: 16px !important;
      }
    }
  </style>
</head>
<body style="margin: 0; padding: 24px 0; background-color: #f1f5f9; -webkit-font-smoothing: antialiased;">

  @php
    $msgObj = isset($message) ? $message : null;
    $embedImage = function($path, $fallbackUrl) use ($msgObj) {
      if (isset($msgObj) && is_object($msgObj) && method_exists($msgObj, 'embed') && file_exists($path)) {
        return $msgObj->embed($path);
      }
      return $fallbackUrl;
    };

    $baseUrl = rtrim(config('app.url', 'https://admin.amis.edu.ph'), '/');
    if (!str_starts_with($baseUrl, 'http')) {
      $baseUrl = 'https://admin.amis.edu.ph';
    }

    $img1 = $embedImage(
      $image1Path ?? public_path('images/reminder/image1_due_soon.png'),
      $baseUrl . '/images/reminder/image1_due_soon.png'
    );
    $img2 = $embedImage(
      $image2Path ?? public_path('images/reminder/image2_payment_info.png'),
      $baseUrl . '/images/reminder/image2_payment_info.png'
    );
    $img3 = $embedImage(
      $image3Path ?? public_path('images/reminder/image3_automated_reminder.jpg'),
      $baseUrl . '/images/reminder/image3_automated_reminder.jpg'
    );
    $logo = $embedImage(
      $logoPath ?? public_path('images/AMIS_Logo.png'),
      $baseUrl . '/images/AMIS_Logo.png'
    );
  @endphp

  <center style="width: 100%; background-color: #f1f5f9;">
    <table role="presentation" class="email-container" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="max-width: 640px; margin: 0 auto; background-color: #ffffff; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">

      <!-- ── 1. AMIS EMAIL HEADER ────────────────────────────────────────── -->
      <tr>
        <td style="background: linear-gradient(135deg, #14532d 0%, #166534 50%, #15803d 100%); padding: 26px 20px; text-align: center;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align="center">
                <img src="{{ $logo }}" alt="AMIS Logo" width="76" height="76"
                     style="width: 76px; height: 76px; border-radius: 50%; background: #ffffff; padding: 4px; display: inline-block; box-shadow: 0 2px 8px rgba(0,0,0,0.15);" />
              </td>
            </tr>
            <tr>
              <td align="center" style="padding-top: 12px;">
                <p style="color: #bbf7d0; font-size: 13px; letter-spacing: 2px; font-weight: 700; margin: 0; font-family: 'Amiri', Tahoma, sans-serif;">
                  المدرسة المنورة الإسلامية
                </p>
                <h1 style="color: #ffffff; font-size: 19px; font-weight: 900; letter-spacing: 1px; margin: 4px 0 0 0; text-transform: uppercase;">
                  Al Munawwara Islamic School
                </h1>
                <p style="color: #86efac; font-size: 11px; letter-spacing: 1.5px; font-weight: 700; margin: 6px 0 0 0; text-transform: uppercase;">
                  Automated Payment Reminder System
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- ── 2. MESSAGE ──────────────────────────────────────────────────── -->
      <tr>
        <td class="mobile-padding" style="padding: 24px 28px 12px 28px; background-color: #ffffff; font-size: 15px; line-height: 1.7; color: #334155;">
          
          <h2 style="font-size: 18px; font-weight: 800; color: #166534; margin: 0 0 6px 0;">
            Assalamu Alaikum{{ !empty($recipientName) && $recipientName !== 'Valued Family' ? ', ' . $recipientName : '' }}!
          </h2>

          @if(!empty($billingMonth))
            <p style="margin: 0 0 12px 0; color: #15803d; font-size: 13px; font-weight: 700;">
              Billing Cycle: {{ \Carbon\Carbon::parse(strlen($billingMonth) === 7 ? $billingMonth . '-01' : $billingMonth)->format('F Y') }}
            </p>
          @endif

          <p style="margin: 0 0 12px 0; color: #1e293b; font-size: 15px;">
            This is a friendly reminder regarding any pending monthly school payment.
          </p>

          <p style="margin: 0 0 14px 0; color: #1e293b; font-size: 15px;">
            If you still have an outstanding balance, kindly settle your payment as soon as possible.
          </p>

          <!-- Anti-trimming invisible unique token to prevent Gmail Show Quoted Text truncation -->
          <div style="display:none;font-size:1px;color:#ffffff;line-height:1px;max-height:0px;max-width:0px;opacity:0;overflow:hidden;mso-hide:all;">
            Reminder UUID: {{ (string) \Illuminate\Support\Str::uuid() }} • Time: {{ microtime(true) }}
          </div>

          <!-- ── 3. IMPORTANT DISREGARD NOTICE ─────────────────────────────── -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 16px 0;">
            <tr>
              <td style="background-color: #fef3c7; border-left: 4px solid #d97706; border-radius: 6px; padding: 12px 16px;">
                <p style="margin: 0; font-size: 13px; font-weight: 800; color: #92400e; line-height: 1.5;">
                  ⚠️ IMPORTANT: IF YOU HAVE ALREADY PAID, PLEASE DISREGARD THIS MESSAGE. NO REPLY IS NECESSARY.
                </p>
              </td>
            </tr>
          </table>

        </td>
      </tr>

      <!-- ── 4. IMAGE 1: MONTHLY PAYMENT IS DUE SOON POSTER ───────────────── -->
      <tr>
        <td class="mobile-padding" style="padding: 6px 20px 14px 20px; background-color: #ffffff;" align="center">
          <img src="{{ $img1 }}" alt="Monthly Payment is Due Soon" class="poster-img" style="border: 1px solid #e2e8f0; width: 100%; max-width: 600px;" />
        </td>
      </tr>

      <!-- ── 5. IMAGE 2: OFFICIAL PAYMENT ACCOUNTS / INFORMATION ──────────── -->
      <tr>
        <td class="mobile-padding" style="padding: 8px 20px 14px 20px; background-color: #ffffff;" align="center">
          <img src="{{ $img2 }}" alt="Official Payment Accounts & Information - BDO, GCash, Maya" class="poster-img" style="border: 1px solid #e2e8f0; width: 100%; max-width: 600px;" />
        </td>
      </tr>

      <!-- ── 6. IMAGE 3: AUTOMATED PAYMENT REMINDER POSTER ────────────────── -->
      <tr>
        <td class="mobile-padding" style="padding: 8px 20px 18px 20px; background-color: #ffffff;" align="center">
          <img src="{{ $img3 }}" alt="Automated Payment Reminder - Note: If you already paid, ignore this and do not reply back" class="poster-img" style="border: 1px solid #e2e8f0; width: 100%; max-width: 600px;" />
        </td>
      </tr>

      <!-- ── 7. RECEIPT REMINDER ──────────────────────────────────────────── -->
      <tr>
        <td class="mobile-padding" style="padding: 6px 28px 18px 28px; background-color: #ffffff; font-size: 14px; line-height: 1.65; color: #334155;">

          <!-- Receipt instruction box -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 18px 0;">
            <tr>
              <td style="background-color: #f0fdf4; border: 2px solid #22c55e; border-radius: 12px; padding: 18px 20px;">
                <p style="margin: 0 0 6px 0; font-size: 14px; font-weight: 800; color: #166534;">
                  📧 After a successful fund transfer:
                </p>
                <p style="margin: 0; font-size: 15px; font-weight: 700; color: #14532d; line-height: 1.6;">
                  Please do not forget to send your payment receipt/proof of payment to:<br>
                  <a href="mailto:amisfinance2324@gmail.com" style="color: #15803d; font-weight: 900; font-size: 17px; text-decoration: underline;">
                    amisfinance2324@gmail.com
                  </a>
                </p>
                <p style="margin: 8px 0 0 0; font-size: 13px; font-weight: 600; color: #15803d;">
                  This will help the AMIS Finance team verify and record your payment.
                </p>
              </td>
            </tr>
          </table>

          <!-- ── 8. CLOSING ───────────────────────────────────────────────── -->
          <p style="margin: 0 0 8px 0; font-size: 14px; font-weight: 600; color: #334155;">
            Thank you for your continued cooperation and support.
          </p>

          <p style="margin: 0 0 16px 0; font-size: 16px; font-weight: 800; color: #166534;">
            Jazakumullahu Khairan.
          </p>

        </td>
      </tr>

      <!-- ── 9. FOOTER ────────────────────────────────────────────────────── -->
      <tr>
        <td class="mobile-padding" style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 22px 28px; text-align: center;">
          <p style="margin: 0; font-size: 12px; font-weight: 600; color: #64748b; line-height: 1.6;">
            This is an automated payment reminder sent by the AMIS Support Staff.<br>
            Please do not reply to this email.
          </p>
          <p style="margin: 8px 0 0 0; font-size: 12px; color: #64748b;">
            If you have payment-related questions, contact:
            <a href="mailto:amisfinance2324@gmail.com" style="color: #166534; font-weight: 700; text-decoration: underline;">
              amisfinance2324@gmail.com
            </a>
          </p>
          <p style="margin: 12px 0 0 0; font-size: 11px; color: #94a3b8; font-weight: 500;">
            © 2026 Al Munawwara Islamic School — All rights reserved.
          </p>
        </td>
      </tr>

    </table>
  </center>

</body>
</html>
