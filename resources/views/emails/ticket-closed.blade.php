<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">Ticket closed</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            Hi {{ $firstName }}, ticket <strong>{{ $ticketCode }}</strong> &mdash; "{{ $ticketSubject }}" &mdash; has been closed.
        </p>
        <p style="font-size:13px;color:#94a3b8;line-height:1.6;margin:0;">
            Need more help? Just open a new ticket from your dashboard.
        </p>
    </div>
</body>
</html>
