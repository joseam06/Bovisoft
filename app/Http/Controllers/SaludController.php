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
    // ─── index (Panel principal de categorías) ─────────────────────────────────

    public function index()
    {
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

        $categorias = [
            [
                'codigo'      => 'preventivo',
                'nombre'      => 'Preventivo',
                'icono'       => 'fa-shield-virus',
                'descripcion' => 'Vacunación, vitaminización, desparasitación y bioseguridad.',
                'items'       => ['Vacunación', 'Vitaminización', 'Desparasitación', 'Bioseguridad'],
                'color_borde' => 'border-blue-400',
                'gradiente'   => 'from-blue-500 to-blue-700',
                'count'       => (clone $base)->where('categoria', 'preventivo')->count(),
                'url'         => route('salud.preventivo'),
            ],
            [
                'codigo'      => 'clinico',
                'nombre'      => 'Clínico',
                'icono'       => 'fa-stethoscope',
                'descripcion' => 'Enfermedades, tratamientos, diagnósticos y cirugías.',
                'items'       => ['Enfermedades', 'Tratamientos', 'Diagnósticos', 'Cirugías'],
                'color_borde' => 'border-purple-400',
                'gradiente'   => 'from-purple-500 to-purple-700',
                'count'       => (clone $base)->where('categoria', 'clinico')->count(),
                'url'         => route('salud.clinico'),
            ],
            [
                'codigo'      => 'reproductivo',
                'nombre'      => 'Reproductivo Sanitario',
                'icono'       => 'fa-venus-mars',
                'descripcion' => 'Sincronización, hormonas, protocolos y preparación IATF.',
                'items'       => ['Sincronización', 'Hormonas', 'Protocolos', 'Preparación IATF'],
                'color_borde' => 'border-pink-400',
                'gradiente'   => 'from-pink-500 to-pink-700',
                'count'       => (clone $base)->where('categoria', 'reproductivo')->count(),
                'url'         => route('salud.reproductivo'),
            ],
            [
                'codigo'      => 'seguimiento',
                'nombre'      => 'Seguimiento',
                'icono'       => 'fa-calendar-check',
                'descripcion' => 'Alertas, próximas aplicaciones, carencias y controles.',
                'items'       => ['Alertas', 'Próximas aplicaciones', 'Carencias', 'Controles'],
                'color_borde' => 'border-yellow-400',
                'gradiente'   => 'from-yellow-500 to-yellow-700',
                'count'       => (clone $base)->where('categoria', 'seguimiento')->count(),
                'url'         => route('salud.seguimiento'),
            ],
        ];

        return view('salud.index', compact('estadisticas', 'categorias'));
    }

    // ─── Método reutilizable para vistas de categoría ──────────────────────────

    private function listadoPorCategoria(string $categoria, string $titulo, string $descripcion)
    {
        $query = Salud::with(['animal', 'finca'])
            ->where('user_id', Auth::id())
            ->where('categoria', $categoria);

        // Filtros (exactamente los mismos que tenía el index original)
        if (request()->filled('buscar')) {
            $b = request('buscar');
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

        if (request()->filled('finca_id'))  $query->where('finca_id',  request('finca_id'));
        if (request()->filled('tipo'))      $query->where('tipo',       request('tipo'));
        if (request()->filled('estado'))    $query->where('estado',     request('estado'));
        if (request()->filled('animal_id')) $query->where('animal_id',  request('animal_id'));

        $registros = $query->orderBy('fecha_aplicacion', 'desc')
                           ->paginate(10)
                           ->appends(request()->query());

        $fincas  = Finca::where('user_id', Auth::id())->orderBy('nombre')->get();
        $tipos   = Salud::getTipos();
        $estados = Salud::getEstados();

        return view('salud.categoria', compact(
            'registros', 'fincas', 'tipos', 'estados', 'categoria', 'titulo', 'descripcion'
        ));
    }

    public function preventivo()
    {
        return $this->listadoPorCategoria(
            'preventivo',
            'Preventivo',
            'Vacunaciones, vitaminización, desparasitación y bioseguridad.'
        );
    }

    public function clinico()
    {
        return $this->listadoPorCategoria(
            'clinico',
            'Clínico',
            'Enfermedades, tratamientos, diagnósticos y cirugías.'
        );
    }

    public function reproductivo()
    {
        return $this->listadoPorCategoria(
            'reproductivo',
            'Reproductivo Sanitario',
            'Sincronización, hormonas, protocolos y preparación IATF.'
        );
    }

    public function seguimiento()
    {
        return $this->listadoPorCategoria(
            'seguimiento',
            'Seguimiento',
            'Alertas, próximas aplicaciones, carencias y controles.'
        );
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
            'tipo'                 => 'required|in:vacunacion,vitaminizacion,desparasitacion,bioseguridad,enfermedad,tratamiento,diagnostico,cirugia,revision,sincronizacion,hormonas,protocolo,preparacion_iatf,alerta,control,carencia,otro',
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

        // La categoría se asigna automáticamente en el modelo (booted)
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
            'tipo'                 => 'required|in:vacunacion,vitaminizacion,desparasitacion,bioseguridad,enfermedad,tratamiento,diagnostico,cirugia,revision,sincronizacion,hormonas,protocolo,preparacion_iatf,alerta,control,carencia,otro',
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

        // La categoría se actualizará automáticamente porque el modelo la recalcula en el evento updating (si quieres ese comportamiento).
        // Si no quieres que se actualice al editar, puedes forzarla manualmente o usar el mismo booted.
        // Actualmente no hay evento updating, por lo que la categoría no cambiará al editar el tipo. Si quieres que sí lo haga,
        // agrega static::updating(...) en el modelo. Aquí lo forzamos manualmente:
        $validated['categoria'] = Salud::getCategoriaPorTipo($validated['tipo']);

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