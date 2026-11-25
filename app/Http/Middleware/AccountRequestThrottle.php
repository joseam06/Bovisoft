<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\ThrottleRequests;

class AccountRequestThrottle
{
    public function handle(Request $request, Closure $next)
    {
        if ($request->isMethod('PUT') && $request->is('user/password')) {
            return app(ThrottleRequests::class)->handle($request, $next, 'account-security');
        }
        if ($request->isMethod('POST') && $request->is('register', 'forgot-password', 'reset-password')) {
            return app(ThrottleRequests::class)->handle($request, $next, 'account-requests');
        }

        return $next($request);
    }
}
