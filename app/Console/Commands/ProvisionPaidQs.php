<?php

namespace App\Console\Commands;

use App\Mail\VpsFailedMail;
use App\Mail\VpsProvisionedMail;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\QsOrder;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

#[Signature('qs:provision-paid')]
#[Description('Check pending Quick Server orders for paid invoices and provision them on InterServer')]
class ProvisionPaidQs extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $pending = QsOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = Invoice::find($order->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
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

            $client   = Client::find($order->client_id);
            $planName = $config['plan_name'] ?? ($config['server'] ?? 'Quick Server');

            if ($result['success'] ?? false) {
                $order->update([
                    'status'            => 'provisioned',
                    'interserver_qs_id' => $result['serviceid'],
                ]);
                $this->info("Provisioned Quick Server order #{$order->id} -> InterServer qs_id {$result['serviceid']}");

                if ($client) {
                    Mail::to($client->email)->send(new VpsProvisionedMail(
                        firstName:  $client->firstname,
                        hostname:   $config['comment'] ?? 'your server',
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
                Log::error("Quick Server auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision Quick Server order #{$order->id}: " . ($result['message'] ?? 'unknown error'));

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
