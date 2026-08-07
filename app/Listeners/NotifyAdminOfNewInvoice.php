<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Models\Admin;
use App\Notifications\AdminAlertNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Notification;

class NotifyAdminOfNewInvoice implements ShouldQueue
{
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice->loadMissing('client');
        $clientName = $invoice->client ? trim("{$invoice->client->firstname} {$invoice->client->lastname}") : 'A customer';

        Notification::send(Admin::all(), new AdminAlertNotification(
            title: 'New order created',
            message: "{$clientName} created invoice #{$invoice->id} for " . \App\Support\CurrencyConverter::format((float) $invoice->total, $invoice->currency_code),
            severity: 'info',
            url: route('admin.invoices.show', $invoice->id),
        ));
    }
}
