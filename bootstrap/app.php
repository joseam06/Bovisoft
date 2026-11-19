<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [\App\Http\Middleware\OfflineBrowserIdentity::class]);
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\App\Exceptions\WorkspaceVersionConflict $error, \Illuminate\Http\Request $request) {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'version_conflict', 'message' => $error->getMessage(),
                    'current' => \App\Services\WorkspaceJournal::serialize($error->current)], 409);
            }

            return back()->withErrors(['base_version' => $error->getMessage()])->withInput();
        });
        //
    })->create();
