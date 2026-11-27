<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Tests\FincasTestCase;

class PerformancePhase63Test extends FincasTestCase
{
    use RefreshDatabase;

    public function test_representative_volumes(): void
    {
        $this->withoutVite();
        Notification::fake();
        $this->travelTo(\Carbon\Carbon::parse('2026-11-24 12:00:00'));
        $user = User::factory()->create();
        $this->actingAs($user);
        $measurements = [];
        $dataset = new \Tests\Support\PerformanceDataset($user);
        foreach ([100, 500, 2000] as $n) {
            $dataset->grow($n);
            foreach (getenv('BOVISOFT_PERF_FINANCE_ONLY') ? ['/finanzas'] : ['/dashboard', '/finanzas', '/animales', '/api/alertas/recientes'] as $path) {
                for ($sample = 0; $sample < 2; $sample++) {
                    DB::flushQueryLog();
                    DB::enableQueryLog();
                    gc_collect_cycles();
                    memory_reset_peak_usage();
                    $start = hrtime(true);
                    $response = $this->get($path);
                    $ms = (hrtime(true) - $start) / 1e6;
                    $queries = count(DB::getQueryLog());
                    DB::disableQueryLog();
                    $response->assertOk();
                    $measurements[] = ['animals' => $n, 'path' => $path, 'sample' => $sample, 'ms' => round($ms, 2), 'queries' => $queries, 'peak_mib' => round(memory_get_peak_usage(true) / 1048576, 2), 'bytes' => strlen($response->getContent())];
                    if ($path === '/dashboard') {
                        $this->assertEqualsWithDelta($n * 12.35, $response->viewData('produccionMes'), 0.001);
                        $this->assertEqualsWithDelta($n * 12.35, array_sum($response->viewData('chartProduccion')), 0.001);
                        $this->assertCount(5, $response->viewData('alertasDashboard'));
                    }
                    if ($path === '/finanzas') {
                        $this->assertEqualsWithDelta($n / 5 * 100.25, $response->viewData('totalIngresos'), 0.001);
                        $this->assertEqualsWithDelta($n / 5 * 40.10, $response->viewData('totalEgresos'), 0.001);
                        $this->assertEqualsWithDelta($n / 5 * 100.25, array_sum(array_column($response->viewData('flujoCaja'), 'ingresos')), 0.001);
                        $this->assertEqualsWithDelta($n / 5 * 40.10, array_sum(array_column($response->viewData('balancePorFinca'), 'egresos')), 0.001);
                    }
                }
            }
        }
        $file = sys_get_temp_dir().'/bovisoft-performance-'.getenv('BOVISOFT_TEST_ENGINE').'-'.(getenv('BOVISOFT_PERF_LABEL') ?: 'current').'.json';
        file_put_contents($file, json_encode($measurements, JSON_PRETTY_PRINT));
        echo "\nMediciones: $file\n";
    }

    public function test_alert_date_boundaries_and_fresh_calls(): void
    {
        $base = ['record_uuid' => 'ficticio', 'id' => 1, 'tipo' => 'vacunacion', 'estado' => 'completado', 'animal_nombre' => 'Ficticio', 'finca_nombre' => 'Ficticia'];
        foreach ([-1 => 'vencida', 0 => 'hoy', 1 => 'proxima', 7 => 'proxima', 8 => 'pronto', 15 => 'pronto', 16 => null] as $days => $expected) {
            $row = $base + ['proxima_aplicacion' => \Carbon\Carbon::parse('2026-11-24')->addDays($days)->toDateString()];
            $alerts = \App\Services\AlertRules::generate([$row], '2026-11-24');
            $this->assertSame($expected, $alerts[0]['tipo'] ?? null);
        }
        $row = $base + ['proxima_aplicacion' => '2026-11-24'];
        $this->assertSame('hoy', \App\Services\AlertRules::generate([$row], '2026-11-24')[0]['tipo']);
        $this->assertSame('vencida', \App\Services\AlertRules::generate([$row], '2026-11-25')[0]['tipo']);
        $this->assertSame([], \App\Services\AlertRules::generate([array_replace($row,['estado' => 'cancelado'])],'2026-11-24'));
    }
}
