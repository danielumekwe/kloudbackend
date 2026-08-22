<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        .msg-body p { margin: 0 0 12px; }
        .msg-body a { color: #2563eb; }
        .msg-body img { max-width: 100%; height: auto; border-radius: 8px; }
        .msg-body ul, .msg-body ol { margin: 0 0 12px; padding-left: 20px; }
    </style>
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <p style="font-size:14px;color:#64748b;margin:0 0 8px;">Message from Kloud101 Support</p>
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">Hi {{ $firstName }}</h1>
        <div class="msg-body" style="font-size:14px;color:#334155;line-height:1.7;">{!! $body !!}</div>
        <hr style="border:none;border-top:1px solid #e2e8f0;margin:24px 0;">
        <p style="font-size:12px;color:#94a3b8;margin:0;">
            This message was sent from the Kloud101 admin team. Reply to this email to respond.
        </p>
    </div>
</body>
</html>
