<?php

namespace App\Providers;

use App\Models\Invoice;
use App\Support\CurrencyConverter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('layouts.app', function ($view) {
            $view->with('availableCurrencies', CurrencyConverter::available());

            $cartInvoices = collect();

            if (session()->has('clientId')) {
                $cartInvoices = Invoice::where('client_id', session('clientId'))
                    ->where('status', 'unpaid')
                    ->latest()
                    ->get();
            }

            $view->with('cartInvoices', $cartInvoices);
        });
    }
}
