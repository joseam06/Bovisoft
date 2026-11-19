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

class WorkspaceSyncPhaseFourTest extends FincasTestCase
{
    use RefreshDatabase;

    private function send(User $user, array $operation)
    {
        return $this->actingAs($user)->postJson('/api/offline/v1/workspace/operations', [
            'account_id' => $user->id, 'device_uuid' => '11111111-1111-4111-8111-111111111111', 'operation' => $operation,
        ]);
    }

    public function test_acknowledgements_are_bound_to_account_operation_and_record_including_replays(): void
    {
        $this->withoutVite();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $operation = ['module' => 'fincas', 'type' => 'create', 'operation_uuid' => (string) Str::uuid(),
            'record_uuid' => (string) Str::uuid(), 'data' => ['nombre' => 'Ficticia fase cuatro', 'latitud' => 4.5, 'longitud' => -74]];
        $first = $this->send($a, $operation)->assertOk()->assertJsonPath('protocol', 1)
            ->assertJsonPath('account_id', $a->id)->assertJsonPath('operation_uuid', $operation['operation_uuid'])
            ->assertJsonPath('record_uuid', $operation['record_uuid'])->assertJsonPath('module', 'fincas')
            ->assertJsonPath('status', 201)->assertJsonPath('record.version', 1)->json();
        $this->send($a, $operation)->assertExactJson($first);
        $denied = $operation;
        $denied['type'] = 'delete';
        $denied['base_version'] = 1;
        $denied['operation_uuid'] = (string) Str::uuid();
        $denied['data'] = [];
        $this->send($b, $denied)->assertJsonPath('status', 404)->assertJsonPath('account_id', $b->id)
            ->assertJsonMissingPath('record')->assertJsonMissingPath('current');
        $snapshot = $this->actingAs($b)->postJson('/api/offline/v1/workspace/snapshots')->assertOk()->json();
        $this->getJson('/api/offline/v1/workspace/changes?cursor='.urlencode($snapshot['cursor']))->assertOk()
            ->assertJsonPath('valid.fincas', [])->assertJsonPath('valid.animales', [])->assertJsonPath('changes', []);
        $this->assertSame(1, Finca::sole()->version);
        $this->assertNotSoftDeleted('fincas', ['client_uuid' => $operation['record_uuid']]);
    }

    public function test_failure_after_domain_write_rolls_back_receipt_journal_counter_and_potrero_before_retry(): void
    {
        $this->withoutVite();
        $a = User::factory()->create();
        $farm = Finca::create(['nombre' => 'Ficticia transaccional', 'codigo' => 'P4-FARM', 'user_id' => $a->id,
            'activa' => true, 'latitud' => 4.5, 'longitud' => -74]);
        $potrero = Potrero::create(['codigo' => 'P4-POT', 'nombre' => 'Ficticio', 'user_id' => $a->id,
            'finca_id' => $farm->id, 'estado' => 'activo', 'animales_actuales' => 0]);
        $operation = ['module' => 'animales', 'type' => 'create', 'operation_uuid' => (string) Str::uuid(),
            'record_uuid' => (string) Str::uuid(), 'data' => ['nombre' => 'Ficticia', 'tipo' => 'vaca', 'sexo' => 'hembra',
                'finca_uuid' => $farm->client_uuid, 'potrero_id' => $potrero->id]];
        $heads = DB::table('workspace_sync_heads')->get()->toArray();
        $counters = DB::table('workspace_code_counters')->get()->toArray();
        $fail = true;
        DB::listen(function ($query) use (&$fail) {
            if ($fail && str_contains(strtolower($query->sql), 'insert into') && str_contains($query->sql, 'workspace_sync_receipts')) {
                $fail = false;
                throw new \RuntimeException('Fallo ficticio antes del commit del recibo');
            }
        });
        $this->send($a, $operation)->assertStatus(500);
        $this->assertDatabaseCount('animales', 0);
        $this->assertDatabaseCount('workspace_sync_receipts', 0);
        $this->assertDatabaseMissing('workspace_sync_changes', ['record_uuid' => $operation['record_uuid']]);
        $this->assertSame(0, $potrero->fresh()->animales_actuales);
        $this->assertEquals($heads, DB::table('workspace_sync_heads')->get()->toArray());
        $this->assertEquals($counters, DB::table('workspace_code_counters')->get()->toArray());
        $saved = $this->send($a, $operation)->assertOk()->assertJsonPath('status', 201)->assertJsonPath('record.version', 1)->json();
        $this->send($a, $operation)->assertExactJson($saved);
        $this->assertDatabaseCount('animales', 1);
        $this->assertSame(1, Animal::sole()->version);
        $this->assertSame(1, $potrero->fresh()->animales_actuales);
        $this->assertDatabaseCount('workspace_sync_receipts', 1);
        $this->assertSame(1, DB::table('workspace_sync_changes')->where('record_uuid', $operation['record_uuid'])->count());
    }
}
