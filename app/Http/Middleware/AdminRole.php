<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    /**
     * Runs after AdminAuth, so session('adminRole') is always set by the time this
     * fires. Usage: ->middleware('admin.role:super_admin,finance_manager')
     */
    public function handle(Request $request, Closure $next, string ...$allowedRoles): Response
    {
        if (! in_array(session('adminRole'), $allowedRoles, true)) {
            abort(403, 'You do not have permission to access this section.');
        }

        return $next($request);
    }
}
