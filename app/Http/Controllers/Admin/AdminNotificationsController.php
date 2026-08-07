<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminNotificationsController extends Controller
{
    public function index(): View
    {
        $admin = Admin::findOrFail(session('adminId'));

        $notifications = $admin->notifications()->latest()->paginate(25);

        return view('admin.notifications.index', compact('notifications'));
    }

    public function markRead(Request $request, string $id): RedirectResponse
    {
        $admin = Admin::findOrFail(session('adminId'));

        $admin->notifications()->where('id', $id)->first()?->markAsRead();

        return back();
    }

    public function markAllRead(): RedirectResponse
    {
        $admin = Admin::findOrFail(session('adminId'));

        $admin->unreadNotifications->markAsRead();

        return back();
    }
}
