<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NotificationsController extends Controller
{
    public function index(): View
    {
        $client = Client::findOrFail(session('clientId'));

        $notifications = $client->notifications()->latest()->paginate(25);

        return view('dashboard.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $client = Client::findOrFail(session('clientId'));

        $client->notifications()->where('id', $id)->first()?->markAsRead();

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $client = Client::findOrFail(session('clientId'));

        $client->unreadNotifications->markAsRead();

        return back();
    }
}
