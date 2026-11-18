<?php

// Local browser fixture only. Never use this router for deployment.
use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\FincasApplication;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$database = sys_get_temp_dir().'/bovisoft-fincas-browser-'.substr(sha1(dirname(__DIR__, 2)), 0, 12).'.sqlite';

if (PHP_SAPI === 'cli') {
    if (($argv[1] ?? '') !== 'prepare') {
        fwrite(STDERR, "Uso: php tests/manual/fincas.php prepare\n");
        exit(1);
    }

    if (file_exists($database)) {
        fwrite(STDERR, "La base ficticia ya existe; se conserva sin reiniciarla: $database\n");
        exit(1);
    }

    $handle = fopen($database, 'x');
    fclose($handle);
    $app = FincasApplication::create($database, browser: true);
    if (Artisan::call('migrate', ['--force' => true]) !== 0) {
        fwrite(STDERR, "No se pudo preparar la base ficticia.\n");
        exit(1);
    }

    foreach (['a', 'b'] as $tag) {
        $user = User::factory()->create([
            'name' => 'Cuenta ficticia '.strtoupper($tag),
            'email' => 'fincas-'.$tag.'@example.test',
            'password' => 'DemoFincas2026!',
        ]);
        $finca = Finca::create(['user_id' => $user->id, 'codigo' => 'DEMO-FIN-'.$tag,
            'nombre' => 'Finca ficticia '.strtoupper($tag), 'activa' => true,
            'latitud' => 4.5, 'longitud' => -74.1]);
        Potrero::create(['user_id' => $user->id, 'finca_id' => $finca->id,
            'codigo' => 'DEMO-POT-'.$tag, 'nombre' => 'Potrero ficticio '.strtoupper($tag), 'estado' => 'activo']);
        Animal::create(['user_id' => $user->id, 'finca_id' => $finca->id,
            'codigo' => 'DEMO-ANI-'.$tag, 'nombre' => 'Animal ficticio '.strtoupper($tag),
            'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
        echo $user->email.' | finca '.$finca->id."\n";
    }

    echo "SQLite de pruebas: $database\n";
    echo "Clave de ambas cuentas ficticias: DemoFincas2026!\n";
    exit;
}

if (PHP_SAPI !== 'cli-server' || ! file_exists($database)) {
    http_response_code(503);
    exit('Prepara primero la base ficticia con el comando documentado.');
}

// Let the PHP development server serve only static files from public/.
// PHP requests must always use this isolated bootstrap, including /index.php.
$path = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/');
$public = realpath(dirname(__DIR__, 2).'/public');
$asset = realpath($public.$path);
if ($asset && str_starts_with($asset, $public.DIRECTORY_SEPARATOR)
    && is_file($asset) && preg_match('/\.(css|js|png|jpg|jpeg|gif|svg|ico|woff2?|ttf)$/i', $asset)) {
    return false;
}

$app = FincasApplication::create($database, browser: true);
$app->handleRequest(Request::capture());
