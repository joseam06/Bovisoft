<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FincasTestCase;

class WorkspaceSyncTest extends FincasTestCase
{
    use RefreshDatabase;

    private User $a;

    private User $b;

    private Finca $farm;

    private Finca $foreign;

    private string $base = '/api/offline/v1/workspace';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->a = User::factory()->create();
        $this->b = User::factory()->create();
        $this->farm = Finca::create(['nombre' => 'Propia', 'codigo' => 'WORK-A', 'user_id' => $this->a->id, 'activa' => true, 'latitud' => 4.5, 'longitud' => -74]);
        $this->foreign = Finca::create(['nombre' => 'Ajena', 'codigo' => 'WORK-B', 'user_id' => $this->b->id, 'activa' => true, 'latitud' => 5, 'longitud' => -75]);
        $this->actingAs($this->a);
    }

    private function op(string $module = 'fincas', string $type = 'create', array $data = []): array
    {
        return ['module' => $module, 'type' => $type, 'operation_uuid' => (string) Str::uuid(), 'record_uuid' => (string) Str::uuid(),
            'data' => $data ?: ['nombre' => 'Finca nueva', 'latitud' => '4.5', 'longitud' => '-74.1']];
    }

    private function send(array $op, ?int $account = null)
    {
        return $this->postJson($this->base.'/operations', ['account_id' => $account ?? $this->a->id,
            'device_uuid' => '11111111-1111-4111-8111-111111111111', 'operation' => $op]);
    }

    public function test_replaying_a_committed_farm_and_its_dependent_animal_is_idempotent(): void
    {
        $farm = $this->op();
        $first = $this->send($farm)->assertOk()->assertJsonPath('status', 201)->json();
        $this->send($farm)->assertExactJson($first);
        $animal = $this->op('animales', data: ['nombre' => 'Lucera offline', 'tipo' => 'vaca', 'sexo' => 'hembra', 'finca_uuid' => $farm['record_uuid']]);
        $created = $this->send($animal)->assertOk()->assertJsonPath('status', 201)->json();
        $this->send($animal)->assertExactJson($created);
        $this->assertDatabaseCount('animales', 1);
        $this->assertSame(1, Animal::sole()->version);
        $this->assertSame($first['record']['id'], Animal::sole()->finca_id);
        $this->assertSame(1, DB::table('workspace_sync_changes')->where('record_uuid', $animal['record_uuid'])->count());
        $this->assertSame(1, DB::table('workspace_sync_changes')->where('record_uuid', $farm['record_uuid'])->count());
        $this->assertDatabaseCount('workspace_sync_receipts', 2);
        $farm['data']['nombre'] = 'Otro payload';
        $this->send($farm)->assertJsonPath('status', 409)->assertJsonPath('error', 'operation_uuid_reused');
    }

    public function test_missing_dependency_and_unauthorized_parent_do_not_create_animals(): void
    {
        foreach ([(string) Str::uuid(), $this->foreign->client_uuid] as $uuid) {
            $op = $this->op('animales', data: ['tipo' => 'vaca', 'sexo' => 'hembra', 'finca_uuid' => $uuid]);
            $first = $this->send($op)->assertJsonPath('status', 422)->json();
            $this->send($op)->assertExactJson($first);
        }
        $this->assertDatabaseCount('animales', 0);
    }

    public function test_concurrent_edits_and_normal_forms_publish_versions_without_silent_overwrites(): void
    {
        $snapshot = $this->postJson($this->base.'/snapshots')->assertOk()->json();
        $payload = ['nombre' => 'Edición normal', 'latitud' => 4.5, 'longitud' => -74, 'base_version' => 1];
        $this->put('/fincas/'.$this->farm->id, $payload)->assertRedirect();
        $this->assertSame(2, $this->farm->fresh()->version);
        $op = $this->op(type: 'update', data: ['nombre' => 'Borrador otro dispositivo', 'latitud' => 4.5, 'longitud' => -74]);
        $op['record_uuid'] = $this->farm->client_uuid;
        $op['base_version'] = 1;
        $conflict = $this->send($op)->assertJsonPath('status', 409)->assertJsonPath('current.nombre', 'Edición normal')->json();
        $this->send($op)->assertExactJson($conflict);
        $this->assertSame('Edición normal', $this->farm->fresh()->nombre);
        $this->getJson($this->base.'/changes?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('changes.0.record.version', 2);
        $this->putJson('/fincas/'.$this->farm->id, $payload + ['area' => 10])->assertStatus(409);
        $op['operation_uuid'] = (string) Str::uuid();
        $op['base_version'] = 2;
        $this->send($op)->assertJsonPath('status', 200)->assertJsonPath('record.version', 3);
    }

    public function test_only_owned_records_are_downloaded_and_denied_actions_leave_data_intact(): void
    {
        $animal = Animal::create(['codigo' => 'WORK-ANI-B', 'nombre' => 'Privado B', 'tipo' => 'vaca', 'sexo' => 'hembra', 'user_id' => $this->b->id, 'finca_id' => $this->foreign->id]);
        $response = $this->postJson($this->base.'/snapshots')->assertOk()->assertJsonCount(1, 'records');
        $this->assertStringNotContainsString('Ajena', $response->getContent());
        $this->assertStringNotContainsString('Privado B', $response->getContent());
        $before = $animal->fresh()->getRawOriginal();
        $op = $this->op('animales', 'delete');
        $op['data'] = [];
        $op['record_uuid'] = $animal->client_uuid;
        $op['base_version'] = 1;
        $this->send($op)->assertJsonPath('status', 404);
        $this->assertSame($before, $animal->fresh()->getRawOriginal());
        $this->send($this->op(), $this->b->id)->assertStatus(409);
        $this->actingAs($this->b)->getJson($this->base.'/changes?cursor='.urlencode($response->json('cursor')))->assertNotFound();
    }

    public function test_animal_potrero_counts_and_receipts_commit_once_and_invalid_moves_are_atomic(): void
    {
        $potrero = Potrero::create(['codigo' => 'WORK-POT-A', 'nombre' => 'Potrero A', 'user_id' => $this->a->id, 'finca_id' => $this->farm->id, 'estado' => 'activo', 'animales_actuales' => 0]);
        $op = $this->op('animales', data: ['tipo' => 'vaca', 'sexo' => 'hembra', 'finca_uuid' => $this->farm->client_uuid, 'potrero_id' => $potrero->id]);
        $first = $this->send($op)->assertJsonPath('status', 201)->json();
        $this->send($op)->assertExactJson($first);
        $this->assertSame(1, $potrero->fresh()->animales_actuales);
        $invalid = $this->op('animales', 'update', $op['data'] + ['nombre' => 'Cambio no válido']);
        $invalid['record_uuid'] = $op['record_uuid'];
        $invalid['base_version'] = 1;
        $invalid['data']['finca_uuid'] = $this->foreign->client_uuid;
        $this->send($invalid)->assertJsonPath('status', 422);
        $this->assertSame(1, Animal::sole()->version);
        $this->assertSame(1, $potrero->fresh()->animales_actuales);
        $delete = $this->op('animales', 'delete');
        $delete['data'] = [];
        $delete['record_uuid'] = $op['record_uuid'];
        $delete['base_version'] = 1;
        $deleted = $this->send($delete)->assertJsonPath('record.deleted', true)->assertJsonPath('record.version', 2)->json();
        $this->send($delete)->assertExactJson($deleted);
        $this->assertSame(0, $potrero->fresh()->animales_actuales);
        $this->assertSoftDeleted('animales', ['client_uuid' => $op['record_uuid']]);
    }

    public function test_soft_deletions_arrive_as_removals_and_farms_with_children_are_protected(): void
    {
        $animal = Animal::create(['codigo' => 'WORK-ANI-A', 'tipo' => 'vaca', 'sexo' => 'hembra', 'user_id' => $this->a->id, 'finca_id' => $this->farm->id]);
        $snapshot = $this->postJson($this->base.'/snapshots')->assertOk()->json();
        $op = $this->op(type: 'delete');
        $op['data'] = [];
        $op['record_uuid'] = $this->farm->client_uuid;
        $op['base_version'] = 1;
        $this->send($op)->assertJsonPath('status', 422);
        $this->assertNotSoftDeleted('fincas', ['id' => $this->farm->id]);
        $animal->delete();
        $op['operation_uuid'] = (string) Str::uuid();
        $this->send($op)->assertJsonPath('status', 200)->assertJsonPath('record.deleted', true);
        $this->getJson($this->base.'/changes?cursor='.urlencode($snapshot['cursor']))->assertOk()
            ->assertJsonPath('changes.0.action', 'remove')->assertJsonPath('changes.1.action', 'remove');
    }

    public function test_related_history_blocks_deletion_without_losing_original_attribution(): void
    {
        $animal = Animal::create(['codigo' => 'WORK-HIST', 'tipo' => 'vaca', 'sexo' => 'hembra', 'user_id' => $this->a->id, 'finca_id' => $this->farm->id]);
        $production = app(\App\Services\ProduccionService::class)->create($this->a, [
            'animal_id' => $animal->id, 'finca_id' => $this->farm->id, 'fecha' => now()->format('Y-m-d'), 'litros' => '12.35',
        ]);
        $op = $this->op('animales', 'delete');
        $op['data'] = [];
        $op['record_uuid'] = $animal->client_uuid;
        $op['base_version'] = 1;
        $this->send($op)->assertJsonPath('status', 422)->assertJsonStructure(['errors' => ['historial']]);
        $this->assertNotSoftDeleted('animales', ['id' => $animal->id]);
        $newFarm = Finca::create(['nombre' => 'Destino', 'codigo' => 'WORK-DEST', 'user_id' => $this->a->id, 'activa' => true]);
        $animal->update(['finca_id' => $newFarm->id]);
        $op = $this->op('fincas', 'delete');
        $op['data'] = [];
        $op['record_uuid'] = $this->farm->client_uuid;
        $op['base_version'] = 1;
        $this->send($op)->assertJsonPath('status', 422)->assertJsonStructure(['errors' => ['historial']]);
        $this->assertNotSoftDeleted('fincas', ['id' => $this->farm->id]);
        $this->assertSame($this->farm->id, $production->fresh()->finca_id);
        $this->assertSame($animal->id, $production->fresh()->animal_id);
    }

    public function test_pausing_and_reenabling_does_not_drop_or_duplicate_operations(): void
    {
        $op = $this->op();
        config(['offline.workspace.enabled' => false]);
        $this->send($op)->assertStatus(503);
        $this->assertDatabaseCount('workspace_sync_receipts', 0);
        config(['offline.workspace.enabled' => true]);
        $first = $this->send($op)->assertJsonPath('status', 201)->json();
        $this->send($op)->assertExactJson($first);
        $this->assertSame(1, Finca::where('client_uuid', $op['record_uuid'])->count());
    }

    public function test_normal_finca_toggle_preserves_access_when_legacy_coordinates_are_missing(): void
    {
        $this->farm->update(['latitud' => null, 'longitud' => null]);
        $this->postJson('/fincas/'.$this->farm->id.'/toggle-estado', ['base_version' => 2])->assertOk()->assertJsonPath('activa', false);
        $this->assertSame(3, $this->farm->fresh()->version);
    }

    public function test_normal_form_requires_a_version_and_shows_a_visible_conflict_without_rebasing_silently(): void
    {
        $payload = ['nombre' => 'No sobrescribir', 'latitud' => 4.5, 'longitud' => -74];
        $this->putJson('/fincas/'.$this->farm->id, $payload)->assertUnprocessable()->assertJsonValidationErrors('base_version');
        $this->assertSame('Propia', $this->farm->fresh()->nombre);
        $this->farm->update(['nombre' => 'Cambio remoto']);
        $this->from('/fincas/'.$this->farm->id.'/edit')->put('/fincas/'.$this->farm->id, $payload + ['base_version' => 1])
            ->assertRedirect('/fincas/'.$this->farm->id.'/edit')->assertSessionHasErrors('base_version');
        $this->get('/fincas/'.$this->farm->id.'/edit')->assertOk()->assertSee('Revisa los cambios antes de continuar')
            ->assertSee('value="1"', false)->assertSee('No sobrescribir');
        $this->assertSame('Cambio remoto', $this->farm->fresh()->nombre);
        $this->assertSame(2, $this->farm->fresh()->version);
    }

    public function test_generic_dashboard_shell_contains_no_account_data_or_csrf(): void
    {
        $this->get('/offline-shell')->assertOk()->assertSee('Estás usando BoviSoft sin conexión')->assertSee('btn-campanita', false)
            ->assertDontSee('workspace-controls')->assertDontSee('workspace-unlock')->assertDontSee('Preparar este dispositivo')
            ->assertDontSee($this->a->email)->assertDontSee('name="_token"', false)->assertDontSee('name="csrf-token"', false);
    }

    public function test_workspace_cursor_and_access_do_not_expire_after_seven_days(): void
    {
        $snapshot = $this->postJson($this->base.'/snapshots')->assertOk()->json();
        $this->travel(60)->days();
        $this->getJson($this->base.'/changes?cursor='.urlencode($snapshot['cursor']))->assertOk()->assertJsonPath('account_id', $this->a->id);
        $this->getJson('/offline/session')->assertOk()->assertJsonMissingPath('lease_expires_at');
    }
}
