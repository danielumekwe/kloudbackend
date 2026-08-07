<?php

namespace App\Providers;

use App\Mail\Transport\ZeptoMailApiTransport;
use App\Models\Admin;
use App\Models\Client;
use App\Models\Invoice;
use App\Support\CurrencyConverter;
use Illuminate\Support\Facades\Mail;
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
        Mail::extend('zeptomail-api', function (array $config = []) {
            return new ZeptoMailApiTransport($config['token'] ?? config('services.zeptomail.token'));
        });

        View::composer('layouts.app', function ($view) {
            $view->with('availableCurrencies', CurrencyConverter::available());

            $cartInvoices = collect();
            $bellNotifications = collect();

            if (session()->has('clientId')) {
                $cartInvoices = Invoice::where('client_id', session('clientId'))
                    ->where('status', 'unpaid')
                    ->latest()
                    ->get();

                if ($client = Client::find(session('clientId'))) {
                    $bellNotifications = $client->notifications()->latest()->limit(8)->get();
                }
            }

            $view->with('cartInvoices', $cartInvoices);
            $view->with('bellNotifications', $bellNotifications);
        });

        View::composer('layouts.admin', function ($view) {
            $bellNotifications = collect();

            if (session('isAdmin') && session('adminId') && ($admin = Admin::find(session('adminId')))) {
                $bellNotifications = $admin->notifications()->latest()->limit(8)->get();
            }

            $view->with('bellNotifications', $bellNotifications);
        });
    }
}
