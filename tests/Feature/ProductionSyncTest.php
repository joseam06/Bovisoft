<?php

namespace Tests\Feature;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Produccion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\FincasTestCase;

class ProductionSyncTest extends FincasTestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $other;

    private Finca $finca;

    private Finca $foreignFinca;

    private Animal $animal;

    private Animal $foreignAnimal;

    private string $base = '/api/offline/v1/produccion';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->owner = User::factory()->create(['email' => 'sync-a@example.test']);
        $this->other = User::factory()->create(['email' => 'sync-b@example.test']);
        $this->finca = Finca::create(['codigo' => 'SYNC-A', 'nombre' => 'Finca A', 'user_id' => $this->owner->id, 'activa' => true]);
        $this->foreignFinca = Finca::create(['codigo' => 'SYNC-B', 'nombre' => 'Finca B', 'user_id' => $this->other->id, 'activa' => true]);
        $this->animal = Animal::create(['codigo' => 'SYNC-ANI-A', 'nombre' => 'Animal A', 'user_id' => $this->owner->id,
            'finca_id' => $this->finca->id, 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
        $this->foreignAnimal = Animal::create(['codigo' => 'SYNC-ANI-B', 'nombre' => 'Animal B', 'user_id' => $this->other->id,
            'finca_id' => $this->foreignFinca->id, 'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo']);
        $this->actingAs($this->owner);
    }

    public function test_lost_response_replay_creates_exactly_one_row_event_and_receipt(): void
    {
        $operation = $this->operation();
        $first = $this->push([$operation])->assertOk()->assertJsonPath('results.0.status', 201)->json();
        // Discard the first response: the client has no acknowledgement and retries.
        $this->push([$operation])->assertOk()->assertExactJson($first);
        $this->assertDatabaseCount('producciones', 1);
        $this->assertDatabaseCount('production_sync_changes', 1);
        $this->assertDatabaseCount('production_sync_receipts', 1);
        $this->assertSame(1, Produccion::sole()->version);

        $changed = $operation;
        $changed['data']['litros'] = '22.00';
        $this->push([$changed])->assertJsonPath('results.0.status', 409)->assertJsonPath('results.0.body.error', 'operation_uuid_reused');
        $this->assertSame('12.35', Produccion::sole()->litros);
    }

    public function test_same_record_uuid_different_operation_is_not_another_measurement(): void
    {
        $operation = $this->operation();
        $this->push([$operation])->assertJsonPath('results.0.status', 201);
        $operation['operation_uuid'] = (string) Str::uuid();
        $this->push([$operation])->assertJsonPath('results.0.status', 409)->assertJsonPath('results.0.body.error', 'record_uuid_exists');
        $this->push([$this->operation()])->assertJsonPath('results.0.status', 201);
        $this->assertDatabaseCount('producciones', 2);
        $this->assertSame(2, Produccion::distinct()->count('codigo'));
    }

    public function test_stale_edit_conflicts_and_a_new_rebased_operation_can_be_applied(): void
    {
        $create = $this->operation();
        $this->push([$create])->assertJsonPath('results.0.status', 201);
        $edit = $this->edit($create['record_uuid'], 1, '20.00');
        $this->push([$edit])->assertJsonPath('results.0.status', 200)->assertJsonPath('results.0.body.record.version', 2);
        $stale = $this->edit($create['record_uuid'], 1, '30.00');
        $result = $this->push([$stale])->assertJsonPath('results.0.status', 409)
            ->assertJsonPath('results.0.body.current.version', 2)->json();
        $this->assertSame('20.00', Produccion::sole()->litros);
        $this->push([$this->edit($create['record_uuid'], 2, '30.00')])->assertJsonPath('results.0.body.record.version', 3);
        $this->push([$stale])->assertExactJson($result);
        $this->assertDatabaseCount('production_sync_changes', 3);
    }

    public function test_normal_forms_publish_changes_and_refuse_a_stale_form(): void
    {
        $this->post('/produccion', $this->data())->assertRedirect();
        $record = Produccion::sole();
        $initial = $this->snapshot();
        $this->put('/produccion/'.$record->id, array_replace($this->data(), ['base_version' => 1, 'litros' => '19.00']))->assertRedirect();
        $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertOk()
            ->assertJsonPath('changes.0.record.version', 2)->assertJsonPath('changes.0.record.litros', '19.00');
        $this->from('/produccion/'.$record->id.'/edit')->put('/produccion/'.$record->id,
            array_replace($this->data(), ['base_version' => 1, 'litros' => '99.00']))
            ->assertRedirect()->assertSessionHasErrors('base_version');
        $this->assertSame('19.00', $record->fresh()->litros);
        $this->from('/produccion/'.$record->id)->delete('/produccion/'.$record->id, ['base_version' => 1])
            ->assertRedirect()->assertSessionHasErrors('base_version');
        $this->get('/produccion/'.$record->id)->assertOk()->assertSee('Este registro cambió');
        $this->assertFalse($record->fresh()->trashed());
        $this->deleteJson('/produccion/'.$record->id, ['base_version' => 2])->assertRedirect();
        $this->assertSoftDeleted('producciones', ['id' => $record->id]);
        $delta = $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertOk();
        $this->assertSame('remove', collect($delta->json('changes'))->last()['action']);
        $this->assertSame(3, collect($delta->json('changes'))->last()['version']);
    }

    public function test_snapshot_is_stable_across_pages_and_changes_catch_up_after_it(): void
    {
        $this->post('/produccion', $this->data())->assertRedirect();
        $this->post('/produccion', $this->data())->assertRedirect();
        $initial = $this->snapshot(['page_size' => 1]);
        $this->assertFalse($initial['complete']);
        $this->assertNull($initial['cursor']);
        $second = Produccion::orderByDesc('id')->first();
        $this->put('/produccion/'.$second->id, array_replace($this->data(), ['base_version' => 1, 'litros' => '22.00']))->assertRedirect();
        $page = $this->getJson($this->base.'/snapshots/'.$initial['snapshot_uuid'].'?page=2')->assertOk()
            ->assertJsonPath('complete', true)->assertJsonPath('records.0.version', 1)->json();
        $this->getJson($this->base.'/changes?cursor='.urlencode($page['cursor']))->assertOk()
            ->assertJsonPath('changes.0.record.version', 2)->assertJsonPath('changes.0.record.litros', '22.00');
    }

    public function test_partial_batch_and_paged_deltas_can_resume_without_duplicate_writes(): void
    {
        $initial = $this->snapshot();
        $bad = $this->operation();
        $bad['data']['litros'] = 0;
        $ops = [$this->operation(), $bad, $this->operation()];
        $first = $this->push($ops)->assertOk()->assertJsonPath('results.0.status', 201)
            ->assertJsonPath('results.1.status', 422)->assertJsonPath('results.2.status', 201)->json();
        $this->push($ops)->assertExactJson($first);
        $this->assertDatabaseCount('producciones', 2);
        $this->assertDatabaseCount('production_sync_changes', 2);
        $page = $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']).'&limit=1')
            ->assertJsonCount(1, 'changes')->assertJsonPath('has_more', true)->json();
        $this->getJson($this->base.'/changes?cursor='.urlencode($page['cursor']).'&limit=1')
            ->assertJsonCount(1, 'changes')->assertJsonPath('has_more', false);
    }

    public function test_account_bound_snapshots_cursors_and_operations_never_expose_foreign_records(): void
    {
        $op = $this->operation();
        $this->push([$op])->assertJsonPath('results.0.status', 201);
        $initial = $this->snapshot();
        $this->actingAs($this->other);
        $this->getJson($this->base.'/snapshots/'.$initial['snapshot_uuid'])->assertNotFound();
        $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertNotFound();
        $this->postJson($this->base.'/snapshots', ['finca_ids' => [$this->finca->id]])->assertNotFound();
        $own = $this->postJson($this->base.'/snapshots', [])->assertOk()->assertJsonCount(0, 'records')
            ->assertJsonPath('references.fincas.0.id', $this->foreignFinca->id)->json();
        $this->push([$this->edit($op['record_uuid'], 1, '90.00')])->assertJsonPath('results.0.status', 404)->assertDontSee('12.35');
        $ownOp = $this->operation();
        $ownOp['operation_uuid'] = $op['operation_uuid'];
        $ownOp['data']['animal_id'] = $this->foreignAnimal->id;
        $ownOp['data']['finca_id'] = $this->foreignFinca->id;
        $this->push([$ownOp])->assertJsonPath('results.0.status', 201);
        $this->getJson($this->base.'/changes?cursor='.urlencode($own['cursor']))->assertJsonCount(1, 'changes')
            ->assertJsonPath('changes.0.record.record_uuid', $ownOp['record_uuid']);
        $this->actingAs($this->owner)->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))
            ->assertJsonCount(0, 'changes')->assertDontSee($ownOp['record_uuid']);
    }

    public function test_reference_catalog_and_authoritative_manifest_detect_changes_without_production_edits(): void
    {
        $this->post('/produccion', $this->data())->assertRedirect();
        $initial = $this->snapshot();
        $this->finca->update(['activa' => false]);
        $this->animal->update(['estado' => 'vendido']);
        $delta = $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertOk()
            ->assertJsonCount(0, 'changes')->assertJsonPath('references.authoritative', true)
            ->assertJsonPath('references.fincas.0.activa', false)->assertJsonPath('references.animales.0.estado', 'vendido');
        $this->assertCount(1, $delta->json('valid_record_uuids'));
        $this->animal->delete();
        $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertOk()->assertJsonCount(0, 'valid_record_uuids');
        $this->getJson($this->base.'/snapshots/'.$initial['snapshot_uuid'])->assertStatus(409);
    }

    public function test_replay_rechecks_current_authorization_instead_of_leaking_the_cached_result(): void
    {
        $op = $this->operation();
        $this->push([$op])->assertJsonPath('results.0.status', 201);
        $this->finca->update(['user_id' => $this->other->id]);
        $this->push([$op])->assertJsonPath('results.0.status', 404)->assertDontSee('12.35');
        $this->assertDatabaseCount('producciones', 1);
    }

    public function test_expired_or_tampered_cursors_require_a_new_initial_download(): void
    {
        $initial = $this->snapshot();
        $this->getJson($this->base.'/changes?cursor=invalid')->assertUnprocessable();
        $this->travel(25)->hours();
        $this->getJson($this->base.'/snapshots/'.$initial['snapshot_uuid'])->assertGone();
        $this->travel(7)->days();
        $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertGone();
    }

    public function test_pausing_the_api_preserves_receipts_and_replay_can_resume(): void
    {
        $op = $this->operation();
        $result = $this->push([$op])->json();
        config(['offline.production.enabled' => false]);
        $this->push([$op])->assertStatus(503);
        $this->assertDatabaseCount('production_sync_receipts', 1);
        config(['offline.production.enabled' => true]);
        $this->push([$op])->assertExactJson($result);
    }

    public function test_session_expiry_does_not_apply_operations_and_offline_delete_is_excluded(): void
    {
        $op = $this->operation();
        $op['type'] = 'delete';
        $this->push([$op])->assertJsonPath('results.0.status', 422);
        auth()->forgetGuards();
        $this->postJson($this->base.'/operations', ['device_uuid' => (string) Str::uuid(), 'operations' => [$this->operation()]])->assertUnauthorized();
        $this->assertDatabaseCount('producciones', 0);
    }

    private function snapshot(array $options = []): array
    {
        return $this->postJson($this->base.'/snapshots', $options + ['finca_ids' => [$this->finca->id]])->assertOk()->json();
    }

    public function test_default_scope_is_bounded_so_its_cursor_remains_usable(): void
    {
        for ($index = 0; $index < 50; $index++) {
            Finca::create(['user_id' => $this->owner->id, 'codigo' => 'SCOPE-'.$index, 'nombre' => 'Finca ficticia '.$index]);
        }
        $this->postJson($this->base.'/snapshots', [])->assertUnprocessable()->assertJsonValidationErrors('finca_ids');
        $initial = $this->snapshot();
        $this->getJson($this->base.'/changes?cursor='.urlencode($initial['cursor']))->assertOk();
    }

    private function push(array $operations)
    {
        return $this->postJson($this->base.'/operations', ['account_id' => auth()->id(), 'device_uuid' => (string) Str::uuid(), 'operations' => $operations]);
    }

    public function test_old_account_queue_is_rejected_before_any_operation_is_applied(): void
    {
        $this->postJson($this->base.'/operations', ['account_id' => $this->other->id,
            'device_uuid' => (string) Str::uuid(), 'operations' => [$this->operation()]])
            ->assertStatus(409)->assertJsonPath('error', 'account_mismatch');
        $this->assertDatabaseCount('producciones', 0);
        $this->assertDatabaseCount('production_sync_receipts', 0);
    }

    public function test_api_reuses_the_historical_date_rule(): void
    {
        $operation = $this->operation();
        $this->push([$operation])->assertJsonPath('results.0.status', 201);
        $this->animal->update(['estado' => 'vendido']);
        $this->push([$this->edit($operation['record_uuid'], 1, '20.00')])->assertJsonPath('results.0.status', 200);
        $edit = $this->edit($operation['record_uuid'], 2, '30.00');
        $edit['data']['fecha'] = now()->subDays(2)->format('Y-m-d');
        $this->push([$edit])->assertJsonPath('results.0.status', 422)
            ->assertJsonPath('results.0.body.errors.fecha.0', fn ($message) => str_contains($message, 'requiere revisión online'));
        $this->assertSame('20.00', Produccion::sole()->litros);
        $this->assertSame(2, Produccion::sole()->version);
    }

    private function operation(): array
    {
        return ['operation_uuid' => (string) Str::uuid(), 'record_uuid' => (string) Str::uuid(), 'type' => 'create', 'data' => $this->data()];
    }

    private function edit(string $record, int $version, string $litros): array
    {
        return ['operation_uuid' => (string) Str::uuid(), 'record_uuid' => $record, 'type' => 'update', 'base_version' => $version,
            'data' => array_replace($this->data(), ['litros' => $litros])];
    }

    private function data(): array
    {
        return ['animal_id' => $this->animal->id, 'finca_id' => $this->finca->id, 'fecha' => now()->subDay()->format('Y-m-d'),
            'sesion' => 'manana', 'litros' => '12.35', 'calidad' => 'buena', 'observaciones' => 'Medición ficticia'];
    }
}
