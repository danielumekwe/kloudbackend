<?php

namespace App\Console\Commands;

use App\Models\DomainRenewal;
use App\Models\Invoice;
use App\Services\InterServerService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('domains:provision-paid-renewal')]
#[Description('Check pending domain renewal invoices for payment and submit the renewal to InterServer')]
class ProvisionPaidDomainRenewal extends Command
{
    public function handle(InterServerService $interserver): int
    {
        $pending = DomainRenewal::where('status', 'pending_payment')->with('domainOrder')->get();

        foreach ($pending as $renewal) {
            $invoice = Invoice::find($renewal->invoice_id);

            if (! $invoice || $invoice->status !== 'paid') {
                continue;
            }

            $order = $renewal->domainOrder;

            // Claim before calling out — see ProvisionPaidDomain for why.
            $renewal->update(['status' => 'provisioning']);

            $result = $interserver->renewDomain($order->interserver_domain_id, ['years' => $renewal->years]);

            if ($result['success'] ?? false) {
                $renewal->update(['status' => 'completed']);
                $this->info("Renewed domain order #{$order->id} for {$renewal->years} year(s)");
            } else {
                $renewal->update([
                    'status'         => 'failed',
                    'failure_reason' => $result['message'] ?? json_encode($result),
                ]);
                Log::error("Domain renewal failed for renewal #{$renewal->id}", $result);
                $this->error("Failed to renew domain order #{$order->id}: " . ($result['message'] ?? 'unknown error'));
            }
        }

        return self::SUCCESS;
    }
}
