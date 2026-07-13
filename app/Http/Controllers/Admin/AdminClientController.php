<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Controller;
use App\Mail\AdminMessageMail;
use App\Mail\PasswordResetMail;
use App\Models\Client;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use App\Services\TicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AdminClientController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(Request $request): View
    {
        $query = trim((string) $request->query('q', ''));

        if ($query !== '' && ($exact = Client::findByAccountCode($query))) {
            return redirect()->route('admin.clients.show', $exact);
        }

        $clients = Client::query()
            ->when($query !== '', fn ($q) => $q->where(function ($q) use ($query) {
                $q->where('email', 'like', "%{$query}%")
                    ->orWhere('firstname', 'like', "%{$query}%")
                    ->orWhere('lastname', 'like', "%{$query}%");
            }))
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.clients.index', compact('clients', 'query'));
    }

    public function show(Client $client): View
    {
        $tickets  = $this->tickets->getTickets($client->id);
        $invoices = Invoice::where('client_id', $client->id)->latest()->get();

        $vps    = VpsOrder::where('client_id', $client->id)->latest()->get()->map(fn ($o) => ['type' => 'VPS',          'desc' => $o->config['plan_name'] ?? $o->category ?? 'VPS',             'status' => $o->status, 'price' => $o->price, 'invoice_id' => $o->invoice_id, 'created_at' => $o->created_at]);
        $qs     = QsOrder::where('client_id', $client->id)->latest()->get()->map(fn ($o) => ['type' => 'Quick Server',  'desc' => $o->config['plan_name'] ?? 'Quick Server',                     'status' => $o->status, 'price' => $o->price, 'invoice_id' => $o->invoice_id, 'created_at' => $o->created_at]);
        $ssl    = SslOrder::where('client_id', $client->id)->latest()->get()->map(fn ($o) => ['type' => 'SSL',          'desc' => $o->config['product_name'] ?? $o->config['domain'] ?? 'SSL',  'status' => $o->status, 'price' => $o->price, 'invoice_id' => $o->invoice_id, 'created_at' => $o->created_at]);
        $domain = DomainOrder::where('client_id', $client->id)->latest()->get()->map(fn ($o) => ['type' => 'Domain',   'desc' => $o->domain_name . '.' . $o->tld,                              'status' => $o->status, 'price' => $o->price, 'invoice_id' => $o->invoice_id, 'created_at' => $o->created_at]);
        $orders = $vps->concat($qs)->concat($ssl)->concat($domain)->sortByDesc('created_at')->values();

        return view('admin.clients.show', compact('client', 'tickets', 'invoices', 'orders'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'firstname'   => ['required', 'string', 'max:100'],
            'lastname'    => ['required', 'string', 'max:100'],
            'email'       => ['required', 'email', 'max:200', 'unique:clients,email,' . $client->id],
            'phonenumber' => ['nullable', 'string', 'max:30'],
            'address1'    => ['nullable', 'string', 'max:200'],
            'city'        => ['nullable', 'string', 'max:100'],
            'state'       => ['nullable', 'string', 'max:100'],
            'postcode'    => ['nullable', 'string', 'max:20'],
            'country'     => ['nullable', 'string', 'size:2'],
        ]);

        $client->update($validated);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client profile updated.');
    }

    public function suspend(Client $client): RedirectResponse
    {
        $client->update(['suspended_at' => now()]);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client suspended — they will be logged out and cannot sign in until unsuspended.');
    }

    public function unsuspend(Client $client): RedirectResponse
    {
        $client->update(['suspended_at' => null]);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Client unsuspended.');
    }

    /**
     * Same token/link mechanism as the client-facing "forgot password" flow (see
     * PasswordResetController) — admin just triggers the send on the client's behalf
     * instead of the client requesting it themselves.
     */
    public function resetPassword(Client $client): RedirectResponse
    {
        $token = Str::random(64);

        DB::table('password_reset_tokens')->updateOrInsert(
            ['email' => $client->email],
            ['token' => Hash::make($token), 'created_at' => now()]
        );

        $resetUrl = route('password.reset', ['token' => $token, 'email' => $client->email]);

        Mail::to($client->email)->send(new PasswordResetMail($resetUrl, $client->firstname));

        return redirect()->route('admin.clients.show', $client)->with('success', 'Password reset link sent to ' . $client->email . '.');
    }

    public function resendVerification(Client $client): RedirectResponse
    {
        if ($client->isEmailVerified()) {
            return redirect()->route('admin.clients.show', $client)->with('error', 'This client is already verified.');
        }

        EmailVerificationController::send($client);

        return redirect()->route('admin.clients.show', $client)->with('success', 'Verification email re-sent to ' . $client->email . '.');
    }

    public function addCredit(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01', 'max:10000'],
            'note'   => ['nullable', 'string', 'max:200'],
        ]);

        $client->increment('credit_balance', $validated['amount']);

        return redirect()->route('admin.clients.show', $client)
            ->with('success', sprintf('Added $%.2f credit to %s %s. New balance: $%.2f.',
                $validated['amount'], $client->firstname, $client->lastname, (float) $client->fresh()->credit_balance));
    }

    public function sendEmail(Request $request, Client $client): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body'    => ['required', 'string', 'max:5000'],
        ]);

        Mail::to($client->email)->send(new AdminMessageMail(
            firstName: $client->firstname,
            subject:   $validated['subject'],
            body:      $validated['body'],
        ));

        return redirect()->route('admin.clients.show', $client)
            ->with('success', 'Email sent to ' . $client->email . '.');
    }
}
