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
use Illuminate\Support\Facades\DB;

class FinanzasController extends Controller
{
    // ─── Panel principal ───

    public function index(Request $request)
    {
        $userId = Auth::id();

        // Período de análisis (por defecto: mes actual)
        $periodo = $request->get('periodo', 'mes');
        [$fechaInicio, $fechaFin] = $this->calcularPeriodo($periodo, $request);

        // Finca filtro (opcional)
        $fincaId = $request->get('finca_id');

        // ── Totales del período ──
        $queryIngresos = Ingreso::autorizadasPara($userId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        $queryEgresos  = Egreso::autorizadasPara($userId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($fincaId) {
            $queryIngresos->where('finca_id', $fincaId);
            $queryEgresos->where('finca_id', $fincaId);
        }

        $totalIngresos = (clone $queryIngresos)->sum('monto');
        $totalEgresos  = (clone $queryEgresos)->sum('monto');
        $balance = \App\Services\Money::decimal(\App\Services\Money::cents($totalIngresos)-\App\Services\Money::cents($totalEgresos));

        // ── Ingresos por tipo ──
        $ingresosPorTipo = (clone $queryIngresos)
            ->select('tipo', DB::raw('SUM(monto) as total'))
            ->groupBy('tipo')
            ->pluck('total', 'tipo');

        // ── Egresos por categoría ──
        $egresosPorCategoria = (clone $queryEgresos)
            ->select('categoria', DB::raw('SUM(monto) as total'))
            ->groupBy('categoria')
            ->pluck('total', 'categoria');

        // ── Últimos movimientos ──
        $ultimosIngresos = Ingreso::autorizadasPara($userId)->with(['finca', 'animal'])
            ->orderByDesc('fecha')
            ->limit(5)
            ->get();

        $ultimosEgresos = Egreso::autorizadasPara($userId)->with(['finca', 'animal'])
            ->orderByDesc('fecha')
            ->limit(5)
            ->get();

        // ── Flujo de caja mensual (últimos 6 meses) para gráfica ──
        $flujoCaja = $this->calcularFlujoCaja($userId, $fincaId);

        // ── Balance por finca ──
        $balancePorFinca = $this->calcularBalancePorFinca($userId, $fechaInicio, $fechaFin);

        // ── Costos de salud (cruzados desde módulo Salud) ──
        $costosSalud = Salud::where('user_id', $userId)
            ->whereBetween('fecha_aplicacion', [$fechaInicio, $fechaFin])
            ->whereNotNull('costo')
            ->where('costo', '>', 0)
            ->sum('costo');

        // ── Fincas para el filtro ──
        $fincas = Finca::where('user_id', $userId)->orderBy('nombre')->get();

        return view('finanzas.index', compact(
            'totalIngresos', 'totalEgresos', 'balance',
            'ingresosPorTipo', 'egresosPorCategoria',
            'ultimosIngresos', 'ultimosEgresos',
            'flujoCaja', 'balancePorFinca', 'costosSalud',
            'fincas', 'fincaId', 'periodo',
            'fechaInicio', 'fechaFin'
        ));
    }

    // ─── INGRESOS ───

    public function createIngreso(Request $request)
    {
        $userId  = Auth::id();
        $fincas  = Finca::where('user_id', $userId)->where('activa', true)->orderBy('nombre')->get();
        $animales = Animal::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))
            ->where('estado', 'activo')
            ->with('finca')
            ->orderBy('nombre')
            ->get();
        $tipos   = Ingreso::getTipos();
        $codigo  = Ingreso::generarCodigo();

        // Pre-selección de animal (desde botón "Vender" en show del animal)
        $animalPresel = $request->filled('animal_id')
            ? Animal::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->find($request->animal_id)
            : null;

        $tipoPresel = $request->get('tipo', 'otro');

        return view('finanzas.ingresos.create', compact(
            'fincas', 'animales', 'tipos', 'codigo', 'animalPresel', 'tipoPresel'
        ));
    }

    public function storeIngreso(Request $request)
    {
        $record = null;
        $result = app(\App\Services\FinancialService::class)->submit($request->user(), 'ingresos', 'create', $request->all(), $record);
        if ($request->expectsJson()) return response()->json($result, $result['status'] >= 400 ? $result['status'] : 200);
        if ($result['status'] >= 400) return back()->withErrors($result['errors'] ?? ['movimiento' => 'No se guardó: '.$result['error']])->withInput($request->except('_token'));
        return redirect()->route('finanzas.index')->with('success', 'Movimiento financiero confirmado.');
    }

    public function showIngreso(int $id)
    {
        $ingreso = Ingreso::autorizadasPara(Auth::id())->with(['finca', 'animal', 'user'])
            ->findOrFail($id);

        return view('finanzas.ingresos.show', compact('ingreso'));
    }

    public function destroyIngreso(Request $request, int $id)
    {
        $record = app(\App\Services\WorkspaceRecords::class)->find($request->user(), 'ingresos', $id);
        $result = app(\App\Services\FinancialService::class)->submit($request->user(), 'ingresos', 'delete', $request->all(), $record);
        if ($request->expectsJson()) return response()->json($result, $result['status'] >= 400 ? $result['status'] : 200);
        if ($result['status'] >= 400) return back()->withErrors($result['errors'] ?? ['movimiento' => 'No se guardó: '.$result['error']])->withInput($request->except('_token'));
        return redirect()->route('finanzas.index')->with('success', 'Movimiento financiero confirmado.');
    }

    // ─── EGRESOS ───

    public function createEgreso(Request $request)
    {
        $userId    = Auth::id();
        $fincas    = Finca::where('user_id', $userId)->where('activa', true)->orderBy('nombre')->get();
        $animales  = Animal::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))
            ->where('estado', 'activo')
            ->with('finca')
            ->orderBy('nombre')
            ->get();
        $categorias = Egreso::getCategorias();
        $codigo     = Egreso::generarCodigo();

        // Pre-llenar con datos de salud si viene desde el módulo salud
        $saludPresel = $request->filled('salud_id')
            ? Salud::where('user_id', $userId)->find($request->salud_id)
            : null;

        return view('finanzas.egresos.create', compact(
            'fincas', 'animales', 'categorias', 'codigo', 'saludPresel'
        ));
    }

    public function storeEgreso(Request $request)
    {
        $record = null;
        $result = app(\App\Services\FinancialService::class)->submit($request->user(), 'egresos', 'create', $request->all(), $record);
        if ($request->expectsJson()) return response()->json($result, $result['status'] >= 400 ? $result['status'] : 200);
        if ($result['status'] >= 400) return back()->withErrors($result['errors'] ?? ['movimiento' => 'No se guardó: '.$result['error']])->withInput($request->except('_token'));
        return redirect()->route('finanzas.index')->with('success', 'Movimiento financiero confirmado.');
    }

    public function showEgreso(int $id)
    {
        $egreso = Egreso::autorizadasPara(Auth::id())->with(['finca', 'animal', 'salud', 'user'])
            ->findOrFail($id);

        return view('finanzas.egresos.show', compact('egreso'));
    }

    public function destroyEgreso(Request $request, int $id)
    {
        $record = app(\App\Services\WorkspaceRecords::class)->find($request->user(), 'egresos', $id);
        $result = app(\App\Services\FinancialService::class)->submit($request->user(), 'egresos', 'delete', $request->all(), $record);
        if ($request->expectsJson()) return response()->json($result, $result['status'] >= 400 ? $result['status'] : 200);
        if ($result['status'] >= 400) return back()->withErrors($result['errors'] ?? ['movimiento' => 'No se guardó: '.$result['error']])->withInput($request->except('_token'));
        return redirect()->route('finanzas.index')->with('success', 'Movimiento financiero confirmado.');
    }

    // ─── Helpers privados ───

    private function calcularPeriodo(string $periodo, Request $request): array
    {
        return match ($periodo) {
            'semana'  => [Carbon::now()->startOfWeek(), Carbon::now()->endOfWeek()],
            'mes'     => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
            'anio'    => [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()],
            'custom'  => [
                Carbon::parse($request->get('fecha_inicio', Carbon::now()->startOfMonth())),
                Carbon::parse($request->get('fecha_fin',   Carbon::now()->endOfMonth())),
            ],
            default   => [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()],
        };
    }

    private function calcularFlujoCaja(int $userId, ?int $fincaId): array
    {
        $mesesNombres = [
            1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
            5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
            9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
        ];

        $meses = [];
        $ranges = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha = Carbon::now()->subMonths($i);
            $inicio = $fecha->copy()->startOfMonth();
            $fin = $fecha->copy()->endOfMonth();

            $ranges[] = [$inicio, $fin];
            $meses[] = [
                'mes' => $mesesNombres[$fecha->month].' '.$fecha->year,
                'ingresos' => 0.0,
                'egresos' => 0.0,
            ];
        }

        foreach (['ingresos' => Ingreso::class, 'egresos' => Egreso::class] as $field => $model) {
            $query = $model::autorizadasPara($userId);
            if ($fincaId) {
                $query->where('finca_id', $fincaId);
            }
            foreach ($ranges as $index => [$start, $end]) {
                $query->selectRaw('COALESCE(SUM(CASE WHEN fecha BETWEEN ? AND ? THEN monto ELSE 0 END), 0) AS m'.$index, [$start, $end]);
            }
            $totals = $query->toBase()->first();
            foreach ($meses as $index => &$month) {
                $month[$field] = (float) $totals->{'m'.$index};
            }
            unset($month);
        }

        return $meses;
    }

    private function calcularBalancePorFinca(int $userId, Carbon $inicio, Carbon $fin): array
    {
        $fincas = Finca::where('user_id', $userId)->get();
        $ingresos = Ingreso::autorizadasPara($userId)->whereBetween('fecha', [$inicio, $fin])->selectRaw('finca_id, SUM(monto) AS total')->groupBy('finca_id')->toBase()->pluck('total', 'finca_id');
        $egresos = Egreso::autorizadasPara($userId)->whereBetween('fecha', [$inicio, $fin])->selectRaw('finca_id, SUM(monto) AS total')->groupBy('finca_id')->toBase()->pluck('total', 'finca_id');
        $resultado = [];

        foreach ($fincas as $finca) {
            $ing = $ingresos[$finca->id] ?? 0;
            $egr = $egresos[$finca->id] ?? 0;

            $resultado[] = [
                'finca' => $finca->nombre,
                'ingresos' => $ing,
                'egresos' => $egr,
                'balance' => \App\Services\Money::decimal(\App\Services\Money::cents($ing) - \App\Services\Money::cents($egr)),
            ];
        }

        return $resultado;
    }
}
