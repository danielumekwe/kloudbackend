<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">New login to your account</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            Hi {{ $firstName }}, your Kloud101 account was just signed into from <strong>{{ $location }}</strong>
            (IP: {{ $ip }}) on {{ $loggedInAt }}.
        </p>
        <p style="font-size:13px;color:#94a3b8;line-height:1.6;margin:0;">
            If this wasn't you, reset your password immediately and contact support.
        </p>
    </div>
</body>
</html>
