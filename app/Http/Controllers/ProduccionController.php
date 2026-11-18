<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Produccion;
use App\Services\ProduccionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ProduccionController extends Controller
{
    // ─── index ─────────────────────────────────────────────────────────────────

    public function index(Request $request, ProduccionService $service)
    {
        $userId = Auth::id();
        $hoy = Carbon::today();

        // Estadísticas globales
        $totalHoy = Produccion::autorizadasPara($userId)->whereDate('fecha', $hoy)->sum('litros');
        $totalSemana = Produccion::autorizadasPara($userId)
            ->whereBetween('fecha', [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()])
            ->sum('litros');
        $totalMes = Produccion::autorizadasPara($userId)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->sum('litros');
        $totalRegistros = Produccion::autorizadasPara($userId)->count();

        // Top 5 animales del mes
        $topAnimales = Produccion::with('animal')
            ->autorizadasPara($userId)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->selectRaw('animal_id, SUM(litros) as total_litros, COUNT(*) as total_registros')
            ->groupBy('animal_id')
            ->orderByDesc('total_litros')
            ->limit(5)
            ->get();

        // Datos para el gráfico de los últimos 7 días
        $chartDias = [];
        $chartLitros = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = $hoy->copy()->subDays($i);
            $chartDias[] = $dia->format('d/m');
            $chartLitros[] = (float) Produccion::autorizadasPara($userId)
                ->whereDate('fecha', $dia)
                ->sum('litros');
        }

        // Listado filtrable
        $query = Produccion::with(['animal', 'finca'])
            ->autorizadasPara($userId);

        if ($request->filled('finca_id')) {
            $query->where('finca_id', $request->finca_id);
        }
        if ($request->filled('animal_id')) {
            $query->where('animal_id', $request->animal_id);
        }
        if ($request->filled('sesion')) {
            $query->where('sesion', $request->sesion);
        }
        if ($request->filled('calidad')) {
            $query->where('calidad', $request->calidad);
        }
        if ($request->filled('fecha_desde')) {
            $query->whereDate('fecha', '>=', $request->fecha_desde);
        }
        if ($request->filled('fecha_hasta')) {
            $query->whereDate('fecha', '<=', $request->fecha_hasta);
        }

        $registros = $query->orderBy('fecha', 'desc')
            ->paginate(15)
            ->appends($request->query());

        $fincas = Finca::where('user_id', $userId)->orderBy('nombre')->get();
        $animales = $service->animalesElegibles($request->user())
            ->with('finca')
            ->orderBy('nombre')
            ->get();

        $sesiones = Produccion::getSesiones();
        $calidades = Produccion::getCalidades();

        return view('produccion.index', compact(
            'totalHoy', 'totalSemana', 'totalMes', 'totalRegistros',
            'topAnimales', 'registros', 'fincas', 'animales',
            'sesiones', 'calidades', 'chartDias', 'chartLitros'
        ));
    }

    // ─── create ────────────────────────────────────────────────────────────────

    public function create(Request $request, ProduccionService $service)
    {
        $fincas = Finca::where('user_id', Auth::id())
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();

        $animales = $service->animalesElegibles($request->user())
            ->with('finca')
            ->orderBy('nombre')
            ->get();

        $sesiones = Produccion::getSesiones();
        $calidades = Produccion::getCalidades();
        $codigo = Produccion::generarCodigo();

        $animalPresel = $request->filled('animal_id')
            ? $animales->firstWhere('id', $request->integer('animal_id'))
            : null;

        return view('produccion.create', compact(
            'fincas', 'animales', 'sesiones', 'calidades', 'codigo', 'animalPresel'
        ));
    }

    // ─── store ─────────────────────────────────────────────────────────────────

    public function store(Request $request, ProduccionService $service)
    {
        $registro = $service->create($request->user(), $request->all());

        return redirect()->route('produccion.index')
            ->with('success', 'Producción '.$registro->codigo.' registrada exitosamente.');
    }

    // ─── show ──────────────────────────────────────────────────────────────────

    public function show(int $id, ProduccionService $service)
    {
        $registro = $service->findForUser(Auth::user(), $id);

        // Registros del mismo animal en el mismo mes para comparar
        $historialAnimal = Produccion::where('animal_id', $registro->animal_id)
            ->autorizadasPara(Auth::id())
            ->where('id', '!=', $id)
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get();

        return view('produccion.show', compact('registro', 'historialAnimal'));
    }

    // ─── edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id, ProduccionService $service)
    {
        $registro = $service->findForUser(Auth::user(), $id);
        $requiereRevisionFecha = $service->requiereRevisionFecha($registro);

        $sesiones = Produccion::getSesiones();
        $calidades = Produccion::getCalidades();

        return view('produccion.edit', compact(
            'registro', 'requiereRevisionFecha', 'sesiones', 'calidades'
        ));
    }

    // ─── update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id, ProduccionService $service)
    {
        $registro = $service->update($request->user(), $id, $request->all());

        return redirect()->route('produccion.show', $registro->id)
            ->with('success', 'Registro de producción actualizado exitosamente.');
    }

    // ─── destroy ───────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $registro = Produccion::autorizadasPara(Auth::id())->findOrFail($id);
        $registro->delete();

        return redirect()->route('produccion.index')
            ->with('success', 'Registro eliminado exitosamente.');
    }

    // ─── API: datos del gráfico (7 o 30 días) ─────────────────────────────────

    public function apiChart(Request $request)
    {
        $userId = Auth::id();
        $rango = $request->get('rango', '7dias');
        $hoy = Carbon::today();

        $data = [];

        if ($rango === '7dias') {
            for ($i = 6; $i >= 0; $i--) {
                $dia = $hoy->copy()->subDays($i);
                $data[] = [
                    'fecha' => $dia->format('d/m'),
                    'litros' => (float) Produccion::autorizadasPara($userId)
                        ->whereDate('fecha', $dia)
                        ->sum('litros'),
                ];
            }
        } elseif ($rango === '30dias') {
            for ($i = 29; $i >= 0; $i--) {
                $dia = $hoy->copy()->subDays($i);
                $data[] = [
                    'fecha' => $dia->format('d/m'),
                    'litros' => (float) Produccion::autorizadasPara($userId)
                        ->whereDate('fecha', $dia)
                        ->sum('litros'),
                ];
            }
        } elseif ($rango === 'anio') {
            $mesesNombres = [
                1 => 'Ene', 2 => 'Feb', 3 => 'Mar', 4 => 'Abr',
                5 => 'May', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago',
                9 => 'Sep', 10 => 'Oct', 11 => 'Nov', 12 => 'Dic',
            ];
            for ($m = 1; $m <= 12; $m++) {
                $inicio = Carbon::create($hoy->year, $m, 1)->startOfMonth();
                $fin = $inicio->copy()->endOfMonth();
                $data[] = [
                    'fecha' => $mesesNombres[$m],
                    'litros' => (float) Produccion::autorizadasPara($userId)
                        ->whereBetween('fecha', [$inicio, $fin])
                        ->sum('litros'),
                ];
            }
        }

        return response()->json($data);
    }

    // ─── porAnimal ─────────────────────────────────────────────────────────────

    public function porAnimal(int $animalId)
    {
        $animal = Animal::where('user_id', Auth::id())->findOrFail($animalId);

        $registros = Produccion::where('animal_id', $animalId)
            ->autorizadasPara(Auth::id())
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        $totalAnimal = (float) Produccion::where('animal_id', $animalId)->autorizadasPara(Auth::id())->sum('litros');
        $promedioAnimal = (float) Produccion::where('animal_id', $animalId)->autorizadasPara(Auth::id())->avg('litros');
        $totalRegistros = Produccion::where('animal_id', $animalId)->autorizadasPara(Auth::id())->count();

        return view('produccion.por_animal', compact(
            'animal', 'registros', 'totalAnimal', 'promedioAnimal', 'totalRegistros'
        ));
    }
}
