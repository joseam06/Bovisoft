<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB,Notification};
use Tests\FincasTestCase;
use Tests\Support\PerformanceDataset;
class PerformanceReports631Test extends FincasTestCase
{
    use RefreshDatabase;
    public function test_one_large_pdf_with_explicit_resource_budget():void
    {
        $kind=getenv('BOVISOFT_PDF_KIND')?:'inventario';$n=(int)(getenv('BOVISOFT_PDF_VOLUME')?:500);
        $this->assertContains($kind,['inventario','salud','vacunacion','tratamientos','historial','financiero']);$this->assertContains($n,[500,2000]);
        $this->withoutVite();Notification::fake();$this->travelTo(\Carbon\Carbon::parse('2026-11-24 12:00:00'));$user=User::factory()->create();$this->actingAs($user);(new PerformanceDataset($user))->grow($n);
        foreach([0=>'vacunacion',1=>'desparasitacion',2=>'tratamiento',3=>'revision'] as $mod=>$type)DB::table('salud')->whereRaw('MOD(id,4) = ?',[$mod])->update(['tipo'=>$type]);
        $file=sys_get_temp_dir().'/bovisoft-pdf631-'.getenv('BOVISOFT_TEST_ENGINE').'-'.$kind.'.json';
        $result=['report'=>$kind,'animals'=>$n,'complete'=>false,'memory_budget_mib'=>384,'time_budget_seconds'=>30];file_put_contents($file,json_encode($result));
        ini_set('memory_limit','384M');set_time_limit(30);DB::flushQueryLog();DB::enableQueryLog();memory_reset_peak_usage();$start=hrtime(true);
        $response=$this->get('/reportes/preview/'.$kind.'?periodo=todos&animal_id=1');$ms=(hrtime(true)-$start)/1e6;$queries=count(DB::getQueryLog());DB::disableQueryLog();
        $response->assertOk();$body=$response->getContent();$this->assertStringStartsWith('%PDF-',$body);
        $result+=['queries'=>$queries,'ms'=>round($ms,2),'bytes'=>strlen($body),'peak_mib'=>round(memory_get_peak_usage(true)/1048576,2)];$result['complete']=true;
        file_put_contents($file,json_encode($result,JSON_PRETTY_PRINT));echo json_encode($result).PHP_EOL;
    }
}
