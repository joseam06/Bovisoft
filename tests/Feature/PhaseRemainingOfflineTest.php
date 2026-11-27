<?php
namespace Tests\Feature;

use App\Models\{Animal,Finca,Ingreso,User,WorkspaceProfile};
use App\Services\{AlertRules,Money};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\FincasTestCase;

class PhaseRemainingOfflineTest extends FincasTestCase
{
    use RefreshDatabase;
    public function test_native_templates_catalog_safe_profile_conflict_and_account_isolation(): void
    {
        $this->withoutVite();$a=User::factory()->create(['name'=>'Cuenta ficticia A']);$b=User::factory()->create(['name'=>'Cuenta ficticia B']);
        $farm=Finca::create(['user_id'=>$a->id,'codigo'=>'P52-F','nombre'=>'Finca privada A','area'=>10,'activa'=>true,'latitud'=>4.5,'longitud'=>-74]);
        $animal=Animal::create(['user_id'=>$a->id,'finca_id'=>$farm->id,'codigo'=>'P52-A','nombre'=>'Animal privado A','tipo'=>'vaca','sexo'=>'hembra','estado'=>'activo']);
        $this->actingAs($a);
        $templates=$this->getJson('/api/offline/v1/workspace/views')->assertOk()->json('views');
        foreach(['ingresos','egresos','perfil','finanzas','alertas','reportes'] as $m)$this->assertArrayHasKey($m,$templates);
        foreach(['inventario','salud','vacunacion','tratamientos','historial','financiero'] as $kind){$this->assertStringContainsString('data-report-collection',$templates['reportes']['print_'.$kind]);$this->assertStringContainsString('data:image/png;base64,',$templates['reportes']['print_'.$kind]);}
        $snapshot=$this->postJson('/api/offline/v1/workspace/snapshots')->assertOk()->json();
        $catalog=$snapshot['references'];
        $this->assertSame(4,$catalog['workspace_schema']);$this->assertContains('perfil',$catalog['complete_modules']);$this->assertArrayHasKey('alert_rules',$catalog);
        $profile=WorkspaceProfile::where('user_id',$a->id)->firstOrFail();
        $op=['operation_uuid'=>(string)Str::uuid(),'record_uuid'=>$profile->client_uuid,'module'=>'perfil','type'=>'update','base_version'=>$profile->version,'data'=>['name'=>'Nombre local seguro']];
        $send=fn($op)=>$this->postJson('/api/offline/v1/workspace/operations',['account_id'=>$a->id,'device_uuid'=>(string)Str::uuid(),'operation'=>$op])->assertOk();
        $first=$send($op)->assertJsonPath('status',200)->assertJsonPath('record.name','Nombre local seguro')->json();
        $send($op)->assertExactJson($first);$this->assertSame('Nombre local seguro',$a->fresh()->name);
        $stale=$op;$stale['operation_uuid']=(string)Str::uuid();$stale['data']['name']='Obsoleto';$send($stale)->assertJsonPath('status',409);
        $sensitive=$stale;$sensitive['operation_uuid']=(string)Str::uuid();$sensitive['base_version']=$profile->fresh()->version;$sensitive['data']['email']='unsafe@example.test';$send($sensitive)->assertJsonPath('status',422);
        $this->assertSame($a->email,$a->fresh()->email);
        $serialized=\App\Services\WorkspaceJournal::serialize($profile->fresh());foreach(['email','password','remember_token','photo'] as $key)$this->assertArrayNotHasKey($key,$serialized);
        $this->get('/dashboard')->assertOk()->assertSee('data-dashboard-alert-pattern');
        $this->get('/perfil')->assertOk()->assertSee('base_version');
        $this->patch('/perfil',['name'=>'Cambio web','email'=>$a->email,'base_version'=>$profile->fresh()->version])->assertRedirect();
        $this->assertSame('Cambio web',$profile->fresh()->name);
        $this->actingAs($b);$this->postJson('/api/offline/v1/workspace/snapshots')->assertOk()->assertDontSee('Finca privada A')->assertDontSee('Animal privado A')->assertDontSee('Cambio web');
        $this->postJson('/api/offline/v1/workspace/operations',['account_id'=>$b->id,'device_uuid'=>(string)Str::uuid(),'operation'=>$op])->assertOk()->assertJsonPath('status',404);
        $this->get('/finanzas/ingresos/nuevo')->assertOk();$this->get('/alertas')->assertOk();$this->get('/reportes')->assertOk();
    }
    public function test_exact_money_and_shared_alert_rules_are_deterministic(): void
    {
        $this->assertSame('0.30',Money::sum(['0.10','0.20']));$this->assertSame(99999999999999,Money::cents('999999999999.99'));
        $catalog=AlertRules::catalog();$this->assertNotEmpty($catalog['rules']);
        $records=[['record_uuid'=>(string)Str::uuid(),'id'=>1,'tipo'=>'vacunacion','categoria'=>'preventivo','estado'=>'completado','proxima_aplicacion'=>today()->subDay()->format('Y-m-d'),'fecha_aplicacion'=>today()->subDays(5)->format('Y-m-d'),'animal_nombre'=>'Ficticio','finca_nombre'=>'Ficticia']];
        $first=AlertRules::generate($records,today()->format('Y-m-d'));$this->assertCount(1,$first);$this->assertSame('critico',$first[0]['nivel']);$this->assertSame($first,AlertRules::generate($records,today()->format('Y-m-d')));
        $paddocks=[['record_uuid'=>(string)Str::uuid(),'id'=>2,'nombre'=>'Rotación ficticia','fecha_ultima_rotacion'=>today()->subDays(10)->format('Y-m-d'),'dias_descanso'=>7]];
        $rotation=AlertRules::generate([],today()->format('Y-m-d'),$paddocks);$this->assertCount(1,$rotation);$this->assertSame('/potreros/2',$rotation[0]['url']);
    }
    public function test_linked_expense_ack_replay_rollback_and_reversal_preserve_health_history(): void
    {
        $this->withoutVite();$user=User::factory()->create();$this->actingAs($user);
        $farm=Finca::create(['user_id'=>$user->id,'codigo'=>'LINK-F','nombre'=>'Ficticia','area'=>10,'activa'=>true,'latitud'=>4.5,'longitud'=>-74]);
        $animal=Animal::create(['user_id'=>$user->id,'finca_id'=>$farm->id,'codigo'=>'LINK-A','tipo'=>'vaca','sexo'=>'hembra','estado'=>'activo']);
        $health=\App\Models\Salud::create(['user_id'=>$user->id,'finca_id'=>$farm->id,'animal_id'=>$animal->id,'codigo'=>'LINK-S','tipo'=>'tratamiento','estado'=>'en_tratamiento','fecha_aplicacion'=>today(),'nombre_producto'=>'Ficticio','costo'=>'15.20']);
        $op=['operation_uuid'=>(string)Str::uuid(),'record_uuid'=>(string)Str::uuid(),'module'=>'egresos','type'=>'create','data'=>['finca_uuid'=>$farm->client_uuid,'animal_uuid'=>$animal->client_uuid,'salud_uuid'=>$health->client_uuid,'categoria'=>'salud_animal','monto'=>'15.20','fecha'=>today()->format('Y-m-d')]];
        $version=$health->version;$sync=app(\App\Services\WorkspaceSync::class);$device=(string)Str::uuid();
        \Illuminate\Support\Facades\DB::listen(function($query){if(str_contains($query->sql,'insert into "workspace_sync_receipts"')&&str_contains($query->sql,'values')){if(app()->bound('p52.fail.receipt'))throw new \RuntimeException('Fallo ficticio después del efecto');}});
        app()->instance('p52.fail.receipt',true);
        try{$sync->apply($user,$device,$op);$this->fail('No se inyectó el fallo');}catch(\RuntimeException $e){$this->assertSame('Fallo ficticio después del efecto',$e->getMessage());}finally{app()->offsetUnset('p52.fail.receipt');}
        $this->assertDatabaseCount('egresos',0);$this->assertSame((int)$version,(int)$health->fresh()->version);
        $first=$sync->apply($user,$device,$op);$this->assertSame(201,$first['status']);$this->assertSame($first,$sync->apply($user,$device,$op));$this->assertDatabaseCount('egresos',1);
        $this->assertTrue(\App\Services\WorkspaceJournal::serialize($health->fresh())['egreso_vinculado']);$this->assertSame((int)$version+1,(int)$health->fresh()->version);
        $this->assertTrue(collect($first['related'])->contains(fn($r)=>$r['module']==='salud'&&$r['record']['egreso_vinculado']));
        $delete=['operation_uuid'=>(string)Str::uuid(),'record_uuid'=>$op['record_uuid'],'module'=>'egresos','type'=>'delete','base_version'=>1,'data'=>[]];
        $removed=$sync->apply($user,$device,$delete);$this->assertSame(200,$removed['status']);$this->assertSame($removed,$sync->apply($user,$device,$delete));
        $this->assertFalse(\App\Services\WorkspaceJournal::serialize($health->fresh())['egreso_vinculado']);$this->assertSame('15.20',$health->fresh()->costo);
    }
}
