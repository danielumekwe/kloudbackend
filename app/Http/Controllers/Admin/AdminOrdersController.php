<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\DomainOrder;
use App\Models\QsOrder;
use App\Models\SslOrder;
use App\Models\VpsOrder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminOrdersController extends Controller
{
    public function index(Request $request): View
    {
        $type   = $request->query('type', 'all');
        $status = $request->query('status', 'all');

        $vps = $qs = $ssl = $domain = collect();

        if ($type === 'all' || $type === 'vps') {
            $vps = VpsOrder::with('client')->latest()->get()->map(fn ($o) => $this->normalize($o, 'VPS', $o->config['plan_name'] ?? $o->category ?? 'VPS'));
        }
        if ($type === 'all' || $type === 'qs') {
            $qs = QsOrder::with('client')->latest()->get()->map(fn ($o) => $this->normalize($o, 'Quick Server', $o->config['plan_name'] ?? 'Quick Server'));
        }
        if ($type === 'all' || $type === 'ssl') {
            $ssl = SslOrder::with('client')->latest()->get()->map(fn ($o) => $this->normalize($o, 'SSL', $o->config['product_name'] ?? $o->config['domain'] ?? 'SSL Certificate'));
        }
        if ($type === 'all' || $type === 'domain') {
            $domain = DomainOrder::with('client')->latest()->get()->map(fn ($o) => $this->normalize($o, 'Domain', $o->domain_name . '.' . $o->tld));
        }

        $orders = $vps->concat($qs)->concat($ssl)->concat($domain)
            ->sortByDesc('created_at')
            ->when($status !== 'all', fn ($c) => $c->where('status', $status))
            ->values();

        $stats = [
            'total'       => $orders->count(),
            'pending'     => $orders->whereIn('status', ['pending', 'provisioning'])->count(),
            'provisioned' => $orders->where('status', 'provisioned')->count(),
            'failed'      => $orders->where('status', 'failed')->count(),
        ];

        return view('admin.orders.index', compact('orders', 'stats', 'type', 'status'));
    }

    private function normalize(mixed $order, string $type, string $description): array
    {
        return [
            'id'          => $order->id,
            'type'        => $type,
            'description' => $description,
            'client'      => $order->client,
            'status'      => $order->status,
            'price'       => $order->price,
            'invoice_id'  => $order->invoice_id,
            'created_at'  => $order->created_at,
        ];
    }
}
