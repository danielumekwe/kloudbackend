<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">Your ticket has a new reply</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 16px;">
            Hi {{ $firstName }}, our support team replied to ticket <strong>{{ $ticketCode }}</strong> &mdash; "{{ $ticketSubject }}":
        </p>
        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:16px;font-size:14px;color:#334155;line-height:1.6;white-space:pre-wrap;margin:0 0 24px;">{{ $replyMessage }}</div>
        <p style="font-size:13px;color:#94a3b8;line-height:1.6;margin:0;">
            Log in to your Kloud101 dashboard to reply.
        </p>
    </div>
</body>
</html>
