<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">Invoice refunded</h1>
        <p style="font-size:14px;color:#475569;line-height:1.6;margin:0 0 24px;">
            Hi {{ $firstName }}, invoice #{{ $invoiceId }} for <strong>{{ $currency }} {{ number_format($amount, 2) }}</strong>
            has been refunded. Please allow a few business days for the funds to reflect, depending on your payment method.
        </p>
        <a href="{{ route('billing.index') }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            View Billing
        </a>
    </div>
</body>
</html>
