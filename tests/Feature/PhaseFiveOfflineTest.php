<?php

namespace Tests\Feature;

use App\Models\{Animal, Egreso, Finca, Potrero, Produccion, Salud, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FincasTestCase;

class PhaseFiveOfflineTest extends FincasTestCase
{
    use RefreshDatabase;
    private User $owner;
    private Finca $farm;
    private Animal $animal;

    protected function setUp(): void
    {
        parent::setUp();$this->withoutVite();
        $this->owner = User::factory()->create();
        $this->farm = Finca::create(['user_id' => $this->owner->id, 'codigo' => 'P51-F', 'nombre' => 'Ficticia', 'activa' => true, 'latitud' => 4.5, 'longitud' => -74]);
        $this->animal = Animal::create(['user_id' => $this->owner->id, 'finca_id' => $this->farm->id, 'codigo' => 'P51-A', 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
        $this->actingAs($this->owner);
    }
    private function op(string $module, array $data, string $type = 'create', ?string $record = null, int $version = 1): array
    {
        return ['operation_uuid' => (string) Str::uuid(), 'record_uuid' => $record ?? (string) Str::uuid(), 'module' => $module,
            'type' => $type, 'data' => $data] + ($type === 'create' ? [] : ['base_version' => $version]);
    }
    private function send(array $operation)
    {
        return $this->postJson('/api/offline/v1/workspace/operations', ['account_id' => $this->owner->id, 'device_uuid' => (string) Str::uuid(), 'operation' => $operation])->assertOk();
    }
    private function references(): array {return ['finca_uuid' => $this->farm->client_uuid, 'animal_uuid' => $this->animal->client_uuid];}

    public function test_paddock_dependencies_capacity_moves_occupancy_conflicts_and_deletion(): void
    {
        $farm = $this->op('fincas', ['nombre' => 'Padre nuevo', 'latitud' => 4.5, 'longitud' => -74]);
        $this->send($farm)->assertJsonPath('status', 201);
        $paddock = $this->op('potreros', ['finca_uuid' => $farm['record_uuid'], 'nombre' => 'Potrero nuevo', 'estado' => 'activo', 'capacidad_animales' => 1]);
        $created = $this->send($paddock)->assertJsonPath('status', 201)->json();$this->send($paddock)->assertExactJson($created);
        $animal = $this->op('animales', ['finca_uuid' => $farm['record_uuid'], 'potrero_uuid' => $paddock['record_uuid'], 'tipo' => 'vaca', 'sexo' => 'hembra']);
        $saved = $this->send($animal)->assertJsonPath('status', 201)->json();$this->send($animal)->assertExactJson($saved);
        $potrero = Potrero::sole();$this->assertSame(1, $potrero->animales_actuales);$this->assertSame(2, (int) $potrero->version);
        $another = $this->op('animales', $animal['data']);$this->send($another)->assertJsonPath('status', 422);
        $this->send($this->op('potreros', [], 'delete', $paddock['record_uuid'], 2))->assertJsonPath('status', 422);
        $this->send($this->op('potreros', $paddock['data'], 'update', $paddock['record_uuid'], 1))->assertJsonPath('status', 409);
        $move = $this->op('animales', $animal['data'], 'update', $animal['record_uuid']);$move['data']['potrero_uuid'] = null;
        $this->send($move)->assertJsonPath('status', 200);$this->assertSame(0, $potrero->fresh()->animales_actuales);
        $this->send($this->op('potreros', [], 'delete', $paddock['record_uuid'], 3))->assertJsonPath('record.deleted', true);
        $ordinary = Potrero::create(['codigo' => 'P51-DIRECT', 'nombre' => 'Modelo normal', 'user_id' => $this->owner->id, 'finca_id' => $this->farm->id, 'estado' => 'activo']);
        $this->animal->update(['potrero_id' => $ordinary->id]);
        $this->assertSame(1, $ordinary->fresh()->animales_actuales);
        $this->animal->update(['estado' => 'vendido']); // Same Eloquent path used by an online sale.
        $this->assertSame(0, $ordinary->fresh()->animales_actuales);
    }

    public function test_production_reuses_domain_rules_independent_measurements_and_both_journals(): void
    {
        $data = $this->references() + ['fecha' => today()->format('Y-m-d'), 'litros' => '12.35', 'sesion' => 'manana'];
        $create = $this->op('produccion', $data);$first = $this->send($create)->assertJsonPath('status', 201)->json();
        $this->send($create)->assertExactJson($first);
        $this->send($this->op('produccion', $data))->assertJsonPath('status', 201);
        $this->assertDatabaseCount('producciones', 2);
        $this->assertSame(2, DB::table('production_sync_changes')->count());
        $this->animal->update(['estado' => 'inactivo']);
        $data['litros'] = '15.25';
        $this->send($this->op('produccion', $data, 'update', $create['record_uuid']))->assertJsonPath('record.version', 2);
        $this->send($this->op('produccion', $data, 'update', $create['record_uuid']))->assertJsonPath('status', 409);
        $data['fecha'] = today()->subDay()->format('Y-m-d');
        $this->send($this->op('produccion', $data, 'update', $create['record_uuid'], 2))->assertJsonPath('status', 422);
        $delete = $this->op('produccion', [], 'delete', $create['record_uuid'], 2);
        $deleted = $this->send($delete)->assertJsonPath('record.deleted', true)->json();$this->send($delete)->assertExactJson($deleted);
        $this->assertSame(3, DB::table('workspace_sync_changes')->where('record_uuid', $create['record_uuid'])->count());
    }

    public function test_health_cost_schedule_rollback_replay_and_linked_expense_are_safe(): void
    {
        $data = $this->references() + ['tipo' => 'vacunacion', 'nombre_producto' => 'Vacuna ficticia', 'fecha_aplicacion' => today()->format('Y-m-d'),
            'proxima_aplicacion' => today()->addDays(30)->format('Y-m-d'), 'dias_carencia' => 3, 'costo' => '120.50', 'estado' => 'completado'];
        $create = $this->op('salud', $data);$fail = true;
        DB::listen(function ($q) use (&$fail) {if ($fail && str_contains(strtolower($q->sql), 'insert into') && str_contains($q->sql, 'workspace_sync_receipts')) {$fail=false;throw new \RuntimeException('Interrupción ficticia');}});
        $this->postJson('/api/offline/v1/workspace/operations', ['account_id' => $this->owner->id, 'device_uuid' => (string) Str::uuid(), 'operation' => $create])->assertStatus(500);
        $this->assertDatabaseCount('salud', 0);$this->assertDatabaseMissing('workspace_sync_receipts', ['operation_uuid' => $create['operation_uuid']]);
        $first = $this->send($create)->assertJsonPath('status', 201)->assertJsonPath('record.categoria', 'preventivo')->json();$this->send($create)->assertExactJson($first);
        $record = Salud::sole();$this->assertSame(today()->addDays(3)->format('Y-m-d'), $record->fin_carencia->format('Y-m-d'));
        $this->assertDatabaseCount('egresos', 0); // Existing workflow is explicit, never a second automatic charge.
        Egreso::create(['codigo' => 'P51-E', 'user_id' => $this->owner->id, 'finca_id' => $this->farm->id, 'animal_id' => $this->animal->id,
            'salud_id' => $record->id, 'categoria' => 'salud_animal', 'descripcion' => 'Ficticio', 'fecha' => today(), 'monto' => 120.50]);
        $this->assertSame(2, (int) $record->fresh()->version);
        $this->assertTrue(\App\Services\WorkspaceJournal::serialize($record->fresh())['egreso_vinculado']);
        $data['costo'] = '500.00';$this->send($this->op('salud', $data, 'update', $create['record_uuid'], 2))->assertJsonPath('status', 422);
        $this->send($this->op('salud', [], 'delete', $create['record_uuid'], 2))->assertJsonPath('status', 422);
        $this->assertSame('120.50', $record->fresh()->costo);$this->assertDatabaseCount('egresos', 1);
        $before = DB::table('workspace_sync_changes')->where('module', 'salud')->count();
        $foreign = User::factory()->create();
        try {
            Egreso::create(['codigo' => 'P51-FOREIGN', 'user_id' => $foreign->id, 'finca_id' => $this->farm->id,
                'salud_id' => $record->id, 'categoria' => 'salud_animal', 'fecha' => today(), 'monto' => 1]);
            $this->fail('No se debe vincular un evento sanitario ajeno.');
        } catch (\Illuminate\Validation\ValidationException $expected) {
            $this->assertDatabaseCount('egresos', 1);
            $this->assertSame($before, DB::table('workspace_sync_changes')->where('module', 'salud')->count());
        }
        Egreso::sole()->delete();
        $this->assertFalse(\App\Services\WorkspaceJournal::serialize($record->fresh())['egreso_vinculado']);
    }

    public function test_downloads_native_templates_and_changes_remain_account_scoped(): void
    {
        $this->getJson('/api/offline/v1/workspace/views')->assertOk()->assertJsonStructure(['views' => ['potreros' => ['index','create','show','edit'], 'produccion' => ['index','create','edit'], 'salud' => ['index','preventivo','clinico','show','edit']]]);
        $snapshot = $this->postJson('/api/offline/v1/workspace/snapshots')->assertOk()->assertJsonPath('references.workspace_schema', 3)->json();
        $private = $this->op('potreros', ['finca_uuid' => $this->farm->client_uuid, 'nombre' => 'Privado', 'estado' => 'activo']);
        $this->send($private);
        $this->send($this->op('produccion', $this->references() + ['fecha' => today()->format('Y-m-d'), 'litros' => '12.35']));
        $this->send($this->op('salud', $this->references() + ['tipo' => 'control', 'nombre_producto' => 'Privado', 'fecha_aplicacion' => today()->format('Y-m-d'), 'estado' => 'completado']));
        $this->getJson('/api/offline/v1/workspace/changes?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.module', 'potreros');
        $b = User::factory()->create();$this->actingAs($b);
        $this->postJson('/api/offline/v1/workspace/snapshots')->assertOk()->assertJsonPath('records', []);
        $this->getJson('/api/offline/v1/workspace/changes?cursor='.urlencode($snapshot['cursor']))->assertNotFound();
        $this->owner = $b;
        $this->send($this->op('potreros', [], 'delete', $private['record_uuid']))->assertJsonPath('status', 404);
        $this->assertNotSoftDeleted('potreros', ['client_uuid' => $private['record_uuid']]);
    }
}
