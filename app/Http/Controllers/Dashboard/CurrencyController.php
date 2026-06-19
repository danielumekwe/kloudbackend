<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Support\CurrencyConverter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CurrencyController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'currency' => ['required', 'string'],
        ]);

        $currency = CurrencyConverter::find($validated['currency']);

        if (! $currency) {
            return back()->with('error', 'That currency is not available.');
        }

        session(['currency' => $currency['code']]);

        return back()->with('success', "Currency switched to {$currency['code']}.");
    }
}
