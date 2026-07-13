<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\ServiceCancelledMail;
use App\Models\Client;
use App\Models\DomainOrder;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use App\Services\InterServerService;
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

        $vps = $qs = $ssl = $domain = collect();

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
        }

        $stats = [
            'vps_active'    => VpsOrder::where('status', 'provisioned')->count(),
            'vps_suspended' => VpsOrder::where('status', 'suspended')->count(),
            'qs_active'     => QsOrder::where('status', 'provisioned')->count(),
            'ssl_active'    => SslOrder::where('status', 'provisioned')->count(),
            'domain_active' => DomainOrder::where('status', 'provisioned')->count(),
        ];

        return view('admin.services.index', compact('vps', 'qs', 'ssl', 'domain', 'stats', 'type', 'status'));
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

        return view('admin.services.vps-show', compact('order', 'liveData', 'templates'));
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
            $this->notifyCancelled($order->client_id, 'VPS', $order->config['hostname'] ?? 'VPS', $order->id, 'suspended');
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
            return redirect()->away($url);
        }
        return back()->with('error', 'Console URL not available: ' . ($result['message'] ?? 'try again in a moment'));
    }

    public function vpsCancel(VpsOrder $order): RedirectResponse
    {
        if (! $order->interserver_vps_id) {
            $order->update(['status' => 'cancelled']);
            return redirect()->route('admin.services.index')->with('success', 'Order marked as cancelled (no InterServer record).');
        }
        $result = $this->interserver->cancelVps($order->interserver_vps_id);
        if (($result['success'] ?? false) || ($result['text'] ?? '') === 'VPS is canceled.') {
            $order->update(['status' => 'cancelled']);
            $this->notifyCancelled($order->client_id, 'VPS', $order->config['hostname'] ?? 'VPS', $order->id, 'cancelled');
            return redirect()->route('admin.services.index')->with('success', 'VPS cancelled on InterServer.');
        }
        return back()->with('error', 'Failed to cancel VPS: ' . ($result['message'] ?? 'unknown error'));
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
}
