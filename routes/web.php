<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Api\LegalDocumentController;
use App\Http\Controllers\Admin\AdminBillingSettingsController;
use App\Http\Controllers\Admin\AdminCommunicationsController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminClientController;
use App\Http\Controllers\Admin\AdminInvoicesController;
use App\Http\Controllers\Admin\AdminOrdersController;
use App\Http\Controllers\Admin\AdminPricingController;
use App\Http\Controllers\Admin\AdminProductsController;
use App\Http\Controllers\Admin\AdminServicesController;
use App\Http\Controllers\Admin\AdminTicketController;
use App\Http\Controllers\Admin\AdminTransactionsController;
use App\Http\Controllers\Admin\AdminTwoFactorController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\SocialLoginController;
use App\Http\Controllers\Dashboard\BillingController;
use App\Http\Controllers\Dashboard\CurrencyController;
use App\Http\Controllers\Dashboard\DomainsController;
use App\Http\Controllers\Dashboard\HomeController;
use App\Http\Controllers\Dashboard\PaymentController;
use App\Http\Controllers\Dashboard\ProductController;
use App\Http\Controllers\Dashboard\ProfileController;
use App\Http\Controllers\Dashboard\QsController;
use App\Http\Controllers\Dashboard\ServerController;
use App\Http\Controllers\Dashboard\SslController;
use App\Http\Controllers\Dashboard\SupportController;
use App\Http\Controllers\Dashboard\VpsController;
use App\Http\Controllers\WebhookController;
use Illuminate\Support\Facades\Route;

// -------------------------------------------------------------------------
// Public JSON API — consumed by kloud101.com (Next.js marketing site).
// No CSRF; no session; no auth required for public document reads.
// -------------------------------------------------------------------------
Route::prefix('api')->withoutMiddleware([\Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class])->group(function () {
    Route::get('/legal',        [LegalDocumentController::class, 'index']);
    Route::get('/legal/{slug}', [LegalDocumentController::class, 'show']);
});

// Root → redirect to dashboard or login
Route::get('/', fn () => redirect()->route('dashboard'));

// -------------------------------------------------------------------------
// Payment gateway webhooks — public, no session/CSRF (see bootstrap/app.php).
// Authenticity is verified per-gateway via its own signature scheme.
// -------------------------------------------------------------------------
Route::post('/webhooks/paystack', [WebhookController::class, 'paystack'])->name('webhooks.paystack');
Route::post('/webhooks/flutterwave', [WebhookController::class, 'flutterwave'])->name('webhooks.flutterwave');
Route::post('/webhooks/nowpayments', [WebhookController::class, 'nowpayments'])->name('webhooks.nowpayments');

// -------------------------------------------------------------------------
// Auth (guest only)
// -------------------------------------------------------------------------
Route::middleware('guest')->group(function () {
    Route::get('/login',     [LoginController::class,    'show'])->name('login');
    Route::post('/login',    [LoginController::class,    'login'])->middleware('throttle:6,1');
    Route::get('/register',  [RegisterController::class, 'show'])->name('register');
    Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:6,1');

    // Forgot / reset password
    Route::get('/password/forgot',      [PasswordResetController::class, 'showForgot'])->name('password.request');
    Route::post('/password/forgot',     [PasswordResetController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:6,1');
    Route::get('/password/reset/{token}', [PasswordResetController::class, 'showReset'])->name('password.reset');
    Route::post('/password/reset',      [PasswordResetController::class, 'resetPassword'])->name('password.update')->middleware('throttle:6,1');

    // Social login (Google / Facebook)
    Route::get('/auth/{provider}/redirect', [SocialLoginController::class, 'redirect'])->name('social.redirect');
    Route::get('/auth/{provider}/callback', [SocialLoginController::class, 'callback'])->name('social.callback');
    Route::get('/auth/social/complete',     [SocialLoginController::class, 'showComplete'])->name('social.complete');
    Route::post('/auth/social/complete',    [SocialLoginController::class, 'storeComplete'])->name('social.complete.store')->middleware('throttle:6,1');
});

Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');

// Reachable whether or not the client has an active session on this device/browser
// (same as a password reset link) — authorization comes entirely from the signature.
Route::get('/verify-email/{id}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

// -------------------------------------------------------------------------
// Dashboard (protected by ClientAuth middleware)
// -------------------------------------------------------------------------
Route::middleware('client.auth')->group(function () {

    Route::get('/dashboard', [HomeController::class, 'index'])->name('dashboard');

    Route::post('/verify-email/resend', [EmailVerificationController::class, 'resend'])
        ->name('verification.resend')
        ->middleware('throttle:6,1');

    // Servers
    Route::get('/servers',              [ServerController::class, 'index'])->name('servers.index');
    Route::get('/servers/order',        [ProductController::class, 'index'])->name('servers.order');
    Route::get('/servers/{id}',         [ServerController::class, 'show'])->name('servers.show');
    Route::post('/servers/{id}/action', [ServerController::class, 'action'])->name('servers.action');

    // VPS (InterServer-backed)
    Route::get('/vps',                    [VpsController::class, 'index'])->name('vps.index');
    Route::post('/vps/quote',             [VpsController::class, 'quote'])->name('vps.quote');
    Route::get('/vps/order/{category}',   [VpsController::class, 'catalog'])->name('vps.catalog');
    Route::post('/vps/order/{category}',  [VpsController::class, 'store'])->name('vps.store');
    Route::get('/vps/{order}',            [VpsController::class, 'show'])->name('vps.show');
    Route::post('/vps/{order}/action',    [VpsController::class, 'action'])->name('vps.action');

    // Quick Servers (InterServer-backed)
    Route::get('/qs',                 [QsController::class, 'index'])->name('qs.index');
    Route::post('/qs/quote',          [QsController::class, 'quote'])->name('qs.quote');
    Route::get('/qs/order',           [QsController::class, 'catalog'])->name('qs.catalog');
    Route::post('/qs/order',          [QsController::class, 'store'])->name('qs.store');
    Route::get('/qs/{order}',         [QsController::class, 'show'])->name('qs.show');
    Route::post('/qs/{order}/action', [QsController::class, 'action'])->name('qs.action');

    // SSL Certificates (InterServer-backed)
    Route::get('/ssl',                 [SslController::class, 'index'])->name('ssl.index');
    Route::post('/ssl/quote',          [SslController::class, 'quote'])->name('ssl.quote');
    Route::get('/ssl/order',           [SslController::class, 'catalog'])->name('ssl.catalog');
    Route::post('/ssl/order',          [SslController::class, 'store'])->name('ssl.store');
    Route::get('/ssl/{order}',         [SslController::class, 'show'])->name('ssl.show');
    Route::post('/ssl/{order}/action', [SslController::class, 'action'])->name('ssl.action');

    // Domain Registration (InterServer-backed)
    Route::get('/domains',                 [DomainsController::class, 'index'])->name('domains.index');
    Route::get('/domains/search',          [DomainsController::class, 'search'])->name('domains.search');
    Route::post('/domains/lookup',         [DomainsController::class, 'lookup'])->name('domains.lookup');
    Route::get('/domains/order',           [DomainsController::class, 'catalog'])->name('domains.catalog');
    Route::post('/domains/quote',          [DomainsController::class, 'quote'])->name('domains.quote');
    Route::post('/domains/order/register', [DomainsController::class, 'store'])->name('domains.store');
    Route::post('/domains/order/transfer', [DomainsController::class, 'transferStore'])->name('domains.transfer.store');
    Route::get('/domains/{order}',         [DomainsController::class, 'show'])->name('domains.show');
    Route::post('/domains/{order}/action', [DomainsController::class, 'action'])->name('domains.action');

    Route::get('/domains/{order}/contact',  [DomainsController::class, 'contactShow'])->name('domains.contact.show');
    Route::post('/domains/{order}/contact', [DomainsController::class, 'contactUpdate'])->name('domains.contact.update');

    Route::get('/domains/{order}/dnssec',    [DomainsController::class, 'dnssecIndex'])->name('domains.dnssec.index');
    Route::post('/domains/{order}/dnssec',   [DomainsController::class, 'dnssecStore'])->name('domains.dnssec.store');
    Route::delete('/domains/{order}/dnssec', [DomainsController::class, 'dnssecDestroy'])->name('domains.dnssec.destroy');

    Route::get('/domains/{order}/nameservers',    [DomainsController::class, 'nameserversIndex'])->name('domains.nameservers.index');
    Route::put('/domains/{order}/nameservers',    [DomainsController::class, 'nameserversUpdate'])->name('domains.nameservers.preview');
    Route::post('/domains/{order}/nameservers',   [DomainsController::class, 'nameserversStore'])->name('domains.nameservers.store');
    Route::delete('/domains/{order}/nameservers', [DomainsController::class, 'nameserversDestroy'])->name('domains.nameservers.destroy');

    Route::get('/domains/{order}/whois',  [DomainsController::class, 'whoisShow'])->name('domains.whois.show');
    Route::post('/domains/{order}/whois', [DomainsController::class, 'whoisUpdate'])->name('domains.whois.update');

    Route::get('/domains/{order}/renew',  [DomainsController::class, 'renewShow'])->name('domains.renew.show');
    Route::post('/domains/{order}/renew', [DomainsController::class, 'renewStore'])->name('domains.renew.store');

    Route::get('/domains/{order}/transfer',  [DomainsController::class, 'transferShow'])->name('domains.transfer.show');
    Route::post('/domains/{order}/transfer', [DomainsController::class, 'transferUpdate'])->name('domains.transfer.update');

    // Categories not yet wired to a live provider
    Route::get('/coming-soon/{title}', function (string $title) {
        return view('dashboard.coming-soon', ['title' => str_replace('-', ' ', $title)]);
    })->name('coming-soon');

    // Billing
    Route::get('/billing',      [BillingController::class, 'index'])->name('billing.index');
    Route::get('/billing/{id}', [BillingController::class, 'show'])->name('billing.show');

    // Embedded payments — client-triggered verification after a gateway's own popup/redirect
    Route::post('/billing/{id}/pay/paystack/verify',    [PaymentController::class, 'verifyPaystack'])->name('payment.paystack.verify');
    Route::post('/billing/{id}/pay/flutterwave/verify', [PaymentController::class, 'verifyFlutterwave'])->name('payment.flutterwave.verify');
    Route::post('/billing/{id}/pay/nowpayments/init',   [PaymentController::class, 'initNowPayments'])->name('payment.nowpayments.init');

    // Support
    Route::get('/support',             [SupportController::class, 'index'])->name('support.index');
    Route::get('/support/create',      [SupportController::class, 'create'])->name('support.create');
    Route::post('/support',            [SupportController::class, 'store'])->name('support.store');
    Route::get('/support/{id}',        [SupportController::class, 'show'])->name('support.show');
    Route::post('/support/{id}/reply', [SupportController::class, 'reply'])->name('support.reply');
    Route::post('/support/{id}/close', [SupportController::class, 'close'])->name('support.close');

    // Profile
    Route::get('/profile',           [ProfileController::class, 'index'])->name('profile.index');
    Route::post('/profile',          [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password');

    // Currency switcher
    Route::post('/currency', [CurrencyController::class, 'store'])->name('currency.store');
});

// -------------------------------------------------------------------------
// Admin (separate auth from the client dashboard — shared password, no client session needed)
// -------------------------------------------------------------------------
Route::prefix('admin')->group(function () {
    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('admin.login');
    Route::post('/login', [AdminAuthController::class, 'login'])->middleware('throttle:6,1');
    Route::post('/logout', [AdminAuthController::class, 'logout'])->name('admin.logout');

    // Admin password reset — separate from the client-facing one in PasswordResetController.
    // Only reachable if the email matches a real admins row; otherwise changing the admin
    // password requires direct DB access (see App\Models\Admin::checkPassword).
    Route::get('/forgot-password',  [AdminAuthController::class, 'showForgot'])->name('admin.password.request');
    Route::post('/forgot-password', [AdminAuthController::class, 'sendResetLink'])->name('admin.password.email')->middleware('throttle:6,1');
    Route::get('/reset-password/{token}', [AdminAuthController::class, 'showReset'])->name('admin.password.reset');
    Route::post('/reset-password', [AdminAuthController::class, 'resetPassword'])->name('admin.password.update')->middleware('throttle:6,1');

    // 2FA login challenge — reached mid-login, before isAdmin is set (see AdminAuthController::login).
    Route::get('/two-factor-challenge',  [AdminAuthController::class, 'showTwoFactorChallenge'])->name('admin.two-factor.challenge');
    Route::post('/two-factor-challenge', [AdminAuthController::class, 'verifyTwoFactorChallenge'])->name('admin.two-factor.verify')->middleware('throttle:6,1');

    Route::middleware('admin.auth')->group(function () {
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

        // Self-service 2FA management — any admin can secure their own account.
        Route::get('/security',  [AdminTwoFactorController::class, 'show'])->name('admin.security');
        Route::get('/security/two-factor/setup',   [AdminTwoFactorController::class, 'setup'])->name('admin.security.two-factor.setup');
        Route::post('/security/two-factor/confirm', [AdminTwoFactorController::class, 'confirm'])->name('admin.security.two-factor.confirm');
        Route::post('/security/two-factor/disable', [AdminTwoFactorController::class, 'disable'])->name('admin.security.two-factor.disable');
        Route::post('/security/two-factor/recovery-codes', [AdminTwoFactorController::class, 'regenerateRecoveryCodes'])->name('admin.security.two-factor.recovery-codes');

        Route::get('/orders', [AdminOrdersController::class, 'index'])->name('admin.orders.index');

        // Services management (live InterServer actions)
        Route::prefix('services')->group(function () {
            Route::get('/', [AdminServicesController::class, 'index'])->name('admin.services.index');

            Route::prefix('vps')->group(function () {
                Route::get('/{order}',             [AdminServicesController::class, 'showVps'])->name('admin.services.vps.show');
                Route::post('/{order}/start',      [AdminServicesController::class, 'vpsStart'])->name('admin.services.vps.start');
                Route::post('/{order}/suspend',    [AdminServicesController::class, 'vpsSuspend'])->name('admin.services.vps.suspend');
                Route::post('/{order}/unsuspend',  [AdminServicesController::class, 'vpsUnsuspend'])->name('admin.services.vps.unsuspend');
                Route::post('/{order}/restart',    [AdminServicesController::class, 'vpsRestart'])->name('admin.services.vps.restart');
                Route::post('/{order}/password',   [AdminServicesController::class, 'vpsChangePassword'])->name('admin.services.vps.change-password');
                Route::post('/{order}/reinstall',  [AdminServicesController::class, 'vpsReinstallOs'])->name('admin.services.vps.reinstall-os');
                Route::post('/{order}/console',    [AdminServicesController::class, 'vpsConsole'])->name('admin.services.vps.console');
                Route::post('/{order}/cancel',     [AdminServicesController::class, 'vpsCancel'])->name('admin.services.vps.cancel');
            });

            Route::prefix('qs')->group(function () {
                Route::get('/{order}',             [AdminServicesController::class, 'showQs'])->name('admin.services.qs.show');
                Route::post('/{order}/start',      [AdminServicesController::class, 'qsStart'])->name('admin.services.qs.start');
                Route::post('/{order}/suspend',    [AdminServicesController::class, 'qsSuspend'])->name('admin.services.qs.suspend');
                Route::post('/{order}/unsuspend',  [AdminServicesController::class, 'qsUnsuspend'])->name('admin.services.qs.unsuspend');
                Route::post('/{order}/restart',    [AdminServicesController::class, 'qsRestart'])->name('admin.services.qs.restart');
                Route::post('/{order}/password',   [AdminServicesController::class, 'qsChangePassword'])->name('admin.services.qs.change-password');
                Route::post('/{order}/reinstall',  [AdminServicesController::class, 'qsReinstallOs'])->name('admin.services.qs.reinstall-os');
                Route::post('/{order}/console',    [AdminServicesController::class, 'qsConsole'])->name('admin.services.qs.console');
                Route::post('/{order}/cancel',     [AdminServicesController::class, 'qsCancel'])->name('admin.services.qs.cancel');
            });
        });

        // Communications
        Route::get('/communications/newsletter',       [AdminCommunicationsController::class, 'newsletter'])->name('admin.communications.newsletter');
        Route::post('/communications/newsletter/send', [AdminCommunicationsController::class, 'sendNewsletter'])->name('admin.communications.newsletter.send');

        Route::middleware('admin.role:super_admin,finance_manager')->group(function () {
            Route::get('/pricing',  [AdminPricingController::class, 'index'])->name('admin.pricing');
            Route::post('/pricing', [AdminPricingController::class, 'update'])->name('admin.pricing.update');

            Route::prefix('products')->where(['type' => 'vps|qs|ssl|domain'])->group(function () {
                Route::get('/{type}',              [AdminProductsController::class, 'index'])->name('admin.products.index');
                Route::get('/{type}/{key}/edit',   [AdminProductsController::class, 'edit'])->name('admin.products.edit');
                Route::post('/{type}/{key}/details', [AdminProductsController::class, 'updateDetails'])->name('admin.products.details');
                Route::post('/{type}/{key}/pricing', [AdminProductsController::class, 'updatePricing'])->name('admin.products.pricing');
            });

            Route::get('/billing-settings',  [AdminBillingSettingsController::class, 'index'])->name('admin.billing-settings');
            Route::post('/billing-settings', [AdminBillingSettingsController::class, 'update'])->name('admin.billing-settings.update');

            Route::prefix('invoices')->group(function () {
                Route::get('/',                    [AdminInvoicesController::class, 'index'])->name('admin.invoices.index');
                Route::get('/{id}',                [AdminInvoicesController::class, 'show'])->name('admin.invoices.show');
                Route::post('/{id}/mark-paid',     [AdminInvoicesController::class, 'markPaid'])->name('admin.invoices.mark-paid');
                Route::post('/{id}/cancel',        [AdminInvoicesController::class, 'cancel'])->name('admin.invoices.cancel');
            });

            Route::get('/transactions', [AdminTransactionsController::class, 'index'])->name('admin.transactions.index');
        });

        Route::middleware('admin.role:super_admin,support_agent')->prefix('tickets')->group(function () {
            Route::get('/',             [AdminTicketController::class, 'index'])->name('admin.tickets.index');
            Route::get('/{id}',         [AdminTicketController::class, 'show'])->name('admin.tickets.show');
            Route::post('/{id}/reply',  [AdminTicketController::class, 'reply'])->name('admin.tickets.reply');
            Route::post('/{id}/close',  [AdminTicketController::class, 'close'])->name('admin.tickets.close');
        });

        Route::middleware('admin.role:super_admin,support_agent')->prefix('clients')->group(function () {
            Route::get('/',                       [AdminClientController::class, 'index'])->name('admin.clients.index');
            Route::get('/{client}',               [AdminClientController::class, 'show'])->name('admin.clients.show');
            Route::put('/{client}',                [AdminClientController::class, 'update'])->name('admin.clients.update');
            Route::post('/{client}/suspend',       [AdminClientController::class, 'suspend'])->name('admin.clients.suspend');
            Route::post('/{client}/unsuspend',     [AdminClientController::class, 'unsuspend'])->name('admin.clients.unsuspend');
            Route::post('/{client}/reset-password', [AdminClientController::class, 'resetPassword'])->name('admin.clients.reset-password');
            Route::post('/{client}/resend-verification', [AdminClientController::class, 'resendVerification'])->name('admin.clients.resend-verification');
            Route::post('/{client}/add-credit',          [AdminClientController::class, 'addCredit'])->name('admin.clients.add-credit');
            Route::post('/{client}/send-email',          [AdminClientController::class, 'sendEmail'])->name('admin.clients.send-email');
        });

        // Admin account + role management — restricted to Super Admin so no other
        // role can grant itself (or anyone) elevated access.
        Route::middleware('admin.role:super_admin')->prefix('users')->group(function () {
            Route::get('/',           [AdminUserController::class, 'index'])->name('admin.users.index');
            Route::get('/create',     [AdminUserController::class, 'create'])->name('admin.users.create');
            Route::post('/',          [AdminUserController::class, 'store'])->name('admin.users.store');
            Route::get('/{admin}/edit', [AdminUserController::class, 'edit'])->name('admin.users.edit');
            Route::put('/{admin}',    [AdminUserController::class, 'update'])->name('admin.users.update');
            Route::delete('/{admin}', [AdminUserController::class, 'destroy'])->name('admin.users.destroy');
        });
    });
});
