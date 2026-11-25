<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class OfflineBrowserIdentity
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        if($request->is('login','logout','register','forgot-password','reset-password*','two-factor-challenge','user/confirm-password','auth/*','offline/session','email/*','perfil','perfil/password','perfil/seguridad/*','user/profile*','user/password*')){
            $response->headers->set('Cache-Control','no-store, private');
            $response->headers->set('Pragma','no-cache');
        }
        if ($request->hasSession()) {
            $account = $request->user()?->getAuthIdentifier();
            if (! $request->session()->has('offline_binding') || $request->session()->get('offline_account') !== $account) {
                $request->session()->put('offline_account', $account);
                $request->session()->put('offline_binding', (string) Str::uuid());
            }
            $response->headers->set('X-Bovisoft-Account', $account === null ? 'guest' : (string) $account);
            $response->headers->set('X-Bovisoft-Binding', $request->session()->get('offline_binding'));
        }

        return $response;
    }
}
