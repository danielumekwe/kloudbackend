<?php

namespace App\Http\Controllers\Admin;

use App\Events\ServerActionPerformed;
use App\Http\Controllers\Controller;
use App\Mail\ServiceCancelledMail;
use App\Models\Client;
use App\Models\DedicatedServerOrder;
use App\Models\DomainOrder;
use App\Models\QsOrder;
use App\Models\ServerInstance;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use App\Services\InterServerService;
use App\Services\InvoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminServicesController extends Controller
{
    public function __construct(private InterServerService $interserver) {}

    public function index(Request $request): View
    {
        $type   = $request->query('type', 'vps');
        $status = $request->query('status', 'all');

        $vps = $qs = $ssl = $domain = $dedicated = collect();

        if ($type === 'vps') {
            $vps = VpsOrder::with('client')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString();
        } elseif ($type === 'qs') {
            $qs = QsOrder::with('client')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString();
        } elseif ($type === 'ssl') {
            $ssl = SslOrder::with('client')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString();
        } elseif ($type === 'domain') {
            $domain = DomainOrder::with('client')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString();
        } elseif ($type === 'dedicated') {
            $dedicated = DedicatedServerOrder::with('client')
                ->when($status !== 'all', fn ($q) => $q->where('status', $status))
                ->latest()->paginate(25)->withQueryString();
        }

        $stats = [
            'vps_active'       => VpsOrder::where('status', 'provisioned')->count(),
            'vps_suspended'    => VpsOrder::where('status', 'suspended')->count(),
            'qs_active'        => QsOrder::where('status', 'provisioned')->count(),
            'ssl_active'       => SslOrder::where('status', 'provisioned')->count(),
            'domain_active'    => DomainOrder::where('status', 'provisioned')->count(),
            'dedicated_active' => DedicatedServerOrder::where('status', 'provisioned')->count(),
        ];

        return view('admin.services.index', compact('vps', 'qs', 'ssl', 'domain', 'dedicated', 'stats', 'type', 'status'));
    }

    public function showVps(VpsOrder $order): View
    {
        $order->load('client', 'invoice');
        $liveData  = [];
        $templates = [];

        if ($order->interserver_vps_id) {
            $liveData  = $this->interserver->getVps($order->interserver_vps_id);
            $templates = $this->interserver->getReinstallTemplates($order->interserver_vps_id)['os'] ?? [];
        }

        $instance = ServerInstance::where('orderable_type', VpsOrder::class)->where('orderable_id', $order->id)->first();

        $activity = \App\Models\ActivityLog::where('subject_type', VpsOrder::class)
            ->where('subject_id', $order->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('admin.services.vps-show', compact('order', 'liveData', 'templates', 'instance', 'activity'));
    }

    public function showDedicated(DedicatedServerOrder $order): View
    {
        $order->load('client', 'invoice');
        $liveData = [];

        if ($order->interserver_server_id) {
            $liveData = $this->interserver->getServer($order->interserver_server_id);
        }

        $instance = ServerInstance::where('orderable_type', DedicatedServerOrder::class)->where('orderable_id', $order->id)->first();

        $activity = \App\Models\ActivityLog::where('subject_type', DedicatedServerOrder::class)
            ->where('subject_id', $order->id)
            ->latest('created_at')
            ->limit(50)
            ->get();

        return view('admin.services.dedicated-show', compact('order', 'liveData', 'instance', 'activity'));
    }

    public function showQs(QsOrder $order): View
    {
        $order->load('client', 'invoice');
        $liveData  = [];
        $templates = [];

        if ($order->interserver_qs_id) {
            $liveData  = $this->interserver->getQs($order->interserver_qs_id);
            $templates = $this->interserver->getQsReinstallTemplates($order->interserver_qs_id)['os'] ?? [];
        }

        return view('admin.services.qs-show', compact('order', 'liveData', 'templates'));
    }

    // -------------------------------------------------------------------------
    // VPS management actions
    // -------------------------------------------------------------------------

    public function vpsStart(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->startVps($order->interserver_vps_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'provisioned']);
            $this->syncInstanceStatus($order, 'active');
            $this->logAction($order, 'started', 'Admin started the VPS.');
            return back()->with('success', 'VPS start command sent.');
        }
        return back()->with('error', 'Failed to start VPS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function vpsSuspend(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->stopVps($order->interserver_vps_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'suspended']);
            $this->syncInstanceStatus($order, 'suspended');
            $this->notifyCancelled($order->client_id, 'VPS', $order->config['hostname'] ?? 'VPS', $order->id, 'suspended');
            $this->logAction($order, 'suspended', 'Admin suspended the VPS.');
            return back()->with('success', 'VPS suspended (stopped).');
        }
        return back()->with('error', 'Failed to suspend VPS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function vpsUnsuspend(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->startVps($order->interserver_vps_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'provisioned']);
            $this->syncInstanceStatus($order, 'active');
            $this->logAction($order, 'unsuspended', 'Admin unsuspended the VPS.');
            return back()->with('success', 'VPS unsuspended (started).');
        }
        return back()->with('error', 'Failed to unsuspend VPS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function vpsRestart(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->restartVps($order->interserver_vps_id);
        if ($result['success'] ?? false) {
            $this->logAction($order, 'restarted', 'Admin restarted the VPS.');
            return back()->with('success', 'VPS restart command sent.');
        }
        return back()->with('error', 'Failed to restart VPS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function vpsChangePassword(Request $request, VpsOrder $order): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);

        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->changeRootPassword($order->interserver_vps_id, $request->password);
        if ($result['success'] ?? false) {
            $this->logAction($order, 'password_reset', 'Admin changed the root password.', visibleToClient: false);
            return back()->with('success', 'Root password changed successfully.');
        }
        return back()->with('error', 'Failed to change password: ' . ($result['message'] ?? 'unknown error'));
    }

    public function vpsReinstallOs(Request $request, VpsOrder $order): RedirectResponse
    {
        $request->validate([
            'template'      => ['required', 'string'],
            'localPassword' => ['required', 'string', 'min:8'],
        ]);

        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->reinstallOs(
            $order->interserver_vps_id,
            $request->template,
            $request->localPassword,
        );
        if ($result['success'] ?? false) {
            $this->logAction($order, 'reinstalled', "Admin reinstalled the OS ({$request->template}).");
            return back()->with('success', 'OS reinstall initiated. The server will be ready in a few minutes.');
        }
        return back()->with('error', 'Failed to reinstall OS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function vpsConsole(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            return back()->with('error', 'No InterServer VPS ID on this order.');
        }
        $result = $this->interserver->getViewDesktop($order->interserver_vps_id);
        $url    = $result['url'] ?? $result['console_url'] ?? null;
        if ($url) {
            $this->logAction($order, 'console_opened', 'Admin opened the console.', visibleToClient: false);
            return redirect()->away($url);
        }
        return back()->with('error', 'Console URL not available: ' . ($result['message'] ?? 'try again in a moment'));
    }

    public function vpsCancel(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            $order->update(['status' => 'cancelled']);
            $this->syncInstanceStatus($order, 'terminated');
            $this->logAction($order, 'cancelled', 'Admin cancelled the order (no InterServer record).');
            return redirect()->route('admin.services.index')->with('success', 'Order marked as cancelled (no InterServer record).');
        }
        $result = $this->interserver->cancelVps($order->interserver_vps_id);
        if (($result['success'] ?? false) || ($result['text'] ?? '') === 'VPS is canceled.') {
            $order->update(['status' => 'cancelled']);
            $this->syncInstanceStatus($order, 'terminated');
            $this->notifyCancelled($order->client_id, 'VPS', $order->config['hostname'] ?? 'VPS', $order->id, 'cancelled');
            $this->logAction($order, 'cancelled', 'Admin cancelled the VPS on InterServer.');
            return redirect()->route('admin.services.index')->with('success', 'VPS cancelled on InterServer.');
        }
        return back()->with('error', 'Failed to cancel VPS: ' . ($result['message'] ?? 'unknown error'));
    }

    /**
     * Catch-all for the long tail of InterServerService actions the customer
     * dashboard already exposes (backups, RDNS, VNC, hostname, timezone,
     * resize, buy-IP, ISO, quota, SMTP-block, Webuzo password) — mirrors
     * Dashboard\VpsController::action() so admins get parity without a
     * separate route/method per action.
     */
    public function vpsAction(Request $request, VpsOrder $order): JsonResponse
    {
        if (! $order->interserver_vps_id) {
            return response()->json(['success' => false, 'message' => 'No InterServer VPS ID on this order.'], 422);
        }

        $vpsId = $order->interserver_vps_id;

        $request->validate([
            'command'  => ['required', 'string', 'in:' . implode(',', [
                'backup', 'deletebackup', 'downloadbackup', 'restore',
                'blocksmtp', 'changehostname', 'changetimezone', 'changewebuzopassword',
                'disablecd', 'ejectcd', 'insertcd', 'disablequota', 'enablequota',
                'reversedns', 'setupvnc', 'trafficusage', 'buyhdspace', 'buyip', 'resizeslices',
            ])],
            'password' => ['nullable', 'string', 'min:8'],
            'file'     => ['nullable', 'string'],
            'backup'   => ['nullable', 'string'],
            'hostname' => ['nullable', 'string'],
            'timezone' => ['nullable', 'string'],
            'url'      => ['nullable', 'string'],
            'vnc'      => ['nullable', 'string'],
            'ips'      => ['nullable', 'array'],
            'size'     => ['nullable', 'integer'],
            'slices'   => ['nullable', 'integer'],
        ]);

        $result = match ($request->command) {
            'backup'          => $this->interserver->getVpsBackup($vpsId),
            'deletebackup'    => $request->filled('file')
                ? $this->interserver->deleteVpsBackup($vpsId, $request->file)
                : ['error' => true, 'message' => 'A backup filename is required.'],
            'downloadbackup'  => $request->filled('file')
                ? $this->interserver->downloadVpsBackup($vpsId, $request->file)
                : ['error' => true, 'message' => 'A backup filename is required.'],
            'restore'         => ($request->filled('backup') && $request->filled('password'))
                ? $this->interserver->restoreVps($vpsId, $request->backup, $request->password)
                : ['error' => true, 'message' => 'A backup filename and the InterServer account password are required.'],
            'blocksmtp'       => $this->interserver->blockSmtp($vpsId),
            'changehostname'  => $request->filled('hostname')
                ? $this->interserver->changeHostname($vpsId, $request->hostname)
                : ['error' => true, 'message' => 'A hostname is required.'],
            'changetimezone'  => $request->filled('timezone')
                ? $this->interserver->changeTimezone($vpsId, $request->timezone)
                : ['error' => true, 'message' => 'A timezone is required.'],
            'changewebuzopassword' => $request->filled('password')
                ? $this->interserver->changeWebuzoPassword($vpsId, $request->password)
                : ['error' => true, 'message' => 'A new password is required.'],
            'disablecd'       => $this->interserver->disableCd($vpsId),
            'ejectcd'         => $this->interserver->ejectCd($vpsId),
            'insertcd'        => $request->filled('url')
                ? $this->interserver->insertCd($vpsId, $request->url)
                : ['error' => true, 'message' => 'An ISO URL is required.'],
            'disablequota'    => $this->interserver->disableQuota($vpsId),
            'enablequota'     => $this->interserver->enableQuota($vpsId),
            'reversedns'      => $request->filled('ips')
                ? $this->interserver->setReverseDns($vpsId, $request->ips)
                : ['error' => true, 'message' => 'PTR records are required.'],
            'setupvnc'        => $request->filled('vnc')
                ? $this->interserver->setupVnc($vpsId, $request->vnc)
                : ['error' => true, 'message' => 'A source IP is required.'],
            'trafficusage'    => $this->interserver->getTrafficUsage($vpsId),
            'buyhdspace'      => $request->filled('size')
                ? $this->interserver->buyHdSpace($vpsId, (int) $request->size)
                : ['error' => true, 'message' => 'A target disk size in GB is required.'],
            'buyip'           => $this->interserver->buyIp($vpsId),
            'resizeslices'    => $request->filled('slices')
                ? $this->interserver->resizeSlices($vpsId, (int) $request->slices)
                : ['error' => true, 'message' => 'A target slice count is required.'],
        };

        if ($result['error'] ?? false) {
            return response()->json(['success' => false, 'message' => $result['message'] ?? 'The action could not be completed.'], 422);
        }

        $this->logAction($order, $request->command, "Admin ran \"{$request->command}\" on the VPS.", visibleToClient: false);

        return response()->json(['success' => true, 'message' => $result['text'] ?? 'Action completed successfully.', 'data' => $result]);
    }

    // -------------------------------------------------------------------------
    // Dedicated Server management (billing/status only — InterServerService has
    // no power-lifecycle endpoints for bare metal today; see cancelServer/
    // getServer/listServers. Power/reinstall/rescue controls stay disabled in
    // the UI until those endpoints exist.)
    // -------------------------------------------------------------------------

    public function dedicatedSuspend(DedicatedServerOrder $order): RedirectResponse
    {
        $order->update(['status' => 'suspended']);
        $this->syncInstanceStatus($order, 'suspended');
        $this->notifyCancelled($order->client_id, 'Dedicated Server', $order->config['hostname'] ?? 'Dedicated Server', $order->id, 'suspended');
        $this->logAction($order, 'suspended', 'Admin suspended the Dedicated Server (billing status only).');

        return back()->with('success', 'Dedicated Server suspended.');
    }

    public function dedicatedUnsuspend(DedicatedServerOrder $order): RedirectResponse
    {
        $order->update(['status' => 'provisioned']);
        $this->syncInstanceStatus($order, 'active');
        $this->logAction($order, 'unsuspended', 'Admin unsuspended the Dedicated Server.');

        return back()->with('success', 'Dedicated Server unsuspended.');
    }

    public function dedicatedCancel(DedicatedServerOrder $order): RedirectResponse
    {
        if ($order->interserver_server_id) {
            $result = $this->interserver->cancelServer($order->interserver_server_id);
            if (! ($result['success'] ?? false)) {
                return back()->with('error', 'Failed to cancel: ' . ($result['message'] ?? 'unknown error'));
            }
        }

        $order->update(['status' => 'cancelled']);
        $this->syncInstanceStatus($order, 'terminated');
        $this->notifyCancelled($order->client_id, 'Dedicated Server', $order->config['hostname'] ?? 'Dedicated Server', $order->id, 'cancelled');
        $this->logAction($order, 'cancelled', 'Admin cancelled the Dedicated Server.');

        return redirect()->route('admin.services.index', ['type' => 'dedicated'])->with('success', 'Dedicated Server cancelled.');
    }

    /**
     * Generates a renewal invoice for the order's existing price/billing cycle
     * (VPS or Dedicated) — mirrors the DomainRenewal pattern used elsewhere.
     */
    public function renew(Request $request, string $orderType, int $orderId, InvoiceService $invoices): RedirectResponse
    {
        $order = $orderType === 'dedicated'
            ? DedicatedServerOrder::with('client')->findOrFail($orderId)
            : VpsOrder::with('client')->findOrFail($orderId);

        if (! $order->client) {
            return back()->with('error', 'This order has no associated client.');
        }

        $hostname = $order->config['hostname'] ?? 'server';
        $currency = $order->config['currency'] ?? 'NGN';
        $invoice = $invoices->createAt($order->client, "Renewal — {$hostname}", (float) $order->price, $currency);

        $this->logAction($order, 'renewal_invoiced', "Admin generated renewal invoice #{$invoice->id}.");

        return back()->with('success', "Renewal invoice #{$invoice->id} created for {$order->client->email}.");
    }

    // -------------------------------------------------------------------------
    // QS management actions (same pattern as VPS)
    // -------------------------------------------------------------------------

    public function qsStart(QsOrder $order): RedirectResponse
    {
        if (! $order->interserver_qs_id) {
            return back()->with('error', 'No InterServer QS ID on this order.');
        }
        $result = $this->interserver->startQs($order->interserver_qs_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'provisioned']);
            return back()->with('success', 'Quick Server start command sent.');
        }
        return back()->with('error', 'Failed to start QS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function qsSuspend(QsOrder $order): RedirectResponse
    {
        if (! $order->interserver_qs_id) {
            return back()->with('error', 'No InterServer QS ID on this order.');
        }
        $result = $this->interserver->stopQs($order->interserver_qs_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'suspended']);
            return back()->with('success', 'Quick Server suspended.');
        }
        return back()->with('error', 'Failed to suspend QS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function qsUnsuspend(QsOrder $order): RedirectResponse
    {
        if (! $order->interserver_qs_id) {
            return back()->with('error', 'No InterServer QS ID on this order.');
        }
        $result = $this->interserver->startQs($order->interserver_qs_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'provisioned']);
            return back()->with('success', 'Quick Server unsuspended.');
        }
        return back()->with('error', 'Failed to unsuspend QS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function qsChangePassword(Request $request, QsOrder $order): RedirectResponse
    {
        $request->validate(['password' => ['required', 'string', 'min:8']]);

        if (! $order->interserver_qs_id) {
            return back()->with('error', 'No InterServer QS ID on this order.');
        }
        $result = $this->interserver->changeQsRootPassword($order->interserver_qs_id, $request->password);
        if ($result['success'] ?? false) {
            return back()->with('success', 'Root password changed successfully.');
        }
        return back()->with('error', 'Failed to change password: ' . ($result['message'] ?? 'unknown error'));
    }

    public function qsReinstallOs(Request $request, QsOrder $order): RedirectResponse
    {
        $request->validate([
            'template'      => ['required', 'string'],
            'localPassword' => ['required', 'string', 'min:8'],
        ]);

        if (! $order->interserver_qs_id) {
            return back()->with('error', 'No InterServer QS ID on this order.');
        }
        $result = $this->interserver->reinstallQsOs(
            $order->interserver_qs_id,
            $request->template,
            $request->localPassword,
        );
        if ($result['success'] ?? false) {
            return back()->with('success', 'OS reinstall initiated.');
        }
        return back()->with('error', 'Failed to reinstall OS: ' . ($result['message'] ?? 'unknown error'));
    }

    public function qsConsole(QsOrder $order): RedirectResponse
    {
        if (! $order->interserver_qs_id) {
            return back()->with('error', 'No InterServer QS ID on this order.');
        }
        $result = $this->interserver->getQsViewDesktop($order->interserver_qs_id);
        $url    = $result['url'] ?? $result['console_url'] ?? null;
        if ($url) {
            return redirect()->away($url);
        }
        return back()->with('error', 'Console URL not available: ' . ($result['message'] ?? 'try again in a moment'));
    }

    public function qsCancel(QsOrder $order): RedirectResponse
    {
        if (! $order->interserver_qs_id) {
            $order->update(['status' => 'cancelled']);
            return redirect()->route('admin.services.index', ['type' => 'qs'])->with('success', 'Order marked as cancelled.');
        }
        $result = $this->interserver->cancelQs($order->interserver_qs_id);
        if ($result['success'] ?? false) {
            $order->update(['status' => 'cancelled']);
            $this->notifyCancelled($order->client_id, 'Quick Server', $order->config['plan_name'] ?? 'Quick Server', $order->id, 'cancelled');
            return redirect()->route('admin.services.index', ['type' => 'qs'])->with('success', 'Quick Server cancelled.');
        }
        return back()->with('error', 'Failed to cancel: ' . ($result['message'] ?? 'unknown error'));
    }

    // -------------------------------------------------------------------------

    private function notifyCancelled(int $clientId, string $type, string $description, int $orderId, string $event): void
    {
        $client = Client::find($clientId);
        if ($client && $event === 'cancelled') {
            Mail::to($client->email)->send(new ServiceCancelledMail(
                firstName:          $client->firstname,
                serviceType:        $type,
                serviceDescription: $description,
                orderId:            $orderId,
            ));
        }
    }

    private function syncInstanceStatus(VpsOrder|DedicatedServerOrder $order, string $status): void
    {
        ServerInstance::where('orderable_type', $order::class)
            ->where('orderable_id', $order->id)
            ->update(['status' => $status]);
    }

    private function logAction(VpsOrder|DedicatedServerOrder $order, string $action, string $description, bool $visibleToClient = true): void
    {
        event(new ServerActionPerformed($order, $action, $description, [], $visibleToClient));
    }
}
