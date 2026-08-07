<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Services\ActivityLogger;
use App\Support\CurrencyConverter;

/**
 * Not queued — the audit trail should not depend on a queue worker being up.
 */
class LogInvoicePaidActivity
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function handle(InvoicePaid $event): void
    {
        $invoice = $event->invoice;

        $this->logger->log(
            action: 'invoice.paid',
            description: "Invoice #{$invoice->id} paid — " . CurrencyConverter::format((float) $invoice->total, $invoice->currency_code),
            subject: $invoice,
        );
    }
}
