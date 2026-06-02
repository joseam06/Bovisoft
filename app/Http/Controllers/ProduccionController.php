<?php

namespace App\Http\Controllers;

use App\Models\Produccion;
use App\Models\Animal;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class ProduccionController extends Controller
{
    // ─── Tipos de animal que pueden producir leche ─────────────────────────────
    private const TIPOS_PRODUCTIVOS = ['vaca', 'novilla'];

    // ─── index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $userId = Auth::id();
        $hoy    = Carbon::today();

        // Estadísticas globales
        $totalHoy    = Produccion::where('user_id', $userId)->whereDate('fecha', $hoy)->sum('litros');
        $totalSemana = Produccion::where('user_id', $userId)
            ->whereBetween('fecha', [$hoy->copy()->startOfWeek(), $hoy->copy()->endOfWeek()])
            ->sum('litros');
        $totalMes = Produccion::where('user_id', $userId)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->sum('litros');
        $totalRegistros = Produccion::where('user_id', $userId)->count();

        // Top 5 animales del mes
        $topAnimales = Produccion::with('animal')
            ->where('user_id', $userId)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->selectRaw('animal_id, SUM(litros) as total_litros, COUNT(*) as total_registros')
            ->groupBy('animal_id')
            ->orderByDesc('total_litros')
            ->limit(5)
            ->get();

        // Datos para el gráfico de los últimos 7 días
        $chartDias    = [];
        $chartLitros  = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = $hoy->copy()->subDays($i);
            $chartDias[]   = $dia->format('d/m');
            $chartLitros[] = (float) Produccion::where('user_id', $userId)
                ->whereDate('fecha', $dia)
                ->sum('litros');
        }

        // Listado filtrable
        $query = Produccion::with(['animal', 'finca'])
            ->where('user_id', $userId);

        if ($request->filled('finca_id'))    $query->where('finca_id', $request->finca_id);
        if ($request->filled('animal_id'))   $query->where('animal_id', $request->animal_id);
        if ($request->filled('sesion'))      $query->where('sesion', $request->sesion);
        if ($request->filled('calidad'))     $query->where('calidad', $request->calidad);
        if ($request->filled('fecha_desde')) $query->whereDate('fecha', '>=', $request->fecha_desde);
        if ($request->filled('fecha_hasta')) $query->whereDate('fecha', '<=', $request->fecha_hasta);

        $registros = $query->orderBy('fecha', 'desc')
            ->paginate(15)
            ->appends($request->query());

        $fincas   = Finca::where('user_id', $userId)->orderBy('nombre')->get();
        $animales = Animal::where('user_id', $userId)
            ->where('estado', 'activo')
            ->whereIn('tipo', self::TIPOS_PRODUCTIVOS)
            ->orderBy('nombre')
            ->get();

        $sesiones  = Produccion::getSesiones();
        $calidades = Produccion::getCalidades();

        return view('produccion.index', compact(
            'totalHoy', 'totalSemana', 'totalMes', 'totalRegistros',
            'topAnimales', 'registros', 'fincas', 'animales',
            'sesiones', 'calidades', 'chartDias', 'chartLitros'
        ));
    }

    // ─── create ────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $fincas = Finca::where('user_id', Auth::id())
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();

        $animales = Animal::where('user_id', Auth::id())
            ->where('estado', 'activo')
            ->whereIn('tipo', self::TIPOS_PRODUCTIVOS)
            ->with('finca')
            ->orderBy('nombre')
            ->get();

        $sesiones  = Produccion::getSesiones();
        $calidades = Produccion::getCalidades();
        $codigo    = Produccion::generarCodigo();

        $animalPresel = $request->filled('animal_id')
            ? Animal::where('user_id', Auth::id())->find($request->animal_id)
            : null;

        return view('produccion.create', compact(
            'fincas', 'animales', 'sesiones', 'calidades', 'codigo', 'animalPresel'
        ));
    }

    // ─── store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id'     => 'required|exists:animales,id',
            'finca_id'      => 'required|exists:fincas,id',
            'fecha'         => 'required|date|before_or_equal:today',
            'sesion'        => 'nullable|in:manana,tarde,noche',
            'litros'        => 'required|numeric|min:0.01|max:999.99',
            'calidad'       => 'nullable|in:normal,buena,excelente,rechazada',
            'observaciones' => 'nullable|string|max:1000',
        ], [
            'animal_id.required'        => 'Debes seleccionar un animal.',
            'finca_id.required'         => 'Debes seleccionar una finca.',
            'fecha.required'            => 'La fecha es obligatoria.',
            'fecha.before_or_equal'     => 'La fecha no puede ser futura.',
            'litros.required'           => 'Los litros producidos son obligatorios.',
            'litros.min'                => 'La producción debe ser mayor a 0 litros.',
            'litros.max'                => 'El valor máximo permitido es 999.99 litros.',
        ]);

        // Verificar que el animal pertenece al usuario y es productivo
        $animal = Animal::where('id', $validated['animal_id'])
            ->where('user_id', Auth::id())
            ->whereIn('tipo', self::TIPOS_PRODUCTIVOS)
            ->first();

        if (!$animal) {
            return back()->withErrors(['animal_id' => 'El animal seleccionado no es válido o no produce leche.']);
        }

        // Verificar que la finca pertenece al usuario
        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso sobre esta finca.']);
        }

        $validated['user_id'] = Auth::id();
        $validated['codigo']  = Produccion::generarCodigo();

        Produccion::create($validated);

        return redirect()->route('produccion.index')
            ->with('success', 'Producción ' . $validated['codigo'] . ' registrada exitosamente.');
    }

    // ─── show ──────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $registro = Produccion::with(['animal', 'finca'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        // Registros del mismo animal en el mismo mes para comparar
        $historialAnimal = Produccion::where('animal_id', $registro->animal_id)
            ->where('user_id', Auth::id())
            ->where('id', '!=', $id)
            ->orderBy('fecha', 'desc')
            ->limit(10)
            ->get();

        return view('produccion.show', compact('registro', 'historialAnimal'));
    }

    // ─── edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $registro = Produccion::where('user_id', Auth::id())->findOrFail($id);

        $fincas = Finca::where('user_id', Auth::id())->orderBy('nombre')->get();
        $animales = Animal::where('user_id', Auth::id())
            ->where('estado', 'activo')
            ->whereIn('tipo', self::TIPOS_PRODUCTIVOS)
            ->with('finca')
            ->orderBy('nombre')
            ->get();

        $sesiones  = Produccion::getSesiones();
        $calidades = Produccion::getCalidades();

        return view('produccion.edit', compact(
            'registro', 'fincas', 'animales', 'sesiones', 'calidades'
        ));
    }

    // ─── update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $registro = Produccion::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'animal_id'     => 'required|exists:animales,id',
            'finca_id'      => 'required|exists:fincas,id',
            'fecha'         => 'required|date|before_or_equal:today',
            'sesion'        => 'nullable|in:manana,tarde,noche',
            'litros'        => 'required|numeric|min:0.01|max:999.99',
            'calidad'       => 'nullable|in:normal,buena,excelente,rechazada',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $animal = Animal::where('id', $validated['animal_id'])
            ->where('user_id', Auth::id())
            ->whereIn('tipo', self::TIPOS_PRODUCTIVOS)
            ->first();

        if (!$animal) {
            return back()->withErrors(['animal_id' => 'El animal no es válido o no produce leche.']);
        }

        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso sobre esta finca.']);
        }

        $registro->update($validated);

        return redirect()->route('produccion.show', $registro->id)
            ->with('success', 'Registro de producción actualizado exitosamente.');
    }

    // ─── destroy ───────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $registro = Produccion::where('user_id', Auth::id())->findOrFail($id);
        $registro->delete();

        return redirect()->route('produccion.index')
            ->with('success', 'Registro eliminado exitosamente.');
    }

    // ─── API: datos del gráfico (7 o 30 días) ─────────────────────────────────

    public function apiChart(Request $request)
    {
        $userId = Auth::id();
        $dias   = (int) $request->get('dias', 7);
        $dias   = in_array($dias, [7, 30]) ? $dias : 7;
        $hoy    = Carbon::today();

        $data = [];
        for ($i = $dias - 1; $i >= 0; $i--) {
            $dia    = $hoy->copy()->subDays($i);
            $data[] = [
                'fecha'  => $dia->format('d/m'),
                'litros' => (float) Produccion::where('user_id', $userId)
                    ->whereDate('fecha', $dia)
                    ->sum('litros'),
            ];
        }

        return response()->json($data);
    }

    // ─── porAnimal ─────────────────────────────────────────────────────────────

    public function porAnimal(int $animalId)
    {
        $animal = Animal::where('user_id', Auth::id())->findOrFail($animalId);

        $registros = Produccion::where('animal_id', $animalId)
            ->where('user_id', Auth::id())
            ->orderBy('fecha', 'desc')
            ->paginate(15);

        $totalAnimal   = (float) Produccion::where('animal_id', $animalId)->where('user_id', Auth::id())->sum('litros');
        $promedioAnimal = (float) Produccion::where('animal_id', $animalId)->where('user_id', Auth::id())->avg('litros');
        $totalRegistros = Produccion::where('animal_id', $animalId)->where('user_id', Auth::id())->count();

        return view('produccion.por_animal', compact(
            'animal', 'registros', 'totalAnimal', 'promedioAnimal', 'totalRegistros'
        ));
    }
}