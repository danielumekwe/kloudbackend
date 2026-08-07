<?php

namespace App\Listeners;

use App\Events\InvoiceCreated;
use App\Mail\InvoiceCreatedAdminMail;
use App\Mail\InvoiceCreatedMail;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Mail;

class SendInvoiceCreatedEmails implements ShouldQueue
{
    public function handle(InvoiceCreated $event): void
    {
        $invoice = $event->invoice->loadMissing(['client', 'items']);

        if (! $invoice->client) {
            return;
        }

        $description = $invoice->items->first()->description ?? 'Kloud101 service';

        Mail::to($invoice->client->email)->send(new InvoiceCreatedMail(
            firstName: $invoice->client->firstname,
            invoiceId: $invoice->id,
            description: $description,
            amount: (float) $invoice->total,
            currency: $invoice->currency_code,
            dueDate: optional($invoice->due_date)->format('M j, Y') ?? 'On receipt',
        ));

        if ($adminEmail = config('mail.admin_notification_email')) {
            Mail::to($adminEmail)->send(new InvoiceCreatedAdminMail(
                clientName: trim("{$invoice->client->firstname} {$invoice->client->lastname}"),
                invoiceId: $invoice->id,
                description: $description,
                amount: (float) $invoice->total,
                currency: $invoice->currency_code,
                status: $invoice->status,
            ));
        }
    }
}
