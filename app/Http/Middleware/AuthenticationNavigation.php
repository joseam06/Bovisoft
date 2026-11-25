<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class AuthenticationNavigation
{
    private function page(?string $url, Request $request, bool $intended = false): bool
    {
        if (! $url) {
            return false;
        }
        $parts = parse_url($url);
        if ($parts === false || isset($parts['host']) && (strtolower($parts['host']) !== strtolower($request->getHost()) || ($parts['port'] ?? (($parts['scheme'] ?? 'http') === 'https' ? 443 : 80)) !== $request->getPort())) {
            return false;
        }
        $path = $parts['path'] ?? '/';
        if (! str_starts_with($path, '/') || str_starts_with($path, '//') || preg_match('~^/(api|offline|offline-shell|auth)(/|$)~', $path)) {
            return false;
        }
        if ($intended && preg_match('~^/(login|register|forgot-password|reset-password|two-factor-challenge)(/|$)~', $path)) {
            return false;
        }
        try {
            $route = Route::getRoutes()->match(Request::create($url, 'GET'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e) {
            return false;
        }

        return in_array('web', $route->gatherMiddleware(), true);
    }

    public function handle(Request $request, Closure $next)
    {
        foreach (['url.intended', '_previous.url'] as $key) {
            if ($request->session()->has($key) && ! $this->page($request->session()->get($key), $request, $key === 'url.intended')) {
                $request->session()->forget($key);
            }
        }
        if ($request->headers->has('referer') && ! $this->page($request->headers->get('referer'), $request)) {
            $request->headers->remove('referer');
        }
        // Old workers can still send their script URL as Referer. Failed forms
        // must return to their own page while their sessions are repaired.
        if ($request->isMethod('POST') && $request->is('login', 'register', 'forgot-password') && ! $request->headers->has('referer')) {
            $request->headers->set('referer', $request->url());
        }

        return $next($request);
    }
}
