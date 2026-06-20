<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">Verify your email address</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            Hi {{ $firstName }}, welcome to Kloud101! Click the button below to confirm this is your email
            address. This link expires in 60 minutes.
        </p>
        <a href="{{ $verifyUrl }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            Verify email
        </a>
        <p style="font-size:13px;color:#94a3b8;line-height:1.6;margin:24px 0 0;">
            If you didn't create a Kloud101 account, you can safely ignore this email.
        </p>
    </div>
</body>
</html>
