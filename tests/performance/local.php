<?php

// Explicit disposable local databases only; no habitual .env/config bootstrap.
require dirname(__DIR__, 2).'/vendor/autoload.php';
use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\FincasApplication;
use Tests\Support\PerformanceDataset;

$registry = sys_get_temp_dir().'/bovisoft-performance-runtime.json';
$action = $argv[1] ?? '';
if (PHP_SAPI === 'cli' && $action === 'prepare') {
    if (file_exists($registry)) {
        throw new RuntimeException('Conserva la base activa; termina primero con drop.');
    }
    $engine = $argv[2] ?? 'mysql';
    if (! in_array($engine, ['mysql', 'pgsql'])) {
        throw new RuntimeException('Motor no permitido');
    }
    $name = 'bovisoft_test_piloto_'.bin2hex(random_bytes(8));
    $pdo = new PDO($engine.':host=127.0.0.1;port='.($engine === 'mysql' ? 3306 : 55432).($engine === 'pgsql' ? ';dbname=postgres' : ''), $engine === 'mysql' ? 'root' : 'postgres', '');
    $pdo->exec('CREATE DATABASE '.$name);
    file_put_contents($registry, json_encode(['engine' => $engine, 'database' => $name]));
}
if (! file_exists($registry)) {
    throw new RuntimeException('Prepara primero el entorno desechable');
}
$settings = json_decode(file_get_contents($registry), true);
$engine = $settings['engine'];
$name = $settings['database'];
if (! preg_match('/^bovisoft_test_piloto_[a-f0-9]{16}$/', $name)) {
    throw new RuntimeException('Nombre inválido');
}
if (PHP_SAPI === 'cli' && $action === 'drop') {
    $pdo = new PDO($engine.':host=127.0.0.1;port='.($engine === 'mysql' ? 3306 : 55432).($engine === 'pgsql' ? ';dbname=postgres' : ''), $engine === 'mysql' ? 'root' : 'postgres', '');
    $pdo->exec('DROP DATABASE '.$name);
    unlink($registry);
    echo "Base desechable eliminada.\n";
    exit;
}
if (PHP_SAPI === 'cli-server') {
    $path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
    $public = realpath(dirname(__DIR__, 2).'/public');
    $asset = realpath($public.$path);
    if ($asset && str_starts_with($asset, $public.DIRECTORY_SEPARATOR) && is_file($asset) && preg_match('/\.(js|css|png|jpg|ico|svg|woff2?|ttf)$/i', $asset)) {
        return false;
    }
}
putenv('BOVISOFT_TEST_ENGINE='.$engine);
putenv('BOVISOFT_TEST_DATABASE='.$name);
$app = FincasApplication::create();
config(['app.env' => 'local', 'app.url' => 'http://127.0.0.1:8097', 'session.driver' => 'database', 'session.cookie' => 'bovisoft_perf_only']);
if (PHP_SAPI === 'cli-server') $app->instance('env', 'local');
if (PHP_SAPI === 'cli') {
    if($action==='grow'){
        $user=User::where('email','performance@example.test')->firstOrFail();
        $volume=(int)($argv[2]??2000);if(!in_array($volume,[500,2000]))throw new RuntimeException('Volumen de prueba no permitido');
        (new PerformanceDataset($user))->grow($volume);echo "Dataset ficticio ampliado.\n";exit;
    }
    if ($action === 'catalog') {
        $user=User::where('email','performance@example.test')->firstOrFail();
        $packet=(int)DB::selectOne('SELECT @@max_allowed_packet AS bytes')->bytes;
        DB::flushQueryLog();DB::enableQueryLog();$start=hrtime(true);
        $catalog=(new ReflectionMethod(\App\Services\WorkspaceSync::class,'catalog'))->invoke(app(\App\Services\WorkspaceSync::class),$user);
        $result=['catalog_bytes'=>strlen(json_encode($catalog,JSON_THROW_ON_ERROR)),'max_allowed_packet'=>$packet,'queries'=>count(DB::getQueryLog()),'ms'=>round((hrtime(true)-$start)/1e6,2),'records'=>count($catalog['records'])];
        DB::disableQueryLog();file_put_contents(sys_get_temp_dir().'/bovisoft-performance-catalog.json',json_encode($result,JSON_PRETTY_PRINT));echo json_encode($result).PHP_EOL;exit;
    }
    if ($action === 'snapshot') {
        $user = User::where('email', 'performance@example.test')->firstOrFail();
        DB::flushQueryLog();
        DB::enableQueryLog();
        memory_reset_peak_usage();
        $start = hrtime(true);
        try {$snapshot = app(\App\Services\WorkspaceSync::class)->snapshot($user);}
        catch(\Throwable $failure){echo json_encode(['complete'=>false,'error_class'=>get_class($failure),'error_code'=>$failure->getCode()]).PHP_EOL;exit(2);}
        $result = ['animals' => DB::table('animales')->count(), 'queries' => count(DB::getQueryLog()), 'ms' => round((hrtime(true) - $start) / 1e6, 2), 'peak_mib' => round(memory_get_peak_usage(true) / 1048576, 2), 'first_page_bytes' => strlen(json_encode($snapshot))];
        DB::disableQueryLog();
        file_put_contents(sys_get_temp_dir().'/bovisoft-performance-snapshot.json', json_encode($result, JSON_PRETTY_PRINT));
        echo json_encode($result).PHP_EOL;
        exit;
    }
    Artisan::call('migrate', ['--force' => true]);
    $password = getenv('BOVISOFT_BROWSER_TEST_PASSWORD');
    if (! $password) {
        throw new RuntimeException('Define la contraseña ficticia');
    }
    $user = User::factory()->create(['email' => 'performance@example.test', 'name' => 'Rendimiento ficticio', 'password' => $password]);
    (new PerformanceDataset($user))->grow((int) ($argv[3] ?? 100));
    echo 'Preparado '.$name.'; '.($argv[3] ?? 100)." animales; sin correos reales.\n";
    exit;
}
$app->handleRequest(Illuminate\Http\Request::capture());
