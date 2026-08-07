<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <div style="width:48px;height:48px;background:#fef3c7;border-radius:12px;display:flex;align-items:center;justify-content:center;margin-bottom:16px;">
            <span style="font-size:24px;">⏰</span>
        </div>
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 8px;">Payment reminder</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            Hi {{ $firstName }}, invoice #{{ $invoiceId }} for <strong>{{ $currency }} {{ number_format($amount, 2) }}</strong>
            is due on {{ $dueDate }}. Please pay soon to avoid any interruption to your service.
        </p>
        <a href="{{ route('billing.show', $invoiceId) }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            Pay Now
        </a>
    </div>
</body>
</html>
