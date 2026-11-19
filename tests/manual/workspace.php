<?php

// Separate, fictitious dataset. Never route a deployment through this file.
putenv('BOVISOFT_WORKSPACE_DEMO=1');
if (PHP_SAPI !== 'cli') {
    return require __DIR__.'/offline.php';
}
require __DIR__.'/offline.php';

$user = \App\Models\User::where('email', 'offline-a@example.test')->firstOrFail();
$finca = \App\Models\Finca::where('user_id', $user->id)->where('codigo', 'OFF-A1')->firstOrFail();
for ($index = 0; $index < 105; $index++) {
    \App\Models\Animal::firstOrCreate(['codigo' => 'OFF-LIST-'.$index], [
        'nombre' => 'Animal ficticio de listado '.$index, 'user_id' => $user->id, 'finca_id' => $finca->id,
        'tipo' => 'ternero', 'sexo' => 'macho', 'estado' => 'activo',
    ]);
}
echo "Dataset de Fincas/Animales con más de cien filas preparado. Puerto sugerido: 8094.\n";
