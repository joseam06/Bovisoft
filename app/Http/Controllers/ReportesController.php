<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use App\Models\Animal;
use App\Models\Salud;
use App\Models\Finca;
use App\Models\Ingreso;
use App\Models\Egreso;

class ReportesController extends Controller
{
    // ─── Helpers ────────────────────────────────────────────────────────────

    private function buildFiltros(Request $request): array
    {
        $periodo   = $request->input('periodo', 'mes');
        $fincaId   = $request->input('finca_id');
        $fechaIni  = null;
        $fechaFin  = null;

        $ahora = Carbon::now();

        switch ($periodo) {
            case 'dia':
                $fechaIni = $ahora->copy()->startOfDay();
                $fechaFin = $ahora->copy()->endOfDay();
                $periodoLabel = 'Hoy (' . $ahora->format('d/m/Y') . ')';
                break;
            case 'semana':
                $fechaIni = $ahora->copy()->startOfWeek();
                $fechaFin = $ahora->copy()->endOfWeek();
                $periodoLabel = 'Esta semana (' . $fechaIni->format('d/m') . ' - ' . $fechaFin->format('d/m/Y') . ')';
                break;
            case 'mes':
                $fechaIni = $ahora->copy()->startOfMonth();
                $fechaFin = $ahora->copy()->endOfMonth();
                $periodoLabel = $ahora->translatedFormat('F Y');
                break;
            case 'anio':
                $fechaIni = $ahora->copy()->startOfYear();
                $fechaFin = $ahora->copy()->endOfYear();
                $periodoLabel = 'Año ' . $ahora->year;
                break;
            case 'custom':
                $fechaIni = $request->input('fecha_inicio') ? Carbon::parse($request->input('fecha_inicio'))->startOfDay() : null;
                $fechaFin = $request->input('fecha_fin') ? Carbon::parse($request->input('fecha_fin'))->endOfDay() : null;
                $periodoLabel = ($fechaIni?->format('d/m/Y') ?? '—') . ' al ' . ($fechaFin?->format('d/m/Y') ?? '—');
                break;
            default: // todos
                $periodoLabel = 'Todos los registros';
                break;
        }

        $userId = Auth::id();
        $finca = null;
        if ($fincaId) {
            $finca = Finca::where('id', $fincaId)->where('user_id', $userId)->first();
        }

        return compact('periodo', 'fincaId', 'fechaIni', 'fechaFin', 'periodoLabel', 'userId', 'finca');
    }

    private function applyDateFilter($query, string $campo, ?Carbon $ini, ?Carbon $fin)
    {
        if ($ini) $query->where($campo, '>=', $ini);
        if ($fin) $query->where($campo, '<=', $fin);
        return $query;
    }

    private function commonPdfData(array $f, string $titulo): array
    {
        $user = Auth::user();
        $fincaNombre = $f['finca'] ? $f['finca']->nombre : 'Todas las fincas';

        return [
            'titulo'        => $titulo,
            'fincaNombre'   => $fincaNombre,
            'propietario'   => $user->name,
            'usuarioNombre' => $user->name,
            'periodo'       => $f['periodoLabel'],
            'generadoEl'    => Carbon::now()->format('d/m/Y H:i'),
        ];
    }

    private function makePdf(string $view, array $data, string $filename, bool $preview)
    {
        $pdf = Pdf::loadView($view, $data)
            ->setPaper('letter', 'portrait')
            ->setOptions([
                'isRemoteEnabled'  => true,
                'isHtml5ParserEnabled' => true,
                'defaultFont'      => 'DejaVu Sans',
                'chroot'           => base_path(),
                'allowed_protocols' => ['file://', 'data:'],
            ]);

        if ($preview) {
            return $pdf->stream($filename);
        }

        return $pdf->download($filename);
    }

    // ─── Index ───────────────────────────────────────────────────────────────

    public function index()
    {
        $userId = Auth::id();

        $fincas        = Finca::where('user_id', $userId)->where('activa', true)->orderBy('nombre')->get();
        $totalAnimales = Animal::where('user_id', $userId)->count();
        $totalSalud    = Salud::where('user_id', $userId)->count();
        $totalIngresos = Ingreso::where('user_id', $userId)->sum('monto');
        $totalEgresos  = Egreso::where('user_id', $userId)->sum('monto');

        $animales = Animal::where('user_id', $userId)->where('estado', 'activo')->orderBy('codigo')->get();

        return view('reportes.index', compact('fincas', 'animales', 'totalAnimales', 'totalSalud', 'totalIngresos', 'totalEgresos'));
    }

    // ─── Inventario ──────────────────────────────────────────────────────────

    public function inventario(Request $request, bool $preview = true)
    {
        $f = $this->buildFiltros($request);

        $query = Animal::with('finca')
            ->where('user_id', $f['userId']);

        if ($f['fincaId']) $query->where('finca_id', $f['fincaId']);

        $animales = $query->orderBy('codigo')->get();

        $totalAnimales = $animales->count();
        $vacasActivas  = $animales->where('tipo', 'vaca')->where('estado', 'activo')->count();
        $vendidos      = $animales->where('estado', 'vendido')->count();
        $enCarencia    = Salud::where('user_id', $f['userId'])
            ->whereNotNull('fin_carencia')
            ->where('fin_carencia', '>=', now()->format('Y-m-d'))
            ->when($f['fincaId'], fn($q) => $q->where('finca_id', $f['fincaId']))
            ->distinct('animal_id')
            ->count('animal_id');

        $conteoTipos  = $animales->groupBy('tipo')->map->count();
        $pesoPromedio = $animales->whereNotNull('peso_actual')->avg('peso_actual');

        $data = array_merge($this->commonPdfData($f, 'Inventario Animal'), compact(
            'animales', 'totalAnimales', 'vacasActivas', 'vendidos',
            'enCarencia', 'conteoTipos', 'pesoPromedio'
        ));

        return $this->makePdf(
            'reportes.pdf.inventario',
            $data,
            'inventario_' . now()->format('Ymd_His') . '.pdf',
            $preview
        );
    }

    public function previewInventario(Request $request)  { return $this->inventario($request, true); }
    public function descargarInventario(Request $request) { return $this->inventario($request, false); }

    // ─── Salud ───────────────────────────────────────────────────────────────

    public function salud(Request $request, bool $preview = true)
    {
        $f = $this->buildFiltros($request);

        $query = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId']);

        if ($f['fincaId']) $query->where('finca_id', $f['fincaId']);
        $this->applyDateFilter($query, 'fecha_aplicacion', $f['fechaIni'], $f['fechaFin']);

        $registros          = $query->orderBy('fecha_aplicacion', 'desc')->get();
        $totalRegistros     = $registros->count();
        $vacunaciones       = $registros->where('tipo', 'vacunacion')->count();
        $desparasitaciones  = $registros->where('tipo', 'desparasitacion')->count();
        $costoTotal         = $registros->sum('costo');
        $enCarenciaCount    = $registros->filter(fn($r) => $r->en_carencia)->count();

        $registrosTratamientos = $registros->where('estado', 'en_tratamiento')->values();
        $tratamientosActivos   = $registrosTratamientos->count();

        $enCarencia = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId'])
            ->whereNotNull('fin_carencia')
            ->where('fin_carencia', '>=', now()->format('Y-m-d'))
            ->when($f['fincaId'], fn($q) => $q->where('finca_id', $f['fincaId']))
            ->get();

        // Alertas vencidas: proxima_aplicacion < hoy
        $alertasVencidas = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId'])
            ->whereNotNull('proxima_aplicacion')
            ->where('proxima_aplicacion', '<', now()->format('Y-m-d'))
            ->when($f['fincaId'], fn($q) => $q->where('finca_id', $f['fincaId']))
            ->orderBy('proxima_aplicacion')
            ->get();

        // Alertas proximas: proxima_aplicacion entre hoy y hoy+7
        $alertasProximas = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId'])
            ->whereNotNull('proxima_aplicacion')
            ->whereBetween('proxima_aplicacion', [
                now()->format('Y-m-d'),
                now()->addDays(7)->format('Y-m-d'),
            ])
            ->when($f['fincaId'], fn($q) => $q->where('finca_id', $f['fincaId']))
            ->orderBy('proxima_aplicacion')
            ->get();

        $data = array_merge($this->commonPdfData($f, 'Salud Animal'), compact(
            'registros', 'totalRegistros', 'vacunaciones', 'desparasitaciones',
            'costoTotal', 'enCarenciaCount', 'enCarencia',
            'tratamientosActivos', 'registrosTratamientos',
            'alertasVencidas', 'alertasProximas'
        ));

        return $this->makePdf(
            'reportes.pdf.salud',
            $data,
            'salud_' . now()->format('Ymd_His') . '.pdf',
            $preview
        );
    }

    public function previewSalud(Request $request)  { return $this->salud($request, true); }
    public function descargarSalud(Request $request) { return $this->salud($request, false); }

    // ─── Vacunacion ──────────────────────────────────────────────────────────

    public function vacunacion(Request $request, bool $preview = true)
    {
        $f = $this->buildFiltros($request);

        $query = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId'])
            ->whereIn('tipo', ['vacunacion', 'desparasitacion']);

        if ($f['fincaId']) $query->where('finca_id', $f['fincaId']);
        $this->applyDateFilter($query, 'fecha_aplicacion', $f['fechaIni'], $f['fechaFin']);

        $registros            = $query->orderBy('fecha_aplicacion', 'desc')->get();
        $totalVacunaciones    = $registros->where('tipo', 'vacunacion')->count();
        $totalDesparasitaciones = $registros->where('tipo', 'desparasitacion')->count();
        $costoTotal           = $registros->sum('costo');

        // Proximas dosis en 60 dias
        $proximasDosis = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId'])
            ->whereIn('tipo', ['vacunacion', 'desparasitacion'])
            ->whereNotNull('proxima_aplicacion')
            ->where('proxima_aplicacion', '<=', now()->addDays(60)->format('Y-m-d'))
            ->when($f['fincaId'], fn($q) => $q->where('finca_id', $f['fincaId']))
            ->orderBy('proxima_aplicacion')
            ->get();

        $proximasSiete = $proximasDosis->filter(
            fn($d) => now()->diffInDays($d->proxima_aplicacion, false) <= 7
                   && now()->diffInDays($d->proxima_aplicacion, false) >= 0
        )->count();

        $data = array_merge($this->commonPdfData($f, 'Reporte de Vacunacion y Desparasitacion'), compact(
            'registros', 'totalVacunaciones', 'totalDesparasitaciones',
            'costoTotal', 'proximasDosis', 'proximasSiete'
        ));

        return $this->makePdf(
            'reportes.pdf.salud_vacunacion',
            $data,
            'vacunacion_' . now()->format('Ymd_His') . '.pdf',
            $preview
        );
    }

    public function previewVacunacion(Request $request)  { return $this->vacunacion($request, true); }
    public function descargarVacunacion(Request $request) { return $this->vacunacion($request, false); }

    // ─── Tratamientos ────────────────────────────────────────────────────────

    public function tratamientos(Request $request, bool $preview = true)
    {
        $f = $this->buildFiltros($request);

        $tiposClinicos = ['tratamiento', 'cirugia', 'revision', 'enfermedad', 'diagnostico'];

        $historialQuery = Salud::with(['animal', 'finca'])
            ->where('user_id', $f['userId'])
            ->whereIn('tipo', $tiposClinicos);

        if ($f['fincaId']) $historialQuery->where('finca_id', $f['fincaId']);
        $this->applyDateFilter($historialQuery, 'fecha_aplicacion', $f['fechaIni'], $f['fechaFin']);

        $historial        = $historialQuery->orderBy('fecha_aplicacion', 'desc')->get();
        $activos          = $historial->where('estado', 'en_tratamiento')->values();
        $totalActivos     = $activos->count();
        $totalCompletados = $historial->where('estado', 'completado')->count();
        $costoTotal       = $historial->sum('costo');
        $enCarenciaCount  = $historial->filter(fn($r) => $r->en_carencia)->count();

        $data = array_merge($this->commonPdfData($f, 'Reporte de Tratamientos'), compact(
            'historial', 'activos', 'totalActivos', 'totalCompletados',
            'costoTotal', 'enCarenciaCount'
        ));

        return $this->makePdf(
            'reportes.pdf.salud_tratamientos',
            $data,
            'tratamientos_' . now()->format('Ymd_His') . '.pdf',
            $preview
        );
    }

    public function previewTratamientos(Request $request)  { return $this->tratamientos($request, true); }
    public function descargarTratamientos(Request $request) { return $this->tratamientos($request, false); }

    // ─── Historial por animal ─────────────────────────────────────────────────

    public function historialAnimal(Request $request, bool $preview = true)
    {
        $f       = $this->buildFiltros($request);
        $userId  = $f['userId'];
        $animalId = $request->input('animal_id');

        $animal = Animal::with('finca')
            ->where('id', $animalId)
            ->where('user_id', $userId)
            ->firstOrFail();

        $registros = Salud::with('finca')
            ->where('user_id', $userId)
            ->where('animal_id', $animal->id)
            ->orderBy('fecha_aplicacion', 'desc')
            ->get();

        $totalRegistros = $registros->count();
        $vacunaciones   = $registros->where('tipo', 'vacunacion')->count();
        $tratamientos   = $registros->whereIn('tipo', ['tratamiento', 'cirugia', 'revision'])->count();
        $costoTotal     = $registros->sum('costo');

        $data = array_merge($this->commonPdfData($f, 'Historial Clinico: ' . ($animal->nombre ?? $animal->codigo)), compact(
            'animal', 'registros', 'totalRegistros', 'vacunaciones', 'tratamientos', 'costoTotal'
        ));

        return $this->makePdf(
            'reportes.pdf.salud_historial_animal',
            $data,
            'historial_' . $animal->codigo . '_' . now()->format('Ymd') . '.pdf',
            $preview
        );
    }

    public function previewHistorialAnimal(Request $request)  { return $this->historialAnimal($request, true); }
    public function descargarHistorialAnimal(Request $request) { return $this->historialAnimal($request, false); }

    // ─── Financiero ──────────────────────────────────────────────────────────

    public function financiero(Request $request, bool $preview = true)
    {
        $f = $this->buildFiltros($request);

        $ingQuery = Ingreso::where('user_id', $f['userId']);
        $egrQuery = Egreso::where('user_id', $f['userId']);

        if ($f['fincaId']) {
            $ingQuery->where('finca_id', $f['fincaId']);
            $egrQuery->where('finca_id', $f['fincaId']);
        }

        $this->applyDateFilter($ingQuery, 'fecha', $f['fechaIni'], $f['fechaFin']);
        $this->applyDateFilter($egrQuery, 'fecha', $f['fechaIni'], $f['fechaFin']);

        $ingresos      = $ingQuery->with('finca')->orderBy('fecha', 'desc')->get();
        $egresos       = $egrQuery->with('finca')->orderBy('fecha', 'desc')->get();
        $totalIngresos = $ingresos->sum('monto');
        $totalEgresos  = $egresos->sum('monto');
        $balance       = $totalIngresos - $totalEgresos;

        $costosSaludQuery = Salud::where('user_id', $f['userId'])->whereNotNull('costo');
        if ($f['fincaId']) $costosSaludQuery->where('finca_id', $f['fincaId']);
        $this->applyDateFilter($costosSaludQuery, 'fecha_aplicacion', $f['fechaIni'], $f['fechaFin']);
        $costosSalud = $costosSaludQuery->sum('costo');

        $maxMonto = max($ingresos->max('monto') ?? 0, $egresos->max('monto') ?? 0);

        $data = array_merge($this->commonPdfData($f, 'Reporte Financiero'), compact(
            'ingresos', 'egresos', 'totalIngresos', 'totalEgresos',
            'balance', 'costosSalud', 'maxMonto'
        ));

        return $this->makePdf(
            'reportes.pdf.financiero',
            $data,
            'financiero_' . now()->format('Ymd_His') . '.pdf',
            $preview
        );
    }

    public function previewFinanciero(Request $request)  { return $this->financiero($request, true); }
    public function descargarFinanciero(Request $request) { return $this->financiero($request, false); }
}