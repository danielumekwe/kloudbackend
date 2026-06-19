<?php

namespace App\Console\Commands;

use App\Models\VpsOrder;
use App\Services\InterServerService;
use App\Services\WhmcsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

#[Signature('vps:provision-paid')]
#[Description('Check pending VPS orders for paid WHMCS invoices and provision them on InterServer')]
class ProvisionPaidVps extends Command
{
    public function handle(WhmcsService $whmcs, InterServerService $interserver): int
    {
        $pending = VpsOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = $whmcs->getInvoice($order->whmcs_invoice_id);

            if (($invoice['status'] ?? '') !== 'Paid') {
                continue;
            }

            $config = $order->config;

            // Claim before calling out — see ProvisionPaidDomain for why.
            $order->update(['status' => 'provisioning']);

            $result = $interserver->placeOrder([
                'vpsPlatform'  => $config['platform'],
                'osDistro'     => $config['osDistro'],
                'osVersion'    => $config['osVersion'],
                'slices'       => $config['slices'],
                'location'     => $config['location'],
                'period'       => $config['period'],
                'controlpanel' => $config['controlpanel'],
                'hostname'     => $config['hostname'],
                'rootpass'     => Crypt::decryptString($config['rootpass']),
            ]);

            if ($result['success'] ?? false) {
                $order->update([
                    'status'             => 'provisioned',
                    'interserver_vps_id' => $result['serviceid'],
                ]);
                $this->info("Provisioned VPS order #{$order->id} -> InterServer vps_id {$result['serviceid']}");
            } else {
                $order->update([
                    'status'         => 'failed',
                    'failure_reason' => $result['message'] ?? json_encode($result),
                ]);
                Log::error("VPS auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision VPS order #{$order->id}: " . ($result['message'] ?? 'unknown error'));
            }
        }

        return self::SUCCESS;
    }
}
