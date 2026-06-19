<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\WhmcsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    private const CYCLE_LABELS = [
        'monthly'      => 'Monthly',
        'quarterly'    => 'Quarterly',
        'semiannually' => 'Semi-Annually',
        'annually'     => 'Annually',
        'biennially'   => 'Biennially',
        'triennially'  => 'Triennially',
    ];

    public function __construct(private WhmcsService $whmcs) {}

    public function index(): View
    {
        $products = collect($this->whmcs->getProducts())->map(function (array $product) {
            $pricing = $product['pricing']['USD'] ?? [];

            $cycles = [];
            foreach (self::CYCLE_LABELS as $key => $label) {
                $price = (float) ($pricing[$key] ?? -1);
                if ($price >= 0) {
                    $cycles[$key] = ['label' => $label, 'price' => $price];
                }
            }

            $product['cycles']  = $cycles;
            $product['setupfee'] = (float) ($pricing['msetupfee'] ?? 0);

            return $product;
        })->filter(fn ($product) => ! empty($product['cycles']))->values()->all();

        return view('dashboard.servers.order', compact('products'));
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'pid'          => ['required', 'integer'],
            'billingcycle' => ['required', 'string', 'in:' . implode(',', array_keys(self::CYCLE_LABELS))],
        ]);

        $paymentMethods = $this->whmcs->getPaymentMethods();
        $paymentMethod  = $paymentMethods[0]['module'] ?? 'banktransfer';

        $result = $this->whmcs->addOrder([
            'clientid'     => session('clientId'),
            'pid'          => $request->pid,
            'billingcycle' => self::CYCLE_LABELS[$request->billingcycle],
            'paymentmethod' => $paymentMethod,
        ]);

        if (($result['result'] ?? '') !== 'success') {
            return back()->with('error', $result['message'] ?? 'Unable to place order. Please try again.');
        }

        if (! empty($result['invoiceid'])) {
            return redirect()
                ->route('billing.show', $result['invoiceid'])
                ->with('success', 'Your order has been placed! Complete payment below to activate your server.');
        }

        return redirect()
            ->route('servers.index')
            ->with('success', 'Your order has been placed! We will provision your server shortly.');
    }
}
