<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $services = $this->whmcs->getClientServices(session('clientId'));
        return view('dashboard.servers.index', compact('services'));
    }

    public function show(int $id): View
    {
        $service = $this->whmcs->getServiceDetails($id);

        if (empty($service)) {
            abort(404, 'Service not found.');
        }

        return view('dashboard.servers.show', compact('service'));
    }

    public function action(Request $request, int $id): JsonResponse
    {
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
}
