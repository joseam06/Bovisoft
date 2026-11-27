<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Produccion;
use App\Models\User;
use App\Services\ProductionJournal;
use App\Services\ProductionSyncService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FincasTestCase;

class ProductionSyncConcurrencyTest extends FincasTestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        // Schema-changing tests must not reuse RefreshDatabase's cached SQLite PDO.
        RefreshDatabaseState::$inMemoryConnections = [];
        RefreshDatabaseState::$migrated = false;
        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            RefreshDatabaseState::$inMemoryConnections = [];
            RefreshDatabaseState::$migrated = false;
        }
    }

    public function test_concurrent_replays_have_one_effect_in_postgresql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La prueba multiproceso se ejecuta en PostgreSQL.');
        }
        [$user, $data] = $this->fixture();
        $operation = ['operation_uuid' => (string) Str::uuid(), 'record_uuid' => (string) Str::uuid(), 'type' => 'create', 'data' => $data];
        $results = $this->concurrently($user, [$operation, $operation]);
        $this->assertSame([201, 201], array_column($results, 'status'));
        $this->assertSame($results[0], $results[1]);
        $this->assertDatabaseCount('producciones', 1);
        $this->assertDatabaseCount('production_sync_changes', 1);
        $this->assertDatabaseCount('production_sync_receipts', 1);
    }

    public function test_concurrent_edits_of_the_same_base_version_cannot_overwrite_each_other_in_postgresql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La prueba multiproceso se ejecuta en PostgreSQL.');
        }
        [$user, $data] = $this->fixture();
        $recordUuid = (string) Str::uuid();
        app(ProductionSyncService::class)->applyOperation($user, (string) Str::uuid(), [
            'operation_uuid' => (string) Str::uuid(), 'record_uuid' => $recordUuid, 'type' => 'create', 'data' => $data,
        ]);
        $operations = [];
        foreach (['20.00', '30.00'] as $litros) {
            $operations[] = ['operation_uuid' => (string) Str::uuid(), 'record_uuid' => $recordUuid,
                'type' => 'update', 'base_version' => 1, 'data' => array_replace($data, ['litros' => $litros])];
        }
        $results = $this->concurrently($user, $operations);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([200, 409], $statuses);
        $winner = collect($results)->firstWhere('status', 200)['body']['record'];
        $loser = collect($results)->firstWhere('status', 409)['body'];
        $this->assertSame('version_conflict', $loser['error']);
        $this->assertSame($winner, $loser['current']);
        $this->assertSame($winner['litros'], Produccion::sole()->litros);
        $this->assertSame(2, Produccion::sole()->version);
        $this->assertDatabaseCount('production_sync_changes', 2);
        $this->assertDatabaseCount('production_sync_receipts', 3);
    }

    public function test_migration_backfills_existing_numeric_records_and_preserves_codes(): void
    {
        [$user, $data] = $this->fixture();
        $migration = require database_path('migrations/2026_11_18_000001_create_production_sync_tables.php');
        $migration->down();
        $id = DB::table('producciones')->insertGetId($data + ['user_id' => $user->id, 'codigo' => 'PROD-009000', 'created_at' => now(), 'updated_at' => now()]);
        $migration->up();
        $record = Produccion::findOrFail($id);
        $this->assertTrue(Str::isUuid($record->client_uuid));
        $this->assertSame(1, $record->version);
        $this->assertSame('PROD-009000', $record->codigo);
        $this->assertSame($id, $record->id);
        $code = app(ProductionJournal::class)->transaction($user->id, fn () => app(ProductionJournal::class)->nextCode());
        $this->assertSame('PROD-009001', $code);
    }

    public function test_concurrent_workspace_replays_have_one_effect_in_postgresql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La prueba multiproceso se ejecuta en PostgreSQL.');
        }
        [$user] = $this->fixture();
        $op = ['module' => 'fincas', 'type' => 'create', 'operation_uuid' => (string) Str::uuid(), 'record_uuid' => (string) Str::uuid(),
            'data' => ['nombre' => 'Finca concurrente', 'area' => 10, 'latitud' => 4.5, 'longitud' => -74.1]];
        $results = $this->concurrently($user, [$op, $op], workspace: true);
        $this->assertSame([201, 201], array_column($results, 'status'));
        $this->assertSame($results[0], $results[1]);
        $this->assertSame(1, Finca::where('client_uuid', $op['record_uuid'])->count());
        $this->assertSame(1, DB::table('workspace_sync_changes')->where('record_uuid', $op['record_uuid'])->count());
        $this->assertDatabaseCount('workspace_sync_receipts', 1);
    }

    public function test_concurrent_workspace_edits_return_one_conflict_in_postgresql(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('La prueba multiproceso se ejecuta en PostgreSQL.');
        }
        [$user] = $this->fixture();
        $farm = Finca::where('user_id', $user->id)->sole();
        $operations = [];
        foreach (['Cambio A', 'Cambio B'] as $name) {
            $operations[] = ['module' => 'fincas', 'type' => 'update', 'operation_uuid' => (string) Str::uuid(), 'record_uuid' => $farm->client_uuid,
                'base_version' => 1, 'data' => ['nombre' => $name, 'latitud' => 4.5, 'longitud' => -74.1]];
        }
        $results = $this->concurrently($user, $operations, workspace: true);
        $statuses = array_column($results, 'status');
        sort($statuses);
        $this->assertSame([200, 409], $statuses);
        $winner = collect($results)->firstWhere('status', 200)['record'];
        $loser = collect($results)->firstWhere('status', 409)['current'];
        $this->assertSame($winner, $loser);
        $this->assertSame(2, $farm->fresh()->version);
        $this->assertSame(2, DB::table('workspace_sync_changes')->where('record_uuid', $farm->client_uuid)->count());
    }

    public function test_workspace_migration_preserves_existing_ids_codes_and_soft_deleted_rows(): void
    {
        [$user] = $this->fixture();
        $migration = require database_path('migrations/2026_11_18_000002_create_workspace_sync_tables.php');
        $migration->down();
        $id = DB::table('fincas')->insertGetId(['user_id' => $user->id, 'nombre' => 'Histórica', 'codigo' => 'FIN-009000', 'area' => 10, 'deleted_at' => now()]);
        $migration->up();
        $farm = Finca::withTrashed()->findOrFail($id);
        $this->assertSame($id, $farm->id);
        $this->assertSame('FIN-009000', $farm->codigo);
        $this->assertTrue($farm->trashed());
        $this->assertTrue(Str::isUuid($farm->client_uuid));
        $code = app(\App\Services\WorkspaceJournal::class)->transaction($user->id, fn () => app(\App\Services\WorkspaceJournal::class)->nextCode('fincas'));
        $this->assertSame('FIN-009001', $code);
        $this->assertSame(0, DB::table('animales')->whereNull('client_uuid')->count());
    }

    private function fixture(): array
    {
        $user = User::factory()->create(['email' => 'concurrente@example.test']);
        $finca = Finca::create(['user_id' => $user->id, 'codigo' => 'CONC-FIN', 'nombre' => 'Finca ficticia', 'activa' => true, 'area' => 10]);
        $animal = Animal::create(['user_id' => $user->id, 'finca_id' => $finca->id, 'codigo' => 'CONC-ANI',
            'nombre' => 'Animal ficticio', 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);

        return [$user, ['animal_id' => $animal->id, 'finca_id' => $finca->id, 'fecha' => now()->format('Y-m-d'),
            'litros' => '12.35', 'sesion' => 'manana', 'calidad' => 'buena', 'observaciones' => null]];
    }

    private function concurrently(User $user, array $operations, bool $workspace = false): array
    {
        $directory = sys_get_temp_dir().'/bovisoft-sync-'.Str::uuid();
        mkdir($directory);
        $barrier = $directory.'/start';
        $processes = [];
        try {
            foreach ($operations as $index => $operation) {
                $path = $directory.'/job-'.$index;
                file_put_contents($path, json_encode(['user_id' => $user->id, 'device_uuid' => (string) Str::uuid(),
                    'operation' => $operation, 'barrier' => $barrier, 'workspace' => $workspace], JSON_THROW_ON_ERROR));
                $processes[] = proc_open([PHP_BINARY, '-d', 'extension=pdo_pgsql', base_path('tests/Support/production-sync-worker.php'), $path],
                    [0 => ['pipe', 'r'], 1 => ['file', $path.'.out', 'w'], 2 => ['file', $path.'.err', 'w']], $pipes, base_path());
                fclose($pipes[0]);
            }
            $deadline = microtime(true) + 20;
            while (! file_exists($directory.'/job-0.ready') || ! file_exists($directory.'/job-1.ready')) {
                if (microtime(true) > $deadline) {
                    $this->fail('Los procesos concurrentes no alcanzaron la barrera.');
                }
                usleep(10000);
            }
            touch($barrier);
            $results = [];
            foreach ($processes as $index => $process) {
                $this->assertSame(0, proc_close($process), file_get_contents($directory.'/job-'.$index.'.err'));
                $results[] = json_decode(file_get_contents($directory.'/job-'.$index.'.out'), true, flags: JSON_THROW_ON_ERROR);
            }

            return $results;
        } finally {
            foreach ($processes as $process) {
                if (is_resource($process)) {
                    proc_terminate($process);
                    proc_close($process);
                }
            }
            foreach (['start', 'job-0', 'job-1', 'job-0.ready', 'job-1.ready', 'job-0.out', 'job-1.out', 'job-0.err', 'job-1.err'] as $file) {
                if (file_exists($directory.'/'.$file)) {
                    unlink($directory.'/'.$file);
                }
            }
            rmdir($directory);
        }
    }
}
