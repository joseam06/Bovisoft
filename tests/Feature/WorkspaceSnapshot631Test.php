<?php
namespace Tests\Feature;
use App\Models\{User,Animal,Finca,Salud,Potrero,Ingreso,Egreso};
use App\Services\{WorkspaceSync,WorkspaceJournal};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Notification};
use Tests\FincasTestCase;
use Tests\Support\PerformanceDataset;
class WorkspaceSnapshot631Test extends FincasTestCase
{
    use RefreshDatabase;
    public function test_legacy_pages_remain_readable_and_incomplete_chunks_are_rejected():void
    {
        Notification::fake();$user=User::factory()->create();(new PerformanceDataset($user))->grow(100);$sync=app(WorkspaceSync::class);
        $legacy=$sync->snapshot($user);$this->assertArrayHasKey('records',$legacy);$this->assertCount(100,$legacy['records']);
        $catalog=(new \ReflectionMethod(WorkspaceSync::class,'catalog'))->invoke($sync,$user);
        $id=(string)\Illuminate\Support\Str::uuid();
        DB::table('workspace_sync_snapshots')->insert(['id'=>$id,'user_id'=>$user->id,'sequence'=>0,'payload'=>json_encode($catalog),'expires_at'=>now()->addDay()]);
        $this->assertSame($legacy['records'],$sync->page($user,$id,1)['records']);
        $chunk=$sync->snapshot($user,true);DB::table('workspace_snapshot_chunks')->where('snapshot_id',$chunk['snapshot_uuid'])->where('part',1)->update(['payload'=>base64_encode('corrupted')]);
        try{$sync->page($user,$chunk['snapshot_uuid'],1);$this->fail('Corrupted snapshot accepted');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(409,$e->getStatusCode());}
        $chunk=$sync->snapshot($user,true);DB::table('workspace_snapshot_chunks')->where('snapshot_id',$chunk['snapshot_uuid'])->where('part',1)->delete();
        try{$sync->page($user,$chunk['snapshot_uuid'],1);$this->fail('Incomplete snapshot accepted');}catch(\Symfony\Component\HttpKernel\Exception\HttpException $e){$this->assertSame(409,$e->getStatusCode());}
    }
    public function test_bulk_serialization_matches_existing_contract_and_large_snapshot_is_bounded():void
    {
        Notification::fake();$this->travelTo(\Carbon\Carbon::parse('2026-11-24 12:00:00'));$user=User::factory()->create();$dataset=new PerformanceDataset($user);$sync=app(WorkspaceSync::class);$metrics=[];
        foreach([500,2000] as $n){
            $dataset->grow($n);
            foreach([Finca::class,Animal::class,Potrero::class,Salud::class,Ingreso::class,Egreso::class] as $class){$rows=$class::limit(3)->get();$this->assertSame($rows->map(fn($r)=>WorkspaceJournal::serialize($r))->all(),WorkspaceJournal::serializeMany($rows));}
            $catalogMethod=new \ReflectionMethod(WorkspaceSync::class,'catalog');DB::flushQueryLog();DB::enableQueryLog();$start=hrtime(true);$catalog=$catalogMethod->invoke($sync,$user);$queries=count(DB::getQueryLog());DB::disableQueryLog();$catalogMs=(hrtime(true)-$start)/1e6;
            $this->assertLessThan(100,$queries);$json=json_encode($catalog,JSON_THROW_ON_ERROR);$start=hrtime(true);$page=$sync->snapshot($user,true);$id=$page['snapshot_uuid'];$bytes='';$max=0;$requests=0;
            do{
                $this->assertSame($user->id,$page['account_id']);$this->assertSame('catalog-chunks-v1',$page['format']);$max=max($max,strlen(json_encode($page)));$this->assertLessThan(256*1024,strlen(json_encode($page)));$requests++;
                $bytes.=base64_decode($page['chunk'],true);if($page['complete'])break;$this->assertNull($page['cursor']);$page=$sync->page($user,$id,$page['next_page']);
            }while(true);
            $this->assertSame($json,$bytes);$this->assertSame(hash('sha256',$bytes),$page['sha256']);$this->assertNotEmpty($page['cursor']);
            $metrics[]=['animals'=>$n,'records'=>count($catalog['records']),'catalog_queries'=>$queries,'catalog_ms'=>round($catalogMs,2),'catalog_bytes'=>strlen($bytes),'max_http_bytes'=>$max,'pages'=>$requests,'snapshot_all_pages_ms'=>round((hrtime(true)-$start)/1e6,2),'peak_mib'=>round(memory_get_peak_usage(true)/1048576,2)];
        }
        file_put_contents(sys_get_temp_dir().'/bovisoft-snapshot631-'.getenv('BOVISOFT_TEST_ENGINE').'.json',json_encode($metrics,JSON_PRETTY_PRINT));echo json_encode($metrics).PHP_EOL;
    }
}
