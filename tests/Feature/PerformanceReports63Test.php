<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\FincasTestCase;
use Tests\Support\PerformanceDataset;

class PerformanceReports63Test extends FincasTestCase
{
    use RefreshDatabase;

    public function test_six_existing_reports_have_bounded_queries_and_valid_pdf(): void
    {
        $this->withoutVite();
        Notification::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-24 12:00:00'));
        $user = User::factory()->create();
        $this->actingAs($user);
        (new PerformanceDataset($user))->grow(100);
        $results = [];
        foreach (['inventario', 'salud', 'vacunacion', 'tratamientos', 'historial', 'financiero'] as $kind) {
            DB::flushQueryLog();
            DB::enableQueryLog();
            gc_collect_cycles();
            memory_reset_peak_usage();
            $start = hrtime(true);
            $response = $this->get('/reportes/preview/'.$kind.'?periodo=todos&animal_id=1');
            $ms = (hrtime(true) - $start) / 1e6;
            $queries = count(DB::getQueryLog());
            DB::disableQueryLog();
            $response->assertOk();
            $content = $response->getContent();
            $this->assertStringStartsWith('%PDF-', $content);
            $this->assertLessThan(20, $queries);
            $results[] = ['report' => $kind, 'animals' => 100, 'queries' => $queries, 'ms' => round($ms, 2), 'bytes' => strlen($content), 'peak_mib' => round(memory_get_peak_usage(true) / 1048576, 2)];
        }
        $file = sys_get_temp_dir().'/bovisoft-performance-reports-'.getenv('BOVISOFT_TEST_ENGINE').'.json';
        file_put_contents($file, json_encode($results, JSON_PRETTY_PRINT));
        echo "\nPDF reales: $file\n";
    }
}
