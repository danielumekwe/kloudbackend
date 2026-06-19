<?php

namespace App\Console\Commands;

use App\Models\QsOrder;
use App\Services\InterServerService;
use App\Services\WhmcsService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

#[Signature('qs:provision-paid')]
#[Description('Check pending Quick Server orders for paid WHMCS invoices and provision them on InterServer')]
class ProvisionPaidQs extends Command
{
    public function handle(WhmcsService $whmcs, InterServerService $interserver): int
    {
        $pending = QsOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = $whmcs->getInvoice($order->whmcs_invoice_id);

            if (($invoice['status'] ?? '') !== 'Paid') {
                continue;
            }

            $config = $order->config;

            // Claim before calling out — see ProvisionPaidDomain for why.
            $order->update(['status' => 'provisioning']);

            $result = $interserver->placeQsOrder([
                'server'   => $config['server'],
                'os'       => $config['os'],
                'comment'  => $config['comment'] ?? '',
                'password' => Crypt::decryptString($config['password']),
                'tos'      => true,
            ]);

            if ($result['success'] ?? false) {
                $order->update([
                    'status'            => 'provisioned',
                    'interserver_qs_id' => $result['serviceid'],
                ]);
                $this->info("Provisioned Quick Server order #{$order->id} -> InterServer qs_id {$result['serviceid']}");
            } else {
                $order->update([
                    'status'         => 'failed',
                    'failure_reason' => $result['message'] ?? json_encode($result),
                ]);
                Log::error("Quick Server auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision Quick Server order #{$order->id}: " . ($result['message'] ?? 'unknown error'));
            }
        }

        return self::SUCCESS;
    }
}
