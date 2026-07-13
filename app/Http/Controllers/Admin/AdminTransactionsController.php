<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminTransactionsController extends Controller
{
    public function index(Request $request): View
    {
        $gateway = $request->query('gateway', 'all');
        $status  = $request->query('status', 'all');

        $transactions = PaymentTransaction::with(['client', 'invoice'])
            ->when($gateway !== 'all', fn ($q) => $q->where('gateway', $gateway))
            ->when($status !== 'all',  fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        $gateways = PaymentTransaction::distinct()->pluck('gateway')->sort()->values();

        $stats = [
            'total'     => PaymentTransaction::count(),
            'completed' => PaymentTransaction::where('status', 'completed')->count(),
            'volume'    => PaymentTransaction::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.transactions.index', compact('transactions', 'gateways', 'stats', 'gateway', 'status'));
    }
}
