<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AccountSessionVersion
{
    public function handle(Request $request, Closure $next)
    {
        if ($user = $request->user()) {
            $version = (int) $user->credentials_version;
            if ((int) $request->session()->get('account_version_user', $user->id) === $user->id && $request->session()->has('account_version') && (int) $request->session()->get('account_version') !== $version) {
                Auth::guard('web')->logoutCurrentDevice();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                // Authentication may already have resolved Sanctum for this
                // request. Discard that cached user and stop protected actions.
                Auth::forgetGuards();
                if (! $request->is('offline/session')) {
                    return ($request->expectsJson()
                        ? response()->json(['message' => 'La sesión venció. Inicia sesión nuevamente; tus pendientes se conservan.'], 401)
                        : redirect()->guest(route('login')))->header('Cache-Control', 'no-store, private');
                }
            } else {
                $request->session()->put(['account_version' => $version, 'account_version_user' => $user->id]);
            }
        }
        $response = $next($request);
        if ($user = Auth::guard('web')->user()) {
            $request->session()->put(['account_version' => (int) $user->credentials_version, 'account_version_user' => $user->id]);
        }

        return $response;
    }
}
