<?php

namespace App\Listeners;

use App\Events\InvoicePaid;
use App\Jobs\ProvisionServerOrder;
use App\Models\DedicatedServerOrder;
use App\Models\VpsOrder;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Instant provisioning trigger (Feature 1) — the vps:provision-paid /
 * dedicated:provision-paid console commands remain as a 5-minute sweep
 * fallback for anything this dispatch ever misses.
 *
 * dispatch() is wrapped defensively: on the real 'database' queue driver
 * this never throws (it just inserts a row), but under a synchronous queue
 * driver the job runs inline and its final-attempt failure exception would
 * otherwise propagate straight out of the payment-marking flow that fired
 * this listener (PaymentService::recordPayment / AdminInvoicesController::
 * markPaid) — payment recording must never fail because provisioning did.
 */
class DispatchProvisioningOnInvoicePaid
{
    public function handle(InvoicePaid $event): void
    {
        $invoiceId = $event->invoice->id;

        VpsOrder::where('invoice_id', $invoiceId)
            ->whereIn('status', ['pending_payment', 'paid'])
            ->get()
            ->each(fn (VpsOrder $order) => $this->safeDispatch($order));

        DedicatedServerOrder::where('invoice_id', $invoiceId)
            ->whereIn('status', ['pending_payment', 'paid'])
            ->get()
            ->each(fn (DedicatedServerOrder $order) => $this->safeDispatch($order));
    }

    private function safeDispatch(VpsOrder|DedicatedServerOrder $order): void
    {
        try {
            ProvisionServerOrder::dispatch($order);
        } catch (Throwable $e) {
            Log::error('ProvisionServerOrder dispatch failed', [
                'order_type' => $order::class, 'order_id' => $order->id, 'error' => $e->getMessage(),
            ]);
        }
    }
}
