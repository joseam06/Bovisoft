<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;

class OfflineReadController extends Controller
{
    public function workspace()
    {
        Vite::useHotFile(public_path('build/.offline-no-hot'));

        return response()->view('offline.workspace', ['offlineShell' => true, 'workspaceShell' => true])->header('Cache-Control', 'no-store, private');
    }

    public function page()
    {
        return redirect('/dashboard');
    }

    public function session(Request $request)
    {
        return response()->json([
            'account_id' => $request->user()?->id,
            'profile' => $request->user() ? ['name' => $request->user()->name, 'email' => $request->user()->email, 'photo_url' => $request->user()->foto_url] : null,
            'csrf_token' => csrf_token(),
            'server_time' => now()->getTimestampMs(),
            'workspace_enabled' => (bool) config('offline.workspace.enabled'),
            'production_enabled' => (bool) config('offline.production.enabled'),
            'fincas' => $request->user() ? Finca::delUsuario($request->user()->id)->orderBy('nombre')->get(['id', 'nombre', 'activa']) : [],
        ])->header('Cache-Control', 'no-store, private');
    }

    public function assets()
    {
        abort_unless(File::exists(public_path('build/manifest.json')), 503, 'Construye los recursos antes de preparar la consulta offline.');
        $manifest = json_decode(File::get(public_path('build/manifest.json')), true, flags: JSON_THROW_ON_ERROR);
        $urls = ['/offline-shell', '/offline-store.js', '/workspace-store.js', '/favicon.ico', '/images/logored.png', '/images/logoblackred.png',
            '/vendor/fontawesome/css/all.min.css', '/vendor/fontawesome/webfonts/fa-solid-900.woff2', '/vendor/inter/inter.css'];
        $visited = [];
        $visit = function ($key) use (&$visit, &$visited, &$urls, $manifest) {
            if (isset($visited[$key])) {
                return;
            }
            $visited[$key] = true;
            abort_unless(isset($manifest[$key]), 503, 'Faltan recursos del modo offline.');
            $entry = $manifest[$key];
            $urls[] = '/build/'.$entry['file'];
            foreach ($entry['css'] ?? [] as $css) {
                $urls[] = '/build/'.$css;
            }
            foreach ($entry['imports'] ?? [] as $dependency) {
                $visit($dependency);
            }
        };
        foreach (['resources/css/app.css', 'resources/js/app.js'] as $key) {
            $visit($key);
        }
        foreach (File::glob(public_path('vendor/inter/*.woff2')) as $font) {
            $urls[] = '/vendor/inter/'.basename($font);
        }
        // Some font providers deliver TTF to older user agents.
        foreach (File::glob(public_path('vendor/inter/*.ttf')) as $font) {
            $urls[] = '/vendor/inter/'.basename($font);
        }
        foreach (array_filter($urls, fn ($url) => $url !== '/offline-shell') as $url) {
            abort_unless(File::exists(public_path(ltrim($url, '/'))), 503, 'Falta un recurso local necesario para la consulta offline.');
        }
        $urls = array_values(array_unique($urls));
        sort($urls);
        $fingerprints = array_map(fn ($url) => $url === '/offline-shell'
            ? $url : $url.':'.hash_file('sha256', public_path(ltrim($url, '/'))), $urls);
        foreach (['fincas', 'fincas/animales'] as $module) {
            foreach (['index', 'create', 'show', 'edit'] as $view) {
                $fingerprints[] = hash_file('sha256', resource_path('views/'.$module.'/'.$view.'.blade.php'));
            }
        }
        $version = hash('sha256', json_encode($fingerprints).hash_file('sha256', public_path('sw.js'))
            .hash_file('sha256', public_path('offline-store.js'))
            .hash_file('sha256', resource_path('views/layouts/dashboard.blade.php'))
            .hash_file('sha256', resource_path('views/offline/workspace.blade.php'))
            .hash_file('sha256', resource_path('views/offline/fragment.blade.php'))
            .hash_file('sha256', resource_path('views/offline/feedback.blade.php')));

        return response()->json(['version' => $version, 'urls' => $urls])->header('Cache-Control', 'no-store');
    }
}
