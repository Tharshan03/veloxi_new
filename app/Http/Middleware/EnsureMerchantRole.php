<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureMerchantRole
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('merchant')) {
            abort(403, 'Accès commerçant requis.');
        }

        return $next($request);
    }
}
