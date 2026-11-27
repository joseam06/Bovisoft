<?php

use App\Models\User;
use App\Services\ProductionSyncService;
use Tests\Support\FincasApplication;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$path = $argv[1];
$job = json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
FincasApplication::create();
$user = User::findOrFail($job['user_id']);
touch($path.'.ready');
$deadline = microtime(true) + 20;
while (! file_exists($job['barrier'])) {
    if (microtime(true) > $deadline) {
        throw new RuntimeException('No llegó la señal de inicio concurrente.');
    }
    usleep(10000);
}
$result = ($job['workspace'] ?? false)
    ? app(\App\Services\WorkspaceSync::class)->apply($user, $job['device_uuid'], $job['operation'])
    : app(ProductionSyncService::class)->applyOperation($user, $job['device_uuid'], $job['operation']);
echo json_encode($result, JSON_THROW_ON_ERROR);
