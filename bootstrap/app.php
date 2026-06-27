<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'client.auth' => \App\Http\Middleware\ClientAuth::class,
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.role' => \App\Http\Middleware\AdminRole::class,
        ]);

        // Payment gateway webhooks (Paystack/Flutterwave/NOWPayments) POST here without
        // a Laravel session or CSRF token — authenticity is verified via each gateway's
        // own signature scheme instead (see WebhookController).
        $middleware->validateCsrfTokens(except: [
            'webhooks/*',
            'api/*',
        ]);

        // Apply CORS headers to all /api/* responses so the Next.js marketing
        // site at kloud101.com can call these endpoints cross-origin.
        $middleware->prependToGroup('web', \Illuminate\Http\Middleware\HandleCors::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
