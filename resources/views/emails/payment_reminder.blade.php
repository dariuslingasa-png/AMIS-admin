<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>AMIS Payment Reminder – Monthly School Fees</title>
  <style>
    body {
      margin: 0;
      padding: 0;
      background-color: #f4f6f8;
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
      color: #1e293b;
      -webkit-font-smoothing: antialiased;
    }
    table {
      border-collapse: collapse;
      mso-table-lspace: 0pt;
      mso-table-rspace: 0pt;
    }
    img {
      border: 0;
      outline: none;
      text-decoration: none;
      display: block;
      max-width: 100%;
      height: auto;
    }
    .poster-img {
      width: 100% !important;
      max-width: 600px !important;
      height: auto !important;
      display: block !important;
      margin: 0 auto !important;
      border-radius: 8px;
    }
  </style>
</head>
<body style="margin:0; padding:20px 0; background-color:#f4f6f8;">

  @php
    $embedImage = function($path, $fallbackUrl) use ($message) {
      if (isset($message) && is_object($message) && method_exists($message, 'embed') && file_exists($path)) {
        return $message->embed($path);
      }
      return $fallbackUrl;
    };

    $img1 = $embedImage(
      $image1Path ?? public_path('images/reminder/poster_payment_reminder.png'),
      config('app.url') . '/images/reminder/poster_payment_reminder.png'
    );
    $img2 = $embedImage(
      $image2Path ?? public_path('images/reminder/poster_payment_info.png'),
      config('app.url') . '/images/reminder/poster_payment_info.png'
    );
    $img3 = $embedImage(
      $image3Path ?? public_path('images/reminder/banner_already_paid.jpg'),
      config('app.url') . '/images/reminder/banner_already_paid.jpg'
    );
    $logo = $embedImage(
      $logoPath ?? public_path('images/AMIS_Logo.png'),
      config('app.url') . '/images/AMIS_Logo.png'
    );
  @endphp

  <center>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="max-width:600px; margin:0 auto; background-color:#ffffff; border-radius:12px; overflow:hidden; box-shadow:0 4px 16px rgba(0,0,0,0.06); border: 1px solid #e2e8f0;">

      <!-- ── HEADER ───────────────────────────────────────────────────────── -->
      <tr>
        <td style="background: linear-gradient(135deg, #1b5e20 0%, #2e7d32 100%); padding: 24px 20px; text-align: center;">
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0">
            <tr>
              <td align="center">
                <img src="{{ $logo }}" alt="AMIS Logo" width="70" height="70" style="width:70px; height:70px; border-radius:50%; background:#ffffff; padding:3px; display:inline-block;" />
              </td>
            </tr>
            <tr>
              <td align="center" style="padding-top:12px;">
                <p style="color:#d4edda; font-size:12px; letter-spacing:2px; font-weight:700; margin:0;">
                  المدرسة المنورة الإسلامية
                </p>
                <p style="color:#ffffff; font-size:18px; font-weight:900; letter-spacing:1px; margin:4px 0 0 0; text-transform:uppercase;">
                  Al Munawwara Islamic School
                </p>
                <p style="color:#a7d7b3; font-size:11px; letter-spacing:1px; margin:4px 0 0 0; text-transform:uppercase;">
                  Official Finance Department
                </p>
              </td>
            </tr>
          </table>
        </td>
      </tr>

      <!-- ── PICTURE 1: MONTHLY PAYMENT REMINDER POSTER ────────────────────── -->
      <tr>
        <td style="padding: 16px 16px 8px 16px; background-color:#ffffff;">
          <img src="{{ $img1 }}" alt="Monthly Payment Reminder" class="poster-img" style="border: 1px solid #e2e8f0;" />
        </td>
      </tr>

      <!-- ── REMINDER MESSAGE ─────────────────────────────────────────────── -->
      <tr>
        <td style="padding: 16px 24px 8px 24px; font-size: 15px; line-height: 1.65; color: #334155;">
          <h2 style="font-size: 18px; font-weight: 800; color: #1b5e20; margin: 0 0 12px 0;">
            Assalamu Alaikum!
          </h2>

          @if(!empty($parentName))
            <p style="margin: 0 0 10px 0; font-size: 14px; font-weight: 600; color: #475569;">
              Dear {{ $parentName }},
            </p>
          @endif

          <p style="margin: 0 0 12px 0;">
            This is a friendly reminder regarding any pending monthly school payment.
          </p>

          <p style="margin: 0 0 12px 0;">
            If you still have an outstanding balance, kindly settle your payment as soon as possible.
          </p>

          @if(!empty($studentNames))
            <div style="background-color: #f8fafc; border-left: 4px solid #2e7d32; padding: 10px 14px; margin: 12px 0; border-radius: 4px;">
              <p style="margin: 0; font-size: 12px; font-weight: 700; color: #64748b; text-transform: uppercase;">Enrolled Student(s):</p>
              <p style="margin: 3px 0 0 0; font-size: 14px; font-weight: 700; color: #0f172a;">{{ $studentNames }}</p>
            </div>
          @endif
        </td>
      </tr>

      <!-- ── PICTURE 2: PAYMENT & BANK DETAILS POSTER ──────────────────────── -->
      <tr>
        <td style="padding: 8px 16px; background-color:#ffffff;">
          <img src="{{ $img2 }}" alt="Official Payment Accounts & Information" class="poster-img" style="border: 1px solid #e2e8f0;" />
        </td>
      </tr>

      <!-- ── IMPORTANT PAYMENT / RECEIPT NOTICE ────────────────────────────── -->
      <tr>
        <td style="padding: 12px 24px 8px 24px; font-size: 14px; line-height: 1.6; color: #334155;">

          <!-- Disregard notice box -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 14px 0;">
            <tr>
              <td style="background-color: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; padding: 12px 16px;">
                <p style="margin: 0; font-size: 13px; font-weight: 800; color: #92400e; text-align: center;">
                  ⚠️ IMPORTANT: IF YOU ALREADY PAID, PLEASE IGNORE THIS MESSAGE AND DO NOT REPLY BACK.
                </p>
              </td>
            </tr>
          </table>

          <!-- Receipt instruction box (Large and Bold) -->
          <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="margin: 0 0 16px 0;">
            <tr>
              <td style="background-color: #e8f5e9; border: 2px solid #2e7d32; border-radius: 10px; padding: 16px 18px;">
                <p style="margin: 0 0 6px 0; font-size: 15px; font-weight: 900; color: #1b5e20;">
                  📧 After a successful fund transfer:
                </p>
                <p style="margin: 0; font-size: 15px; font-weight: 800; color: #1b5e20; line-height: 1.6;">
                  Please do not forget to send your receipt/proof of payment to:<br>
                  <a href="mailto:amisfinance2324@gmail.com" style="color: #1b5e20; font-weight: 900; font-size: 17px; text-decoration: underline;">
                    amisfinance2324@gmail.com
                  </a>
                </p>
                <p style="margin: 8px 0 0 0; font-size: 13px; font-weight: 700; color: #2e7d32;">
                  This will help the AMIS Finance team verify and record your payment.
                </p>
              </td>
            </tr>
          </table>

          <p style="margin: 0 0 10px 0; font-size: 13px; color: #64748b;">
            Please disregard this reminder if payment has already been made.
          </p>

          <p style="margin: 0 0 6px 0; font-size: 14px; font-weight: 600; color: #334155;">
            Thank you for your continued cooperation and support.
          </p>

          <p style="margin: 0; font-size: 15px; font-weight: 800; color: #1b5e20;">
            Jazakumullahu Khairan.
          </p>
        </td>
      </tr>

      <!-- ── PICTURE 3: AUTOMATED REMINDER / ALREADY PAID BANNER ───────────── -->
      <tr>
        <td style="padding: 12px 16px 16px 16px; background-color:#ffffff;">
          <img src="{{ $img3 }}" alt="Automated Payment Reminder - If Already Paid Disregard" class="poster-img" style="border: 1px solid #e2e8f0;" />
        </td>
      </tr>

      <!-- ── FOOTER ───────────────────────────────────────────────────────── -->
      <tr>
        <td style="background-color: #f8fafc; border-top: 1px solid #e2e8f0; padding: 20px 24px; text-align: center;">
          <p style="margin: 0; font-size: 14px; font-weight: 900; color: #1b5e20; letter-spacing: 0.5px; text-transform: uppercase;">
            AMIS SUPPORT STAFF
          </p>
          <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">
            Al Munawwara Islamic School · Official Automated Finance Notification
          </p>
          <p style="margin: 8px 0 0 0; font-size: 11px; color: #94a3b8;">
            This is an automated system notification. For inquiries, please contact <a href="mailto:amisfinance2324@gmail.com" style="color: #2e7d32; font-weight: 700;">amisfinance2324@gmail.com</a>.
          </p>
        </td>
      </tr>

    </table>
  </center>
</body>
</html>
