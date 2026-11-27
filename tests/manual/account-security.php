<?php

// Fictitious browser fixture only. No production bootstrap, external mail or credentials.
use App\Notifications\AccountNotice;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use Tests\Support\FincasApplication;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$database = sys_get_temp_dir().'/bovisoft-workspace-browser-'.substr(sha1(dirname(__DIR__, 2)), 0, 12).'.sqlite';
if (PHP_SAPI !== 'cli-server' || ! file_exists($database)) {
    http_response_code(503);
    exit('Prepara el dataset ficticio workspace.');
}
$public = realpath(dirname(__DIR__, 2).'/public');
$asset = realpath($public.rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/'));
if ($asset && str_starts_with($asset, $public.DIRECTORY_SEPARATOR) && is_file($asset) && preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/i', $asset)) {
    return false;
}
$app = FincasApplication::create($database, browser: true);
Notification::fake();
$request = Request::capture();
$kernel = $app->make(Kernel::class);
$response = $kernel->handle($request);
// Let the browser open the captured test email without persisting its signed
// URL in a file, localStorage or IndexedDB. This header exists only in this router.
foreach (Notification::sent(new \Illuminate\Notifications\AnonymousNotifiable, AccountNotice::class) as $notice) {
    if ($notice->title === 'Confirma tu nuevo correo') {
        $response->headers->set('X-Bovi-Test-Confirmation', $notice->url);
    }
}
$response->headers->set('Cache-Control', 'no-store, private');
$response->send();
$kernel->terminate($request,$response);
