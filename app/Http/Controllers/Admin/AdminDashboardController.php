<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Models\PaymentTransaction;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use App\Services\TicketService;
use App\Services\WhmcsService;
use App\Support\CurrencyConverter;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AdminDashboardController extends Controller
{
    public function __construct(private WhmcsService $whmcs, private TicketService $tickets) {}

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

        // WHMCS round-trips are the expensive part of this page — cached briefly so
        // refreshing the dashboard doesn't hammer the WHMCS API on every load. Tickets
        // are local now (Phase 1 of the WHMCS exit) so they're read fresh, uncached.
        $whmcsStats = Cache::remember('admin.dashboard.whmcs_stats', now()->addMinutes(2), function () {
            $outstanding = $this->whmcs->getOutstandingInvoices();

            return [
                'pending_invoices'  => $this->whmcs->getInvoiceCountByStatus('Unpaid'),
                'overdue_invoices'  => $this->whmcs->getInvoiceCountByStatus('Overdue'),
                'paid_invoices'     => $this->whmcs->getInvoiceCountByStatus('Paid'),
                'revenue_waiting'   => round(array_sum(array_map(
                    fn (array $invoice) => CurrencyConverter::convertToUsd(
                        (float) ($invoice['balance'] ?? $invoice['total'] ?? 0),
                        $invoice['currencycode'] ?? 'USD'
                    ),
                    $outstanding
                )), 2),
            ];
        });

        $whmcsStats['open_tickets'] = $this->tickets->getOpenTicketCount();

        return view('admin.dashboard', [
            'stats'        => $stats,
            'whmcsStats'   => $whmcsStats,
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
