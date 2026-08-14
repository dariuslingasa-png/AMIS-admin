<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;background:#f1f5f9;font-family:Arial,sans-serif;color:#0f172a">
    <div style="max-width:560px;margin:32px auto;background:#fff;border:1px solid #dbe3ea;border-radius:16px;overflow:hidden">
        <div style="background:#064e3b;padding:24px 28px;color:#fff">
            <div style="font-size:12px;font-weight:700;letter-spacing:.16em">AMIS SECURITY</div>
            <h1 style="margin:8px 0 0;font-size:22px">Admin sign-in verification</h1>
        </div>
        <div style="padding:28px">
            <p style="margin-top:0">Assalamu Alaikum {{ $user->name }},</p>
            <p style="color:#475569;line-height:1.6">Use this one-time code to sign in to the AMIS Admin Portal:</p>
            <div style="margin:24px 0;padding:20px;text-align:center;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;font-size:36px;font-weight:800;letter-spacing:.28em;color:#065f46">{{ $code }}</div>
            <p style="font-size:13px;color:#64748b;line-height:1.6">The code expires in 10 minutes and can be used only once. If you did not request it, do not share the code and contact the system administrator.</p>
        </div>
    </div>
</body>
</html>
