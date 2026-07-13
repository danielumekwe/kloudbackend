<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Models\PaymentTransaction;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use App\Services\TicketService;
use App\Support\CurrencyConverter;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private TicketService $tickets) {}

    public function index(): View
    {
        $stats = [
            'total_revenue'       => $this->revenueSince(null),
            'revenue_this_month'  => $this->revenueSince(now()->startOfMonth()),
            'active_vps'          => VpsOrder::where('status', 'provisioned')->count(),
            'active_domains'      => DomainOrder::where('status', 'provisioned')->count(),
            'pending_orders'      => VpsOrder::where('status', 'pending_payment')->count()
                + SslOrder::where('status', 'pending_payment')->count()
                + QsOrder::where('status', 'pending_payment')->count()
                + DomainOrder::where('status', 'pending_payment')->count(),
        ];

        // Cached briefly so refreshing the dashboard doesn't re-run these aggregates
        // on every load. Tickets are local since Phase 1 of the WHMCS exit, read fresh.
        $billingStats = Cache::remember('admin.dashboard.billing_stats', now()->addMinutes(2), function () {
            $unpaid = Invoice::where('status', 'unpaid')->get(['total', 'currency_code']);

            return [
                'pending_invoices'   => $unpaid->count(),
                'cancelled_invoices' => Invoice::where('status', 'cancelled')->count(),
                'paid_invoices'      => Invoice::where('status', 'paid')->count(),
                'revenue_waiting'    => round($unpaid->sum(
                    fn (Invoice $invoice) => CurrencyConverter::convertToUsd((float) $invoice->total, $invoice->currency_code)
                ), 2),
            ];
        });

        $billingStats['open_tickets'] = $this->tickets->getOpenTicketCount();

        return view('admin.dashboard', [
            'stats'        => $stats,
            'billingStats' => $billingStats,
            'revenueChart' => $this->dailyRevenueChart(),
        ]);
    }

    private function revenueSince(?\Carbon\Carbon $since): float
    {
        $query = PaymentTransaction::where('status', 'completed');

        if ($since) {
            $query->where('created_at', '>=', $since);
        }

        return round(
            $query->get(['amount', 'currency'])
                ->sum(fn (PaymentTransaction $t) => CurrencyConverter::convertToUsd((float) $t->amount, $t->currency)),
            2
        );
    }

    /**
     * Daily completed-payment totals (converted to USD) for the last 30 days.
     */
    private function dailyRevenueChart(): array
    {
        $transactions = PaymentTransaction::where('status', 'completed')
            ->where('created_at', '>=', now()->subDays(29)->startOfDay())
            ->get(['amount', 'currency', 'created_at']);

        $byDay = $transactions
            ->groupBy(fn (PaymentTransaction $t) => $t->created_at->format('Y-m-d'))
            ->map(fn ($group) => round(
                $group->sum(fn (PaymentTransaction $t) => CurrencyConverter::convertToUsd((float) $t->amount, $t->currency)),
                2
            ));

        $labels = [];
        $data = [];

        for ($i = 29; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $labels[] = $day->format('M j');
            $data[] = $byDay->get($day->format('Y-m-d'), 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
