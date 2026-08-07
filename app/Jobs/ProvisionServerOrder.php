<?php

namespace App\Jobs;

use App\Events\ServerProvisioned;
use App\Events\ServerProvisioningFailed;
use App\Mail\VpsFailedMail;
use App\Mail\VpsProvisionedMail;
use App\Models\Client;
use App\Models\DedicatedServerOrder;
use App\Models\ProvisionLog;
use App\Models\ServerInstance;
use App\Models\VpsOrder;
use App\Services\InterServerService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Provisions a single VpsOrder or DedicatedServerOrder against InterServer.
 * Dispatched immediately when an invoice is marked paid (see App\Listeners\
 * DispatchProvisioningOnInvoicePaid); the vps:provision-paid / dedicated:
 * provision-paid console commands remain as a 5-minute sweep in case a
 * dispatch is ever missed (e.g. a deploy mid-request).
 *
 * The order is flipped to "provisioning" before the API call so a crash
 * mid-call leaves it stuck for CheckStuckProvisioning to flag, rather than
 * silently retried and double-provisioned.
 */
class ProvisionServerOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300, 900];

    public function __construct(public Model $order)
    {
    }

    public function handle(InterServerService $interserver): void
    {
        $order = $this->order->fresh();

        if (! $order || ! in_array($order->status, ['pending_payment', 'paid', 'provisioning'], true)) {
            return; // already handled by another attempt/sweep, or no longer eligible
        }

        if ($order->status !== 'provisioning') {
            $order->update(['status' => 'provisioning']);
        }

        [$result, $requestPayload] = $order instanceof DedicatedServerOrder
            ? $this->placeDedicatedOrder($interserver, $order)
            : $this->placeVpsOrder($interserver, $order);

        $success = $result['success'] ?? false;

        ProvisionLog::create([
            'orderable_type'    => $order->getMorphClass(),
            'orderable_id'      => $order->id,
            'attempt'           => $this->attempts(),
            'status'            => $success ? 'success' : 'failed',
            'request_payload'   => $requestPayload,
            'response_payload'  => $result,
            'error_message'     => $success ? null : ($result['message'] ?? $result['text'] ?? null),
        ]);

        if (! $success) {
            throw new \RuntimeException($result['message'] ?? $result['text'] ?? 'InterServer provisioning failed');
        }

        $this->markProvisioned($order, $result);
    }

    /** @return array{0: array, 1: array} */
    private function placeVpsOrder(InterServerService $interserver, VpsOrder $order): array
    {
        $config = $order->config;

        $payload = [
            'vpsPlatform'  => $config['platform'],
            'osDistro'     => $config['osDistro'],
            'osVersion'    => $config['osVersion'],
            'slices'       => $config['slices'],
            'location'     => $config['location'],
            'period'       => $config['period'],
            'controlpanel' => $config['controlpanel'],
            'hostname'     => $config['hostname'],
            'rootpass'     => Crypt::decryptString($config['rootpass']),
        ];

        $result = $interserver->placeOrder($payload);

        return [$result, [...$payload, 'rootpass' => '[redacted]']];
    }

    /** @return array{0: array, 1: array} */
    private function placeDedicatedOrder(InterServerService $interserver, DedicatedServerOrder $order): array
    {
        $config = $order->config;

        $payload = [
            'hostname'       => $config['hostname'],
            'enablepassword' => true,
            'rootPassword'   => Crypt::decryptString($config['rootpass']),
            'os'             => $config['os'] ?? null,
            'bandwidth'      => $config['bandwidth'] ?? null,
            'ips'            => $config['ips'] ?? null,
            'cp'             => $config['cp'] ?? null,
            'raid'           => $config['raid'] ?? null,
            'comments'       => $config['comment'] ?? '',
        ];

        $result = $interserver->placeBuyNowOrder((int) $config['asset_id'], $payload);

        return [$result, [...$payload, 'rootPassword' => '[redacted]']];
    }

    private function markProvisioned(Model $order, array $result): void
    {
        $config = $order->config;
        $interserverId = $order instanceof DedicatedServerOrder
            ? ($result['order_details']['service_id'] ?? null)
            : ($result['serviceid'] ?? null);

        $order->update([
            'status' => 'provisioned',
            ...($order instanceof DedicatedServerOrder
                ? ['interserver_server_id' => $interserverId]
                : ['interserver_vps_id' => $interserverId]),
        ]);

        ServerInstance::updateOrCreate(
            ['orderable_type' => $order->getMorphClass(), 'orderable_id' => $order->id],
            [
                'client_id'                => $order->client_id,
                'interserver_id'           => $interserverId,
                'hostname'                 => $config['hostname'] ?? null,
                'root_username'            => 'root',
                'root_password_encrypted'  => $config['rootpass'] ?? null,
                'os'                       => $config['osDistro'] ?? $config['os'] ?? null,
                'location'                 => isset($config['location']) ? (string) $config['location'] : null,
                'status'                   => 'active',
                'provisioned_at'           => now(),
                'renewal_at'               => $this->estimateRenewalDate($config),
            ],
        );

        $client = Client::find($order->client_id);
        $planName = $config['plan_name'] ?? $config['platform'] ?? ($config['listing']['cpu'][0] ?? 'Server');

        if ($client) {
            Mail::to($client->email)->send(new VpsProvisionedMail(
                firstName: $client->firstname,
                hostname: $config['hostname'] ?? 'your server',
                planName: $planName,
                orderId: $order->id,
                invoiceId: $order->invoice_id,
            ));
        }

        ServerProvisioned::dispatch($order);

        Log::info('Server provisioned', ['order_type' => $order->getMorphClass(), 'order_id' => $order->id, 'interserver_id' => $interserverId]);
    }

    private function estimateRenewalDate(array $config): ?\Illuminate\Support\Carbon
    {
        $months = (int) ($config['period'] ?? 1);

        return $months > 0 ? now()->addMonths($months) : null;
    }

    public function failed(?Throwable $exception): void
    {
        $order = $this->order->fresh();

        if (! $order) {
            return;
        }

        $reason = $exception?->getMessage() ?? 'Unknown provisioning error';

        $order->update([
            'status'         => 'failed',
            'failure_reason' => $reason,
        ]);

        $client = Client::find($order->client_id);
        $config = $order->config;
        $planName = $config['plan_name'] ?? $config['platform'] ?? 'Server';

        if ($client) {
            Mail::to($client->email)->send(new VpsFailedMail(
                firstName: $client->firstname,
                planName: $planName,
                orderId: $order->id,
                invoiceId: $order->invoice_id,
            ));
        }

        Log::error('Server provisioning failed permanently', [
            'order_type' => $order->getMorphClass(),
            'order_id'   => $order->id,
            'reason'     => $reason,
        ]);

        ServerProvisioningFailed::dispatch($order, $reason);
    }
}
