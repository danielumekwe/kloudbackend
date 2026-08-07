<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionServerOrder;
use App\Models\Invoice;
use App\Models\VpsOrder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Provisioning is normally triggered instantly by App\Listeners\
 * DispatchProvisioningOnInvoicePaid the moment an invoice is marked paid,
 * via a real queued dispatch (fast, but only runs once a queue worker picks
 * it up). This command is the 5-minute safety-net sweep — it runs the same
 * job SYNCHRONOUSLY (dispatchSync), so every paid order is guaranteed to be
 * provisioned within 5 minutes even if no queue worker is running at all.
 * Only orders still 'pending_payment' are picked up here — anything already
 * claimed ('provisioning') by an instant dispatch is left alone, so there's
 * no double-provisioning race between the two paths.
 */
#[Signature('vps:provision-paid')]
#[Description('Sweep for paid-but-not-yet-provisioned VPS orders and provision them (runs inline, no queue worker required)')]
class ProvisionPaidVps extends Command
{
    public function handle(): int
    {
        $pending = VpsOrder::where('status', 'pending_payment')->get();
        $provisioned = 0;

        foreach ($pending as $order) {
            $invoice = Invoice::find($order->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
                continue;
            }

            try {
                ProvisionServerOrder::dispatchSync($order);
                $provisioned++;
            } catch (Throwable $e) {
                // The job's own failed() handler already marked the order 'failed'
                // and notified the client/admin — this just keeps the sweep loop
                // going so one bad order doesn't block the rest.
                Log::error("Provisioning sweep failed for VPS order #{$order->id}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Processed {$provisioned} VPS order(s).");

        return self::SUCCESS;
    }
}
