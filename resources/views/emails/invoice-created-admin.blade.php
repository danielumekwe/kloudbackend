<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
</head>
<body style="margin:0;padding:0;background:#f8fafc;font-family:Helvetica,Arial,sans-serif;">
    <div style="max-width:480px;margin:40px auto;background:#ffffff;border-radius:16px;padding:32px;border:1px solid #e2e8f0;">
        <h1 style="font-size:18px;color:#0f172a;margin:0 0 16px;">New invoice created</h1>
        <div style="background:#f1f5f9;border-radius:10px;padding:16px;margin-bottom:24px;font-size:13px;color:#334155;">
            <div style="margin-bottom:6px;"><strong>Customer:</strong> {{ $clientName }}</div>
            <div style="margin-bottom:6px;"><strong>Invoice #:</strong> {{ $invoiceId }}</div>
            <div style="margin-bottom:6px;"><strong>Service:</strong> {{ $description }}</div>
            <div style="margin-bottom:6px;"><strong>Amount:</strong> {{ $currency }} {{ number_format($amount, 2) }}</div>
            <div><strong>Status:</strong> {{ ucfirst($status) }}</div>
        </div>
        <a href="{{ route('admin.invoices.show', $invoiceId) }}"
           style="display:inline-block;background:#2563eb;color:#ffffff;text-decoration:none;font-size:14px;font-weight:600;padding:12px 24px;border-radius:10px;">
            View Invoice
        </a>
    </div>
</body>
</html>
