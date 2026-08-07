<?php

namespace App\Console\Commands;

use App\Mail\InvoiceOverdueMail;
use App\Mail\PaymentReminderMail;
use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

#[Signature('invoices:send-reminders')]
#[Description('Send payment-reminder emails for invoices due soon, and overdue notices for invoices past due')]
class SendInvoiceReminders extends Command
{
    public function handle(): int
    {
        $reminders = Invoice::with('client')
            ->where('status', 'unpaid')
            ->whereNull('reminder_sent_at')
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [now(), now()->addDays(2)])
            ->get();

        foreach ($reminders as $invoice) {
            if (! $invoice->client) {
                continue;
            }

            Mail::to($invoice->client->email)->send(new PaymentReminderMail(
                firstName: $invoice->client->firstname,
                invoiceId: $invoice->id,
                amount: (float) $invoice->total,
                currency: $invoice->currency_code,
                dueDate: $invoice->due_date->format('M j, Y'),
            ));

            $invoice->update(['reminder_sent_at' => now()]);
        }

        $overdue = Invoice::with('client')
            ->where('status', 'unpaid')
            ->whereNull('overdue_notified_at')
            ->whereNotNull('due_date')
            ->where('due_date', '<', now())
            ->get();

        foreach ($overdue as $invoice) {
            if (! $invoice->client) {
                continue;
            }

            Mail::to($invoice->client->email)->send(new InvoiceOverdueMail(
                firstName: $invoice->client->firstname,
                invoiceId: $invoice->id,
                amount: (float) $invoice->total,
                currency: $invoice->currency_code,
                dueDate: $invoice->due_date->format('M j, Y'),
            ));

            $invoice->update(['overdue_notified_at' => now()]);
        }

        $this->info("Sent {$reminders->count()} reminder(s) and {$overdue->count()} overdue notice(s).");

        return self::SUCCESS;
    }
}
