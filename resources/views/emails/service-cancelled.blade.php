<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 8px;">Service cancelled</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;">
            Hi {{ $firstName }}, your <strong>{{ $serviceType }}</strong> service — <strong>{{ $serviceDescription }}</strong> — has been cancelled (order #{{ $orderId }}).
        </p>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            If this was unexpected or you have any questions, please open a support ticket and our team will assist you.
        </p>
        <a href="{{ route('support.create') }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            Contact Support
        </a>
    </div>
</body>
</html>
