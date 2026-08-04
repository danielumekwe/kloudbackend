<?php

namespace App\Console\Commands;

use App\Mail\VpsFailedMail;
use App\Mail\VpsProvisionedMail;
use App\Models\Client;
use App\Models\DedicatedServerOrder;
use App\Models\Invoice;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Signature('dedicated:provision-paid')]
#[Description('Check pending Dedicated Server orders for paid invoices and provision them on InterServer')]
class ProvisionPaidDedicated extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $pending = DedicatedServerOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = Invoice::find($order->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
                continue;
            }

            $config = $order->config;

            // Claim before calling out — see ProvisionPaidDomain for why.
            $order->update(['status' => 'provisioning']);

            $result = $interserver->placeBuyNowOrder((int) $config['asset_id'], [
                'hostname'       => $config['hostname'],
                'enablepassword' => true,
                'rootPassword'   => Crypt::decryptString($config['rootpass']),
                'os'             => $config['os'] ?? null,
                'bandwidth'      => $config['bandwidth'] ?? null,
                'ips'            => $config['ips'] ?? null,
                'cp'             => $config['cp'] ?? null,
                'raid'           => $config['raid'] ?? null,
                'comments'       => $config['comment'] ?? '',
            ]);

            $client   = Client::find($order->client_id);
            $planName = $config['listing']['cpu'][0] ?? 'Dedicated Server';

            if ($result['success'] ?? false) {
                $order->update([
                    'status'                 => 'provisioned',
                    'interserver_server_id'  => $result['order_details']['service_id'] ?? null,
                ]);
                $this->info("Provisioned Dedicated Server order #{$order->id} -> InterServer server_id " . ($result['order_details']['service_id'] ?? 'unknown'));

                if ($client) {
                    Mail::to($client->email)->send(new VpsProvisionedMail(
                        firstName:  $client->firstname,
                        hostname:   $config['hostname'],
                        planName:   $planName,
                        orderId:    $order->id,
                        invoiceId:  $order->invoice_id,
                    ));
                }
            } else {
                $failureReason = $result['text'] ?? (implode(' ', $result['errors'] ?? []) ?: json_encode($result));

                $order->update([
                    'status'         => 'failed',
                    'failure_reason' => $failureReason,
                ]);
                Log::error("Dedicated Server auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision Dedicated Server order #{$order->id}: " . ($result['text'] ?? 'unknown error'));

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
