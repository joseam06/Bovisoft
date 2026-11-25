<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class VerifiedOrLegacyAccount
{
    public function handle(Request $request, Closure $next, $redirectTo = null)
    {
        if (! $request->user() || ! $request->user()->canAccessRecords()) {
            if ($request->expectsJson()) {
                abort(403, 'Verifica tu correo para acceder a tus registros.');
            }

            return redirect()->guest(route($redirectTo ?: 'verification.notice'));
        }

        return $next($request);
    }
}
