<?php

use App\Models\Animal;
use App\Models\Finca;
use App\Models\User;
use App\Services\ProduccionService;
use Illuminate\Support\Facades\Artisan;
use Tests\Support\FincasApplication;

putenv('BOVISOFT_OFFLINE_DEMO=1');
if (PHP_SAPI !== 'cli') {
    return require __DIR__.'/fincas.php';
}

require dirname(__DIR__, 2).'/vendor/autoload.php';
$prefix = getenv('BOVISOFT_WORKSPACE_DEMO') === '1' ? 'bovisoft-workspace-browser-' : 'bovisoft-offline-browser-';
$database = sys_get_temp_dir().'/'.$prefix.substr(sha1(dirname(__DIR__, 2)), 0, 12).'.sqlite';
if (($argv[1] ?? '') !== 'prepare') {
    fwrite(STDERR, "Uso: php tests/manual/offline.php prepare\n");
    exit(1);
}
$exists = file_exists($database);
if (! $exists && ! getenv('BOVISOFT_BROWSER_TEST_PASSWORD')) {
    throw new RuntimeException('Define BOVISOFT_BROWSER_TEST_PASSWORD con una clave local exclusiva para datos ficticios.');
}
if (! $exists) {
    fclose(fopen($database, 'x'));
}
FincasApplication::create($database, browser: true);
if (Artisan::call('migrate', ['--force' => true]) !== 0) {
    throw new RuntimeException('No se pudo preparar la SQLite ficticia offline.');
}
if (! $exists) {
    $a = User::factory()->create(['name' => 'Cuenta ficticia A', 'email' => 'offline-a@example.test', 'password' => getenv('BOVISOFT_BROWSER_TEST_PASSWORD')]);
    $b = User::factory()->create(['name' => 'Cuenta ficticia B', 'email' => 'offline-b@example.test', 'password' => getenv('BOVISOFT_BROWSER_TEST_PASSWORD')]);
    $farms = [];
    foreach ([[$a, 'A1', 'Finca El Roble'], [$a, 'A2', 'Finca La Esperanza'], [$b, 'B1', 'Finca Los Cedros']] as [$user, $tag, $name]) {
        $farms[$tag] = Finca::create(['user_id' => $user->id, 'nombre' => $name, 'codigo' => 'OFF-'.$tag,
            'area' => 10, 'activa' => true, 'latitud' => 4.5, 'longitud' => -74.1]);
    }
    $animals = [];
    foreach ([[$a, 'A1', 'Lucera'], [$a, 'A1', 'Estrella histórica'], [$a, 'A2', 'Canela'], [$b, 'B1', 'Brisa']] as $index => [$user, $tag, $name]) {
        $animals[$index] = Animal::create(['user_id' => $user->id, 'finca_id' => $farms[$tag]->id,
            'codigo' => 'OFF-ANI-'.$index, 'nombre' => $name, 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
    }
    $service = app(ProduccionService::class);
    for ($index = 0; $index < 125; $index++) {
        $service->create($a, ['animal_id' => $animals[$index < 2 ? 1 : 0]->id, 'finca_id' => $farms['A1']->id,
            'fecha' => now()->subDays($index % 60)->format('Y-m-d'), 'litros' => '12.35', 'sesion' => 'manana',
            'calidad' => 'buena', 'observaciones' => 'Registro ficticio de El Roble.']);
    }
    foreach ([[$a, 'A2', 2, 4], [$b, 'B1', 3, 3]] as [$user, $tag, $animalIndex, $count]) {
        for ($index = 0; $index < $count; $index++) {
            $service->create($user, ['animal_id' => $animals[$animalIndex]->id, 'finca_id' => $farms[$tag]->id,
                'fecha' => now()->subDays($index)->format('Y-m-d'), 'litros' => '18.50', 'sesion' => 'tarde', 'calidad' => 'excelente']);
        }
    }
    $service->create($a, ['animal_id' => $animals[0]->id, 'finca_id' => $farms['A1']->id,
        'fecha' => now()->subDays(100)->format('Y-m-d'), 'litros' => '8.00', 'observaciones' => 'Fuera de los 90 días.']);
    $animals[1]->update(['estado' => 'vendido']);
}
echo "SQLite ficticia preparada: $database\n";
echo "Cuenta A: offline-a@example.test | Cuenta B: offline-b@example.test\n";
echo "La contraseña de las cuentas es la indicada al crear la base mediante BOVISOFT_BROWSER_TEST_PASSWORD.\n";
echo "A: El Roble (125 registros recientes), La Esperanza (4). B: Los Cedros (3).\n";
