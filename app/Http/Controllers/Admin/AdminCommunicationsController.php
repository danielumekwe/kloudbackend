<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterMail;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\View\View;

class AdminCommunicationsController extends Controller
{
    public function newsletter(): View
    {
        $clientCount = Client::count();

        return view('admin.communications.newsletter', compact('clientCount'));
    }

    public function sendNewsletter(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body'    => ['required', 'string', 'max:10000'],
        ]);

        $clients = Client::whereNotNull('email_verified_at')->get();

        foreach ($clients as $client) {
            Mail::to($client->email)->queue(new NewsletterMail(
                firstName: $client->firstname,
                subject:   $validated['subject'],
                body:      $validated['body'],
            ));
        }

        return redirect()->route('admin.communications.newsletter')
            ->with('success', 'Newsletter queued for ' . $clients->count() . ' verified clients.');
    }
}
