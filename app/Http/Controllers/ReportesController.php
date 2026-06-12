<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Egreso;
use App\Models\Finca;
use App\Models\Ingreso;
use App\Models\Salud;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class ReportesController extends Controller
{
    // ─── Panel principal ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $userId = Auth::id();
        $fincas = Finca::where('user_id', $userId)->orderBy('nombre')->get();

        $totalAnimales = Animal::where('user_id', $userId)->count();
        $totalSalud    = Salud::where('user_id', $userId)->count();
        $totalIngresos = Ingreso::where('user_id', $userId)->sum('monto');
        $totalEgresos  = Egreso::where('user_id', $userId)->sum('monto');

        return view('reportes.index', compact(
            'fincas',
            'totalAnimales',
            'totalSalud',
            'totalIngresos',
            'totalEgresos'
        ));
    }

    // ─── Descarga PDF ──────────────────────────────────────────────────────────

    public function descargar(string $tipo, Request $request)
    {
        $datos   = $this->obtenerDatos($tipo, $request);
        $finca   = $this->obtenerFinca($request);
        $periodo = $this->describirPeriodo($request);

        $titulos = [
            'inventario' => 'Reporte de Inventario Animal',
            'salud'      => 'Reporte de Salud Animal',
            'financiero' => 'Reporte Financiero',
        ];

        $titulo = $titulos[$tipo] ?? 'Reporte BoviSoft';

        $pdf = Pdf::loadView("reportes.pdf.{$tipo}", array_merge($datos, [
            'titulo'      => $titulo,
            'fincaNombre' => $finca ? $finca->nombre : 'Todas las fincas',
            'periodo'     => $periodo,
            'generadoEl'  => Carbon::now()->format('d/m/Y H:i'),
        ]))->setPaper('a4', 'portrait');

        $nombreArchivo = $tipo . '_' . Carbon::now()->format('Ymd_His') . '.pdf';

        return $pdf->download($nombreArchivo);
    }

    // ─── Vista previa (inline) ─────────────────────────────────────────────────

    public function preview(string $tipo, Request $request)
    {
        $datos   = $this->obtenerDatos($tipo, $request);
        $finca   = $this->obtenerFinca($request);
        $periodo = $this->describirPeriodo($request);

        $titulos = [
            'inventario' => 'Reporte de Inventario Animal',
            'salud'      => 'Reporte de Salud Animal',
            'financiero' => 'Reporte Financiero',
        ];

        $titulo = $titulos[$tipo] ?? 'Reporte BoviSoft';

        $pdf = Pdf::loadView("reportes.pdf.{$tipo}", array_merge($datos, [
            'titulo'      => $titulo,
            'fincaNombre' => $finca ? $finca->nombre : 'Todas las fincas',
            'periodo'     => $periodo,
            'generadoEl'  => Carbon::now()->format('d/m/Y H:i'),
        ]))->setPaper('a4', 'portrait');

        return $pdf->stream($tipo . '.pdf');
    }

    // ─── Obtener datos según tipo ──────────────────────────────────────────────

    private function obtenerDatos(string $tipo, Request $request): array
    {
        return match ($tipo) {
            'inventario' => $this->datosInventario($request),
            'salud'      => $this->datosSalud($request),
            'financiero' => $this->datosFinanciero($request),
            default      => [],
        };
    }

    // ─── Inventario ────────────────────────────────────────────────────────────
    // El inventario muestra el estado actual del hato, no registros de un periodo.
    // Solo se filtra por finca. El filtro de fecha no aplica aquí.

    private function datosInventario(Request $request): array
    {
        $userId = Auth::id();

        $query = Animal::with(['finca', 'potrero'])
            ->where('user_id', $userId);

        if ($request->filled('finca_id')) {
            $query->where('finca_id', $request->finca_id);
        }

        $animales = $query->orderBy('tipo')->orderBy('nombre')->get();

        $totalAnimales = $animales->count();
        $vacasActivas  = $animales->where('tipo', 'vaca')->where('estado', 'activo')->count();
        $vendidos      = $animales->where('estado', 'vendido')->count();

        // Animales en carencia: consulta global o filtrada por finca
        $enCarenciaQuery = Animal::where('user_id', $userId)
            ->whereHas('salud', function ($q) {
                $q->whereNotNull('fin_carencia')
                  ->where('fin_carencia', '>=', Carbon::today());
            });

        if ($request->filled('finca_id')) {
            $enCarenciaQuery->where('finca_id', $request->finca_id);
        }

        $enCarencia   = $enCarenciaQuery->count();
        $pesoPromedio = $animales->whereNotNull('peso_actual')->avg('peso_actual');
        $conteoTipos  = $animales->groupBy('tipo')
            ->map(fn($g) => $g->count())
            ->toArray();

        return compact(
            'animales',
            'totalAnimales',
            'vacasActivas',
            'vendidos',
            'enCarencia',
            'pesoPromedio',
            'conteoTipos'
        );
    }

    // ─── Salud ─────────────────────────────────────────────────────────────────

    private function datosSalud(Request $request): array
    {
        $userId = Auth::id();

        [$fechaInicio, $fechaFin] = $this->calcularPeriodo($request);

        $query = Salud::with(['animal', 'finca'])
            ->where('user_id', $userId);

        if ($request->filled('finca_id')) {
            $query->where('finca_id', $request->finca_id);
        }

        if ($fechaInicio && $fechaFin) {
            $query->whereBetween('fecha_aplicacion', [
                $fechaInicio->copy()->startOfDay(),
                $fechaFin->copy()->endOfDay(),
            ]);
        }

        $registros      = $query->orderBy('fecha_aplicacion', 'desc')->get();
        $totalRegistros = $registros->count();
        $vacunaciones   = $registros->where('tipo', 'vacunacion')->count();
        $costoTotal     = $registros->sum('costo');

        $enCarenciaQuery = Salud::with(['animal', 'finca'])
            ->where('user_id', $userId)
            ->whereNotNull('fin_carencia')
            ->where('fin_carencia', '>=', Carbon::today());

        if ($request->filled('finca_id')) {
            $enCarenciaQuery->where('finca_id', $request->finca_id);
        }

        $enCarencia      = $enCarenciaQuery->get();
        $enCarenciaCount = $enCarencia->count();

        return compact(
            'registros',
            'totalRegistros',
            'vacunaciones',
            'costoTotal',
            'enCarencia',
            'enCarenciaCount'
        );
    }

    // ─── Financiero ────────────────────────────────────────────────────────────

    private function datosFinanciero(Request $request): array
    {
        $userId = Auth::id();

        [$fechaInicio, $fechaFin] = $this->calcularPeriodo($request);

        $qIngresos = Ingreso::with(['finca', 'animal'])
            ->where('user_id', $userId);

        $qEgresos = Egreso::with(['finca', 'animal'])
            ->where('user_id', $userId);

        if ($request->filled('finca_id')) {
            $qIngresos->where('finca_id', $request->finca_id);
            $qEgresos->where('finca_id', $request->finca_id);
        }

        if ($fechaInicio && $fechaFin) {
            $qIngresos->whereBetween('fecha', [
                $fechaInicio->copy()->startOfDay(),
                $fechaFin->copy()->endOfDay(),
            ]);
            $qEgresos->whereBetween('fecha', [
                $fechaInicio->copy()->startOfDay(),
                $fechaFin->copy()->endOfDay(),
            ]);
        }

        $ingresos      = $qIngresos->orderBy('fecha', 'desc')->get();
        $egresos       = $qEgresos->orderBy('fecha', 'desc')->get();
        $totalIngresos = $ingresos->sum('monto');
        $totalEgresos  = $egresos->sum('monto');
        $balance       = $totalIngresos - $totalEgresos;

        // Costos de salud cruzados desde el módulo Salud
        $qSalud = Salud::where('user_id', $userId)
            ->whereNotNull('costo')
            ->where('costo', '>', 0);

        if ($request->filled('finca_id')) {
            $qSalud->where('finca_id', $request->finca_id);
        }

        if ($fechaInicio && $fechaFin) {
            $qSalud->whereBetween('fecha_aplicacion', [
                $fechaInicio->copy()->startOfDay(),
                $fechaFin->copy()->endOfDay(),
            ]);
        }

        $costosSalud = $qSalud->sum('costo');

        // Máximo para barras proporcionales en PDF
        $maxMonto = max(
            $ingresos->max('monto') ?? 0,
            $egresos->max('monto') ?? 0,
            1
        );

        return compact(
            'ingresos',
            'egresos',
            'totalIngresos',
            'totalEgresos',
            'balance',
            'costosSalud',
            'maxMonto'
        );
    }

    // ─── Helpers ───────────────────────────────────────────────────────────────

    private function calcularPeriodo(Request $request): array
    {
        $periodo = $request->get('periodo', 'mes');

        return match ($periodo) {
            'dia'    => [Carbon::today(), Carbon::today()],
            'semana' => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'mes'    => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'anio'   => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom' => [
                Carbon::parse($request->get('fecha_inicio', Carbon::now()->startOfMonth()->toDateString())),
                Carbon::parse($request->get('fecha_fin', Carbon::now()->endOfMonth()->toDateString())),
            ],
            'todos'  => [null, null],
            default  => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    private function describirPeriodo(Request $request): string
    {
        $periodo = $request->get('periodo', 'mes');

        return match ($periodo) {
            'dia'    => 'Hoy — ' . Carbon::today()->format('d/m/Y'),
            'semana' => 'Esta semana — ' . Carbon::now()->startOfWeek()->format('d/m/Y') . ' al ' . Carbon::now()->endOfWeek()->format('d/m/Y'),
            'mes'    => 'Este mes — ' . Carbon::now()->translatedFormat('F Y'),
            'anio'   => 'Este año — ' . Carbon::now()->year,
            'custom' => ($request->fecha_inicio ?? '?') . ' al ' . ($request->fecha_fin ?? '?'),
            'todos'  => 'Todos los registros',
            default  => 'Este mes',
        };
    }

    private function obtenerFinca(Request $request): ?Finca
    {
        if (!$request->filled('finca_id')) {
            return null;
        }

        return Finca::where('id', $request->finca_id)
            ->where('user_id', Auth::id())
            ->first();
    }
}