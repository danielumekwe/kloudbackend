<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <div style="width:48px;height:48px;background:#dcfce7;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <span style="font-size:24px;">🚀</span>
        </div>
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 8px;">Your VPS is ready!</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;">
            Hi {{ $firstName }}, your <strong>{{ $planName }}</strong> VPS has been provisioned and is now online.
        </p>
        <div style="background:#f1f5f9;border-radius:10px;padding:16px;margin-bottom:24px;font-size:13px;color:#334155;">
            <div style="margin-bottom:6px;"><strong>Hostname:</strong> {{ $hostname }}</div>
            <div><strong>Order #:</strong> {{ $orderId }}</div>
        </div>
        <p style="font-size:13px;color:#64748b;line-height:1.6;margin:0 0 24px;">
            You can log in via SSH using the root password you set when ordering. Your server is immediately accessible.
        </p>
        <a href="{{ route('dashboard') }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            Go to Dashboard
        </a>
    </div>
</body>
</html>
