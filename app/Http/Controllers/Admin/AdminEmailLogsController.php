<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\RawResendMail;
use App\Models\EmailLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminEmailLogsController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $logs = EmailLog::query()
            ->when($search !== '', fn ($q) => $q->where('to_email', 'like', "%{$search}%")
                ->orWhere('subject', 'like', "%{$search}%"))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.email-logs.index', compact('logs', 'search'));
    }

    public function resend(EmailLog $emailLog): RedirectResponse
    {
        if (! $emailLog->body_html) {
            return back()->with('error', 'This email has no stored content to resend.');
        }

        Mail::to($emailLog->to_email)->send(new RawResendMail($emailLog->subject, $emailLog->body_html));

        return back()->with('success', 'Email resent to ' . $emailLog->to_email . '.');
    }
}
