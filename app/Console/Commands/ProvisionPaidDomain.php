<?php

namespace App\Console\Commands;

use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('domains:provision-paid')]
#[Description('Check pending domain orders for paid invoices and register/transfer them on InterServer')]
class ProvisionPaidDomain extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $pending = DomainOrder::where('status', 'pending_payment')->get();

        foreach ($pending as $order) {
            $invoice = Invoice::find($order->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
                continue;
            }

            $payload = array_merge($order->registrant_contact, [
                'hostname'      => "{$order->domain_name}.{$order->tld}",
                'type'          => $order->order_type,
                'whois_privacy' => $order->whois_privacy ? 'enable' : 'disable',
            ]);

            if ($order->order_type === 'transfer') {
                $payload['auth_code'] = $order->config['auth_code'] ?? '';
            }

            // Claim the order before calling out so a crash between the InterServer
            // call succeeding and the status update below can't cause the next run to
            // place a duplicate order — a stuck "provisioning" order needs a human to
            // look at it, which beats silently double-registering the domain.
            $order->update(['status' => 'provisioning']);

            $result = $interserver->placeDomainOrder($payload);

            if ($result['success'] ?? false) {
                $order->update([
                    'status'                 => 'provisioned',
                    'interserver_domain_id'  => $result['serviceid'],
                ]);
                $this->info("Provisioned domain order #{$order->id} -> InterServer domain_id {$result['serviceid']}");
            } else {
                $order->update([
                    'status'         => 'failed',
                    'failure_reason' => $result['message'] ?? json_encode($result),
                ]);
                Log::error("Domain auto-provision failed for order #{$order->id}", $result);
                $this->error("Failed to provision domain order #{$order->id}: " . ($result['message'] ?? 'unknown error'));
            }
        }

        return self::SUCCESS;
    }
}
