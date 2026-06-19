<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (session()->has('isAdmin')) {
            return $next($request);
        }

        return redirect()->route('admin.login')->with('error', 'Please log in to continue.');
    }
}
