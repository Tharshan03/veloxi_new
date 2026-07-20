<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthenticateMerchantDashboard
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->user()) {
            return redirect()->guest(route('merchant.dashboard.login'));
        }

        return $next($request);
    }
}
