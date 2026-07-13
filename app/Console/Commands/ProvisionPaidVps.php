<?php

namespace App\Console\Commands;

use App\Mail\VpsFailedMail;
use App\Mail\VpsProvisionedMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\VpsOrder;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Signature('vps:provision-paid')]
#[Description('Check pending VPS orders for paid invoices and provision them on InterServer')]
class ProvisionPaidVps extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $pending = VpsOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = Invoice::find($order->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
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

            $client  = Client::find($order->client_id);
            $planName = $config['plan_name'] ?? ($config['platform'] ?? 'VPS');

            if ($result['success'] ?? false) {
                $order->update([
                    'status'             => 'provisioned',
                    'interserver_vps_id' => $result['serviceid'],
                ]);
                $this->info("Provisioned VPS order #{$order->id} -> InterServer vps_id {$result['serviceid']}");

                if ($client) {
                    Mail::to($client->email)->send(new VpsProvisionedMail(
                        firstName:  $client->firstname,
                        hostname:   $config['hostname'] ?? 'your server',
                        planName:   $planName,
                        orderId:    $order->id,
                        invoiceId:  $order->invoice_id,
                    ));
                }
            } else {
                $order->update([
                    'status'         => 'failed',
                    'failure_reason' => $result['message'] ?? json_encode($result),
                ]);
                Log::error("VPS auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision VPS order #{$order->id}: " . ($result['message'] ?? 'unknown error'));

                if ($client) {
                    Mail::to($client->email)->send(new VpsFailedMail(
                        firstName:  $client->firstname,
                        planName:   $planName,
                        orderId:    $order->id,
                        invoiceId:  $order->invoice_id,
                    ));
                }
            }
        }

        return self::SUCCESS;
    }
}
