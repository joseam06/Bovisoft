<?php

namespace Tests\Feature;

use App\Models\{Animal, Egreso, Finca, Ingreso, Potrero, User};
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\FincasTestCase;

class FinancialIntegrityTest extends FincasTestCase
{
    use DatabaseMigrations;
    private User $owner;
    private Finca $farm;
    private Animal $animal;
    private Potrero $paddock;
    protected function setUp(): void
    {
        parent::setUp();$this->withoutVite();
        $this->owner=User::factory()->create();
        $this->farm=Finca::create(['user_id'=>$this->owner->id,'codigo'=>'F52-F','nombre'=>'Ficticia','area'=>10,'activa'=>true,'latitud'=>4.5,'longitud'=>-74]);
        $this->paddock=Potrero::create(['user_id'=>$this->owner->id,'finca_id'=>$this->farm->id,'codigo'=>'F52-P','nombre'=>'Ficticio','estado'=>'activo']);
        $this->animal=Animal::create(['user_id'=>$this->owner->id,'finca_id'=>$this->farm->id,'potrero_id'=>$this->paddock->id,'codigo'=>'F52-A','nombre'=>'Ficticio','tipo'=>'vaca','sexo'=>'hembra','estado'=>'activo']);
        $this->actingAs($this->owner);
    }
    private function op(string $module='ingresos', array $extra=[]): array
    {
        return ['operation_uuid'=>(string) Str::uuid(),'record_uuid'=>(string) Str::uuid(),'module'=>$module,'type'=>'create',
            'data'=>$extra+['finca_uuid'=>$this->farm->client_uuid,'animal_uuid'=>$this->animal->client_uuid,'animal_base_version'=>(int)$this->animal->fresh()->version,
                'tipo'=>'venta_animal','categoria'=>'otro','monto'=>'120.50','fecha'=>today()->format('Y-m-d')]];
    }
    private function send(array $op)
    {
        return $this->postJson('/api/offline/v1/workspace/operations',['account_id'=>$this->owner->id,'device_uuid'=>'11111111-1111-4111-8111-111111111111','operation'=>$op])->assertOk();
    }
    public function test_foreign_and_wrong_farm_references_are_denied_in_api_forms_and_reads(): void
    {
        $other=User::factory()->create();$foreignFarm=Finca::create(['user_id'=>$other->id,'codigo'=>'F52-B','nombre'=>'Ajena','area'=>10,'activa'=>true]);
        $foreign=Animal::create(['user_id'=>$other->id,'finca_id'=>$foreignFarm->id,'codigo'=>'F52-B-A','nombre'=>'Privado ajeno','tipo'=>'vaca','sexo'=>'hembra','estado'=>'activo']);
        $ownOther=Finca::create(['user_id'=>$this->owner->id,'codigo'=>'F52-OTHER','nombre'=>'Otra propia','area'=>10,'activa'=>true]);
        foreach(['ingresos','egresos'] as $module){
            $this->send($this->op($module,['animal_uuid'=>$foreign->client_uuid]))->assertJsonPath('status',404);
            $this->send($this->op($module,['finca_uuid'=>$ownOther->client_uuid]))->assertJsonPath('status',404);
            $this->postJson('/finanzas/'.$module,['finca_id'=>$this->farm->id,'animal_id'=>$foreign->id,'tipo'=>'otro','categoria'=>'otro','monto'=>'1.00','fecha'=>today()->format('Y-m-d'),
                'operation_uuid'=>(string)Str::uuid(),'record_uuid'=>(string)Str::uuid(),'device_uuid'=>(string)Str::uuid()])->assertNotFound();
        }
        $this->assertDatabaseCount('ingresos',0);$this->assertDatabaseCount('egresos',0);
        $id=DB::table('ingresos')->insertGetId(['codigo'=>'F52-LEGACY','user_id'=>$this->owner->id,'finca_id'=>$this->farm->id,'animal_id'=>$foreign->id,'tipo'=>'otro','monto'=>'1.00','fecha'=>today(),'client_uuid'=>(string)Str::uuid(),'version'=>1]);
        $this->get('/finanzas/ingresos/'.$id)->assertNotFound();
        $this->get('/finanzas')->assertOk()->assertDontSee('Privado ajeno');
    }
    public function test_sale_receipts_unique_active_link_reversal_and_legitimate_expenses(): void
    {
        $op=$this->op();$first=$this->send($op)->assertJsonPath('status',201)->json();$this->send($op)->assertExactJson($first);
        $this->assertDatabaseCount('ingresos',1);$this->assertSame('vendido',$this->animal->fresh()->estado);$this->assertSame(0,$this->paddock->fresh()->animales_actuales);
        $this->send($this->op())->assertJsonPath('status',422);
        $delete=['operation_uuid'=>(string)Str::uuid(),'record_uuid'=>$op['record_uuid'],'module'=>'ingresos','type'=>'delete','base_version'=>1,'data'=>[]];
        $removed=$this->send($delete)->assertJsonPath('record.version',2)->assertJsonPath('record.deleted',true)->json();$this->send($delete)->assertExactJson($removed);
        $this->assertSame('activo',$this->animal->fresh()->estado);$this->assertNull($this->animal->fresh()->ingreso_venta_id);$this->assertNull(Ingreso::withTrashed()->sole()->active_sale_animal_id);
        $this->send($this->op('egresos'))->assertJsonPath('status',201);$this->assertDatabaseCount('egresos',1);
        $this->send($this->op())->assertJsonPath('status',201);$this->assertSame(1,Ingreso::count());
    }
    public function test_receipt_failure_rolls_back_income_animal_occupancy_and_retries_exactly_once(): void
    {
        $op=$this->op(extra:['monto'=>'0.10']);$fail=true;
        DB::listen(function($q)use(&$fail){if($fail&&str_contains(strtolower($q->sql),'insert into')&&str_contains($q->sql,'workspace_sync_receipts')){$fail=false;throw new \RuntimeException('Fallo ficticio antes del commit');}});
        $this->postJson('/api/offline/v1/workspace/operations',['account_id'=>$this->owner->id,'device_uuid'=>(string)Str::uuid(),'operation'=>$op])->assertStatus(500);
        $this->assertDatabaseCount('ingresos',0);$this->assertSame('activo',$this->animal->fresh()->estado);$this->assertSame(1,$this->paddock->fresh()->animales_actuales);
        $result=$this->send($op)->assertJsonPath('record.monto','0.10')->json();$this->send($op)->assertExactJson($result);$this->assertDatabaseCount('ingresos',1);
        $this->send($this->op('egresos',['monto'=>'1.001']))->assertJsonPath('status',422);$this->assertDatabaseCount('egresos',0);
    }
    public function test_concurrent_sales_and_lost_response_replays_have_one_effect_in_postgresql(): void
    {
        if(DB::getDriverName()!=='pgsql')$this->markTestSkipped('Concurrencia real requiere PostgreSQL.');
        $results=$this->concurrent([$this->op(),$this->op()]);$statuses=array_column($results,'status');sort($statuses);$this->assertSame([201,422],$statuses);$this->assertDatabaseCount('ingresos',1);
        $expense=$this->op('egresos');$results=$this->concurrent([$expense,$expense]);$this->assertSame($results[0],$results[1]);$this->assertDatabaseCount('egresos',1);
    }
    private function concurrent(array $operations): array
    {
        $dir=sys_get_temp_dir().'/bovisoft-finance-'.Str::uuid();mkdir($dir);$processes=[];
        try{
            foreach($operations as $i=>$op){$path=$dir.'/job-'.$i;file_put_contents($path,json_encode(['user_id'=>$this->owner->id,'device_uuid'=>(string)Str::uuid(),'operation'=>$op,'workspace'=>true,'barrier'=>$dir.'/start'],JSON_THROW_ON_ERROR));
                $processes[]=proc_open([PHP_BINARY,'-d','extension=pdo_pgsql',base_path('tests/Support/production-sync-worker.php'),$path],[0=>['pipe','r'],1=>['file',$path.'.out','w'],2=>['file',$path.'.err','w']],$pipes,base_path());fclose($pipes[0]);}
            $deadline=microtime(true)+20;while(!file_exists($dir.'/job-0.ready')||!file_exists($dir.'/job-1.ready')){if(microtime(true)>$deadline)$this->fail('No se alcanzó la barrera');usleep(10000);}touch($dir.'/start');
            $results=[];foreach($processes as $i=>$process){$this->assertSame(0,proc_close($process),file_get_contents($dir.'/job-'.$i.'.err'));$results[]=json_decode(file_get_contents($dir.'/job-'.$i.'.out'),true,flags:JSON_THROW_ON_ERROR);}return $results;
        }finally{foreach($processes as $process)if(is_resource($process)){proc_terminate($process);proc_close($process);}foreach(glob($dir.'/*') as $file)unlink($file);rmdir($dir);}
    }
}
