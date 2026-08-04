<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class CurrencyController extends Controller
{
    public function store(): RedirectResponse
    {
        session(['currency' => 'NGN']);

        return back()->with('error', 'Currency switching is temporarily disabled — all billing is in Naira.');
    }
}
