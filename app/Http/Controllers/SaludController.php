<?php

namespace App\Http\Controllers;

use App\Models\Salud;
use App\Models\Animal;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SaludController extends Controller
{
    // ─── index ─────────────────────────────────────────────────────────────────

    public function index(Request $request)
    {
        $query = Salud::with(['animal', 'finca'])
            ->where('user_id', Auth::id());

        if ($request->filled('buscar')) {
            $b = $request->buscar;
            $query->where(function ($q) use ($b) {
                $q->where('codigo', 'like', "%{$b}%")
                  ->orWhere('nombre_producto', 'like', "%{$b}%")
                  ->orWhere('veterinario', 'like', "%{$b}%")
                  ->orWhereHas('animal', fn($a) =>
                      $a->where('nombre', 'like', "%{$b}%")
                        ->orWhere('codigo', 'like', "%{$b}%")
                  );
            });
        }

        if ($request->filled('finca_id'))  $query->where('finca_id',  $request->finca_id);
        if ($request->filled('tipo'))      $query->where('tipo',       $request->tipo);
        if ($request->filled('estado'))    $query->where('estado',     $request->estado);
        if ($request->filled('animal_id')) $query->where('animal_id',  $request->animal_id);

        $registros = $query->orderBy('fecha_aplicacion', 'desc')
                           ->paginate(10)
                           ->withQueryString();

        $base = Salud::where('user_id', Auth::id());
        $estadisticas = [
            'total'          => (clone $base)->count(),
            'vacunaciones'   => (clone $base)->where('tipo', 'vacunacion')->count(),
            'tratamientos'   => (clone $base)->where('tipo', 'tratamiento')->count(),
            'proximas_7dias' => (clone $base)
                ->whereNotNull('proxima_aplicacion')
                ->whereBetween('proxima_aplicacion', [Carbon::today(), Carbon::today()->addDays(7)])
                ->count(),
            'en_carencia'    => (clone $base)
                ->whereNotNull('fin_carencia')
                ->where('fin_carencia', '>=', Carbon::today())
                ->count(),
        ];

        $fincas  = Finca::where('user_id', Auth::id())->orderBy('nombre')->get();
        $tipos   = Salud::getTipos();
        $estados = Salud::getEstados();

        return view('salud.index', compact('registros', 'estadisticas', 'fincas', 'tipos', 'estados'));
    }

    // ─── create ────────────────────────────────────────────────────────────────

    public function create(Request $request)
    {
        $fincas   = Finca::where('user_id', Auth::id())->orderBy('nombre')->get();
        $animales = Animal::where('user_id', Auth::id())
                        ->where('estado', 'activo')
                        ->with('finca')
                        ->orderBy('nombre')
                        ->get();

        $tipos    = Salud::getTipos();
        $estados  = Salud::getEstados();
        $vias     = Salud::getViasAplicacion();
        $unidades = Salud::getUnidadesDosis();
        $codigo   = Salud::generarCodigo();

        $animalPresel = $request->filled('animal_id')
            ? Animal::where('user_id', Auth::id())->find($request->animal_id)
            : null;

        return view('salud.create', compact(
            'fincas', 'animales', 'tipos', 'estados', 'vias', 'unidades', 'codigo', 'animalPresel'
        ));
    }

    // ─── store ─────────────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $validated = $request->validate([
            'animal_id'            => 'required|exists:animales,id',
            'finca_id'             => 'required|exists:fincas,id',
            'tipo'                 => 'required|in:vacunacion,desparasitacion,tratamiento,cirugia,revision,otro',
            'nombre_producto'      => 'required|string|max:255',
            'enfermedad_prevenida' => 'nullable|string|max:255',
            'diagnostico'          => 'nullable|string|max:2000',
            'fecha_aplicacion'     => 'required|date|before_or_equal:today',
            'dosis'                => 'nullable|numeric|min:0',
            'unidad_dosis'         => 'nullable|string|max:20',
            'via_aplicacion'       => 'nullable|string|max:50',
            'lote_medicamento'     => 'nullable|string|max:100',
            'laboratorio'          => 'nullable|string|max:255',
            'veterinario'          => 'nullable|string|max:255',
            'costo'                => 'nullable|numeric|min:0',
            'proxima_aplicacion'   => 'nullable|date|after:fecha_aplicacion',
            'dias_carencia'        => 'nullable|integer|min:0',
            'estado'               => 'required|in:completado,en_tratamiento,pendiente,cancelado',
            'observaciones'        => 'nullable|string|max:1000',
        ]);

        // Verificar que el animal pertenece al usuario
        Animal::where('id', $validated['animal_id'])
              ->where('user_id', Auth::id())
              ->firstOrFail();

        // Calcular fin de carencia
        if (!empty($validated['dias_carencia']) && $validated['dias_carencia'] > 0) {
            $validated['fin_carencia'] = Carbon::parse($validated['fecha_aplicacion'])
                ->addDays((int) $validated['dias_carencia'])
                ->format('Y-m-d');
        }

        $validated['user_id'] = Auth::id();
        $validated['codigo']  = Salud::generarCodigo();

        Salud::create($validated);

        return redirect()
            ->route('salud.index')
            ->with('success', 'Registro de salud ' . $validated['codigo'] . ' creado exitosamente.');
    }

    // ─── show ──────────────────────────────────────────────────────────────────

    public function show(int $id)
    {
        $registro = Salud::with(['animal.finca', 'finca'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        $historialAnimal = Salud::where('animal_id', $registro->animal_id)
            ->where('id', '!=', $id)
            ->orderBy('fecha_aplicacion', 'desc')
            ->limit(10)
            ->get();

        return view('salud.show', compact('registro', 'historialAnimal'));
    }

    // ─── edit ──────────────────────────────────────────────────────────────────

    public function edit(int $id)
    {
        $registro = Salud::where('user_id', Auth::id())->findOrFail($id);
        $fincas   = Finca::where('user_id', Auth::id())->orderBy('nombre')->get();
        $animales = Animal::where('user_id', Auth::id())
                        ->where('estado', 'activo')
                        ->with('finca')
                        ->orderBy('nombre')
                        ->get();
        $tipos    = Salud::getTipos();
        $estados  = Salud::getEstados();
        $vias     = Salud::getViasAplicacion();
        $unidades = Salud::getUnidadesDosis();

        return view('salud.edit', compact(
            'registro', 'fincas', 'animales', 'tipos', 'estados', 'vias', 'unidades'
        ));
    }

    // ─── update ────────────────────────────────────────────────────────────────

    public function update(Request $request, int $id)
    {
        $registro = Salud::where('user_id', Auth::id())->findOrFail($id);

        $validated = $request->validate([
            'animal_id'            => 'required|exists:animales,id',
            'finca_id'             => 'required|exists:fincas,id',
            'tipo'                 => 'required|in:vacunacion,desparasitacion,tratamiento,cirugia,revision,otro',
            'nombre_producto'      => 'required|string|max:255',
            'enfermedad_prevenida' => 'nullable|string|max:255',
            'diagnostico'          => 'nullable|string|max:2000',
            'fecha_aplicacion'     => 'required|date|before_or_equal:today',
            'dosis'                => 'nullable|numeric|min:0',
            'unidad_dosis'         => 'nullable|string|max:20',
            'via_aplicacion'       => 'nullable|string|max:50',
            'lote_medicamento'     => 'nullable|string|max:100',
            'laboratorio'          => 'nullable|string|max:255',
            'veterinario'          => 'nullable|string|max:255',
            'costo'                => 'nullable|numeric|min:0',
            'proxima_aplicacion'   => 'nullable|date|after:fecha_aplicacion',
            'dias_carencia'        => 'nullable|integer|min:0',
            'estado'               => 'required|in:completado,en_tratamiento,pendiente,cancelado',
            'observaciones'        => 'nullable|string|max:1000',
        ]);

        if (!empty($validated['dias_carencia']) && $validated['dias_carencia'] > 0) {
            $validated['fin_carencia'] = Carbon::parse($validated['fecha_aplicacion'])
                ->addDays((int) $validated['dias_carencia'])
                ->format('Y-m-d');
        } else {
            $validated['fin_carencia'] = null;
        }

        $registro->update($validated);

        return redirect()
            ->route('salud.show', $registro)
            ->with('success', 'Registro de salud actualizado exitosamente.');
    }

    // ─── destroy ───────────────────────────────────────────────────────────────

    public function destroy(int $id)
    {
        $registro = Salud::where('user_id', Auth::id())->findOrFail($id);
        $registro->delete();

        return redirect()
            ->route('salud.index')
            ->with('success', 'Registro eliminado exitosamente.');
    }

    // ─── porAnimal ─────────────────────────────────────────────────────────────

    public function porAnimal(int $animalId)
    {
        $animal = Animal::where('user_id', Auth::id())->findOrFail($animalId);

        $registros = Salud::where('animal_id', $animalId)
            ->orderBy('fecha_aplicacion', 'desc')
            ->paginate(15);

        return view('salud.por_animal', compact('animal', 'registros'));
    }
}