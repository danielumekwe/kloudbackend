<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Services\WhmcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $services = $this->whmcsClientId() ? $this->whmcs->getClientServices($this->whmcsClientId()) : [];
        return view('dashboard.servers.index', compact('services'));
    }

    public function show(int $id): View
    {
        $this->ensureOwnsService($id);

        $service = $this->whmcs->getServiceDetails($id);

        if (empty($service)) {
            abort(404, 'Service not found.');
        }

        return view('dashboard.servers.show', compact('service'));
    }

    public function action(Request $request, int $id): JsonResponse
    {
        $this->ensureOwnsService($id);

        $request->validate([
            'command'  => ['required', 'string', 'in:poweron,poweroff,reboot,reinstall,changepassword,suspend,unsuspend'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $params = [];
        if ($request->command === 'changepassword' && $request->filled('password')) {
            $params['newpassword'] = $request->password;
        }

        $result = $this->whmcs->moduleCommand($id, $request->command, $params);

        if (($result['result'] ?? '') !== 'success') {
            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'The action could not be completed.',
            ], 422);
        }

        $labels = [
            'poweron'        => 'Server powered on successfully.',
            'poweroff'       => 'Server powered off successfully.',
            'reboot'         => 'Server is rebooting.',
            'reinstall'      => 'Server reinstall has been initiated.',
            'changepassword' => 'Root password changed successfully.',
            'suspend'        => 'Server suspended.',
            'unsuspend'      => 'Server unsuspended.',
        ];

        return response()->json([
            'success' => true,
            'message' => $labels[$request->command] ?? 'Action completed successfully.',
        ]);
    }

    /**
     * GetClientsProducts (single, by serviceid) takes no clientid filter, so without
     * this check any logged-in client could power off/reinstall/change the root
     * password of any other client's server just by guessing its id.
     * GetClientsProducts(clientid) is the one WHMCS endpoint that's actually scoped,
     * so it doubles as the ownership check here.
     */
    private function ensureOwnsService(int $id): void
    {
        $services = $this->whmcsClientId() ? $this->whmcs->getClientServices($this->whmcsClientId()) : [];

        $owns = collect($services)->contains(fn (array $service) => (int) ($service['id'] ?? 0) === $id);

        if (! $owns) {
            abort(404, 'Service not found.');
        }
    }

    /**
     * Resolves through whmcs_client_id, never the local session clientId directly —
     * they only coincide for clients migrated before the WHMCS exit. Null if this
     * client has no WHMCS shadow record (yet).
     */
    private function whmcsClientId(): ?int
    {
        return Client::find(session('clientId'))?->whmcs_client_id;
    }
}
