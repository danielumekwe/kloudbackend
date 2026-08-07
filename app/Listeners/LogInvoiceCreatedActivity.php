<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Services\ActivityLogger;
use App\Support\CurrencyConverter;

class LogInvoiceCreatedActivity
{
    public function __construct(private ActivityLogger $logger)
    {
    }

    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice;

        $this->logger->log(
            action: 'invoice.created',
            description: "Invoice #{$invoice->id} created — " . CurrencyConverter::format((float) $invoice->total, $invoice->currency_code),
            subject: $invoice,
        );
    }
}
