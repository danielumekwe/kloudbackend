<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"></head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 8px;">VPS provisioning issue</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 20px;">
            Hi {{ $firstName }}, we encountered an issue while setting up your <strong>{{ $planName }}</strong> VPS (order #{{ $orderId }}).
            Our team has been notified and will resolve this shortly.
        </p>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            Your invoice #{{ $invoiceId }} remains active. No additional charges will apply during this process.
            If you don't hear from us within 24 hours, please open a support ticket.
        </p>
        <a href="{{ route('support.create') }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            Open Support Ticket
        </a>
    </div>
</body>
</html>
