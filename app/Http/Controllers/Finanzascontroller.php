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
    // ─── Panel principal ───────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $userId = Auth::id();

        // Período de análisis (por defecto: mes actual)
        $periodo = $request->get('periodo', 'mes');
        [$fechaInicio, $fechaFin] = $this->calcularPeriodo($periodo, $request);

        // Finca filtro (opcional)
        $fincaId = $request->get('finca_id');

        // ── Totales del período ──
        $queryIngresos = Ingreso::where('user_id', $userId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        $queryEgresos  = Egreso::where('user_id', $userId)
            ->whereBetween('fecha', [$fechaInicio, $fechaFin]);

        if ($fincaId) {
            $queryIngresos->where('finca_id', $fincaId);
            $queryEgresos->where('finca_id', $fincaId);
        }

        $totalIngresos = (clone $queryIngresos)->sum('monto');
        $totalEgresos  = (clone $queryEgresos)->sum('monto');
        $balance       = $totalIngresos - $totalEgresos;

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
        $ultimosIngresos = Ingreso::with(['finca', 'animal'])
            ->where('user_id', $userId)
            ->orderByDesc('fecha')
            ->limit(5)
            ->get();

        $ultimosEgresos = Egreso::with(['finca', 'animal'])
            ->where('user_id', $userId)
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

    // ─── INGRESOS ──────────────────────────────────────────────────────────────

    public function createIngreso(Request $request)
    {
        $userId  = Auth::id();
        $fincas  = Finca::where('user_id', $userId)->where('activa', true)->orderBy('nombre')->get();
        $animales = Animal::where('user_id', $userId)
            ->where('estado', 'activo')
            ->with('finca')
            ->orderBy('nombre')
            ->get();
        $tipos   = Ingreso::getTipos();
        $codigo  = Ingreso::generarCodigo();

        // Pre-selección de animal (desde botón "Vender" en show del animal)
        $animalPresel = $request->filled('animal_id')
            ? Animal::where('user_id', $userId)->find($request->animal_id)
            : null;

        $tipoPresel = $request->get('tipo', 'otro');

        return view('finanzas.ingresos.create', compact(
            'fincas', 'animales', 'tipos', 'codigo', 'animalPresel', 'tipoPresel'
        ));
    }

    public function storeIngreso(Request $request)
    {
        $validated = $request->validate([
            'finca_id'            => 'required|exists:fincas,id',
            'animal_id'           => 'nullable|exists:animales,id',
            'tipo'                => 'required|in:venta_animal,venta_leche,subsidio,arrendamiento,otro',
            'monto'               => 'required|numeric|min:0',
            'fecha'               => 'required|date|before_or_equal:today',
            'descripcion'         => 'nullable|string|max:500',
            'comprador_nombre'    => 'nullable|string|max:255',
            'comprador_telefono'  => 'nullable|string|max:50',
            'comprador_documento' => 'nullable|string|max:50',
            'observaciones'       => 'nullable|string|max:1000',
        ], [
            'finca_id.required' => 'Debes seleccionar una finca.',
            'tipo.required'     => 'Selecciona el tipo de ingreso.',
            'monto.required'    => 'El monto es obligatorio.',
            'monto.min'         => 'El monto no puede ser negativo.',
            'fecha.required'    => 'La fecha es obligatoria.',
        ]);

        // Verificar propiedad de la finca
        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso sobre esta finca.']);
        }

        $validated['user_id'] = Auth::id();
        $validated['codigo']  = Ingreso::generarCodigo();

        // ── Si es venta de animal, actualizar su estado ──
        if ($validated['tipo'] === 'venta_animal' && !empty($validated['animal_id'])) {
            $animal = Animal::where('id', $validated['animal_id'])
                ->where('user_id', Auth::id())
                ->first();

            if ($animal) {
                // Crear el ingreso primero para obtener su ID
                $ingreso = Ingreso::create($validated);

                // Actualizar animal: estado vendido + datos de venta
                $animal->update([
                    'estado'           => 'vendido',
                    'fecha_venta'      => $validated['fecha'],
                    'precio_venta'     => $validated['monto'],
                    'ingreso_venta_id' => $ingreso->id,
                    // Si tenía potrero, quitarlo y decrementar contador
                    'potrero_id'       => null,
                ]);

                // Decrementar contador de potrero si tenía uno
                if ($animal->potrero_id) {
                    \App\Models\Potrero::find($animal->potrero_id)?->decrement('animales_actuales');
                }

                return redirect()->route('finanzas.index')
                    ->with('success', 'Venta registrada. ' . ($animal->nombre ?? $animal->codigo) . ' ha sido marcado como vendido.');
            }
        }

        Ingreso::create($validated);

        return redirect()->route('finanzas.index')
            ->with('success', 'Ingreso ' . $validated['codigo'] . ' registrado exitosamente.');
    }

    public function showIngreso(int $id)
    {
        $ingreso = Ingreso::with(['finca', 'animal', 'user'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('finanzas.ingresos.show', compact('ingreso'));
    }

    public function destroyIngreso(int $id)
    {
        $ingreso = Ingreso::where('user_id', Auth::id())->findOrFail($id);

        // Si la eliminación involucra un animal vendido, revertir el estado
        if ($ingreso->tipo === 'venta_animal' && $ingreso->animal_id) {
            $animal = Animal::find($ingreso->animal_id);
            if ($animal && $animal->ingreso_venta_id === $ingreso->id) {
                $animal->update([
                    'estado'           => 'activo',
                    'fecha_venta'      => null,
                    'precio_venta'     => null,
                    'ingreso_venta_id' => null,
                ]);
            }
        }

        $ingreso->delete();

        return redirect()->route('finanzas.index')
            ->with('success', 'Ingreso eliminado exitosamente.');
    }

    // ─── EGRESOS ───────────────────────────────────────────────────────────────

    public function createEgreso(Request $request)
    {
        $userId    = Auth::id();
        $fincas    = Finca::where('user_id', $userId)->where('activa', true)->orderBy('nombre')->get();
        $animales  = Animal::where('user_id', $userId)
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
        $validated = $request->validate([
            'finca_id'     => 'required|exists:fincas,id',
            'animal_id'    => 'nullable|exists:animales,id',
            'categoria'    => 'required|in:salud_animal,compra_animal,insumos_alimentacion,mano_obra,mantenimiento,otro',
            'monto'        => 'required|numeric|min:0',
            'fecha'        => 'required|date|before_or_equal:today',
            'descripcion'  => 'nullable|string|max:500',
            'observaciones'=> 'nullable|string|max:1000',
            'salud_id'     => 'nullable|exists:salud,id',
        ], [
            'finca_id.required'  => 'Debes seleccionar una finca.',
            'categoria.required' => 'Selecciona la categoría del gasto.',
            'monto.required'     => 'El monto es obligatorio.',
            'monto.min'          => 'El monto no puede ser negativo.',
            'fecha.required'     => 'La fecha es obligatoria.',
        ]);

        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso sobre esta finca.']);
        }

        $validated['user_id'] = Auth::id();
        $validated['codigo']  = Egreso::generarCodigo();

        Egreso::create($validated);

        return redirect()->route('finanzas.index')
            ->with('success', 'Egreso ' . $validated['codigo'] . ' registrado exitosamente.');
    }

    public function showEgreso(int $id)
    {
        $egreso = Egreso::with(['finca', 'animal', 'salud', 'user'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return view('finanzas.egresos.show', compact('egreso'));
    }

    public function destroyEgreso(int $id)
    {
        $egreso = Egreso::where('user_id', Auth::id())->findOrFail($id);
        $egreso->delete();

        return redirect()->route('finanzas.index')
            ->with('success', 'Egreso eliminado exitosamente.');
    }

    // ─── Helpers privados ──────────────────────────────────────────────────────

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
        $meses = [];
        for ($i = 5; $i >= 0; $i--) {
            $fecha    = Carbon::now()->subMonths($i);
            $inicio   = $fecha->copy()->startOfMonth();
            $fin      = $fecha->copy()->endOfMonth();

            $qIng = Ingreso::where('user_id', $userId)->whereBetween('fecha', [$inicio, $fin]);
            $qEgr = Egreso::where('user_id', $userId)->whereBetween('fecha', [$inicio, $fin]);

            if ($fincaId) {
                $qIng->where('finca_id', $fincaId);
                $qEgr->where('finca_id', $fincaId);
            }

            $meses[] = [
                'mes'       => $fecha->translatedFormat('M Y'),
                'ingresos'  => (float) $qIng->sum('monto'),
                'egresos'   => (float) $qEgr->sum('monto'),
            ];
        }
        return $meses;
    }

    private function calcularBalancePorFinca(int $userId, Carbon $inicio, Carbon $fin): array
    {
        $fincas = Finca::where('user_id', $userId)->get();
        $resultado = [];

        foreach ($fincas as $finca) {
            $ing = Ingreso::where('user_id', $userId)
                ->where('finca_id', $finca->id)
                ->whereBetween('fecha', [$inicio, $fin])
                ->sum('monto');

            $egr = Egreso::where('user_id', $userId)
                ->where('finca_id', $finca->id)
                ->whereBetween('fecha', [$inicio, $fin])
                ->sum('monto');

            $resultado[] = [
                'finca'     => $finca->nombre,
                'ingresos'  => (float) $ing,
                'egresos'   => (float) $egr,
                'balance'   => (float) ($ing - $egr),
            ];
        }

        return $resultado;
    }
}