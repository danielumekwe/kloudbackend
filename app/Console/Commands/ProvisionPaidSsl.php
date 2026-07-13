<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\SslOrder;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('ssl:provision-paid')]
#[Description('Check pending SSL certificate orders for paid invoices and provision them on InterServer')]
class ProvisionPaidSsl extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $pending = SslOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = Invoice::find($order->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
                continue;
            }

            $config = $order->config;

            // Claim before calling out — see ProvisionPaidDomain for why.
            $order->update(['status' => 'provisioning']);

            $result = $interserver->placeSslOrder(array_merge($config, [
                'ssl' => $config['package_id'],
            ]));

            if ($result['success'] ?? false) {
                $order->update([
                    'status'             => 'provisioned',
                    'interserver_ssl_id' => $result['serviceid'],
                ]);
                $this->info("Provisioned SSL order #{$order->id} -> InterServer ssl_id {$result['serviceid']}");
            } else {
                $order->update([
                    'status'         => 'failed',
                    'failure_reason' => $result['message'] ?? json_encode($result),
                ]);
                Log::error("SSL auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision SSL order #{$order->id}: " . ($result['message'] ?? 'unknown error'));
            }
        }

        return self::SUCCESS;
    }
}
