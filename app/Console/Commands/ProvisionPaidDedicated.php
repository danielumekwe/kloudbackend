<?php

namespace App\Console\Commands;

use App\Jobs\ProvisionServerOrder;
use App\Models\DedicatedServerOrder;
use App\Models\Invoice;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * See ProvisionPaidVps — same synchronous safety-net sweep, for Dedicated
 * Server orders. Runs the provisioning job inline (dispatchSync), so it
 * works whether or not a queue worker is running in production.
 */
#[Signature('dedicated:provision-paid')]
#[Description('Sweep for paid-but-not-yet-provisioned Dedicated Server orders and provision them (runs inline, no queue worker required)')]
class ProvisionPaidDedicated extends Command
{
    public function handle(): int
    {
        $pending = DedicatedServerOrder::where('status', 'pending_payment')->get();
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
                Log::error("Provisioning sweep failed for Dedicated Server order #{$order->id}", ['error' => $e->getMessage()]);
            }
        }

        $this->info("Processed {$provisioned} Dedicated Server order(s).");

        return self::SUCCESS;
    }
}
