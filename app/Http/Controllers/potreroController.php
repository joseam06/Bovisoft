<?php

namespace App\Http\Controllers;

use App\Models\Potrero;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PotreroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Potrero::where('user_id', Auth::id())
            ->with('finca');

        // Búsqueda
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('codigo', 'like', "%{$search}%");
            });
        }

        // Filtro por estado
        if ($request->filled('estado')) {
            $query->where('estado', $request->estado);
        }

        // Filtro por finca
        if ($request->filled('finca_id')) {
            $query->where('finca_id', $request->finca_id);
        }

        $potreros = $query->latest()->paginate(10);

        // Estadísticas
        $total = Potrero::where('user_id', Auth::id())->count();
        $activos = Potrero::where('user_id', Auth::id())->activos()->count();
        $en_descanso = Potrero::where('user_id', Auth::id())->enDescanso()->count();
        $area_total = Potrero::where('user_id', Auth::id())->sum('area');

        // Fincas para el filtro
        $fincas = Finca::where('user_id', Auth::id())->orderBy('nombre')->get();

        return view('potreros.index', compact(
            'potreros',
            'total',
            'activos',
            'en_descanso',
            'area_total',
            'fincas'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $fincas = Finca::where('user_id', Auth::id())
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();

        if ($fincas->isEmpty()) {
            return redirect()->route('fincas.create')
                ->with('error', 'Primero debes crear una finca para poder registrar potreros');
        }

        $codigo = Potrero::generarCodigo();
        $tipos_pasto = Potrero::getTiposPasto();
        $estados = Potrero::getEstados();

        return view('potreros.create', compact('fincas', 'codigo', 'tipos_pasto', 'estados'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'codigo' => 'required|string|unique:potreros,codigo',
            'nombre' => 'required|string|max:255',
            'area' => 'nullable|numeric|min:0|max:99999.99',
            'tipo_pasto' => 'nullable|string|max:100',
            'capacidad_animales' => 'nullable|integer|min:0|max:9999',
            'dias_descanso' => 'nullable|integer|min:0|max:365',
            'fecha_ultima_rotacion' => 'nullable|date|before_or_equal:today',
            'observaciones' => 'nullable|string',
            'finca_id' => 'required|exists:fincas,id',
            'estado' => 'required|in:activo,en_descanso,en_mantenimiento',
        ], [
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya está en uso',
            'nombre.required' => 'El nombre del potrero es obligatorio',
            'finca_id.required' => 'Debes seleccionar una finca',
            'finca_id.exists' => 'La finca seleccionada no existe',
            'capacidad_animales.integer' => 'La capacidad debe ser un número entero',
            'area.numeric' => 'El área debe ser un número',
            'fecha_ultima_rotacion.before_or_equal' => 'La fecha de rotación no puede ser futura',
        ]);

        // Verificar que la finca pertenezca al usuario
        $finca = Finca::findOrFail($validated['finca_id']);
        if ($finca->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para crear potreros en esta finca');
        }

        // Asignar user_id
        $validated['user_id'] = Auth::id();
        $validated['animales_actuales'] = 0; // Inicia sin animales

        $potrero = Potrero::create($validated);

        return redirect()->route('potreros.index')
            ->with('success', 'Potrero registrado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Potrero $potrero)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver este potrero');
        }

        // Cargar relaciones
        $potrero->load('finca', 'animales');

        return view('potreros.show', compact('potrero'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Potrero $potrero)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar este potrero');
        }

        $fincas = Finca::where('user_id', Auth::id())
            ->orderBy('nombre')
            ->get();

        $tipos_pasto = Potrero::getTiposPasto();
        $estados = Potrero::getEstados();

        return view('potreros.edit', compact('potrero', 'fincas', 'tipos_pasto', 'estados'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Potrero $potrero)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para editar este potrero');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'area' => 'nullable|numeric|min:0|max:99999.99',
            'tipo_pasto' => 'nullable|string|max:100',
            'capacidad_animales' => 'nullable|integer|min:0|max:9999',
            'dias_descanso' => 'nullable|integer|min:0|max:365',
            'fecha_ultima_rotacion' => 'nullable|date|before_or_equal:today',
            'observaciones' => 'nullable|string',
            'finca_id' => 'required|exists:fincas,id',
            'estado' => 'required|in:activo,en_descanso,en_mantenimiento',
        ]);

        // Verificar que la finca pertenezca al usuario
        $finca = Finca::findOrFail($validated['finca_id']);
        if ($finca->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para asignar potreros a esta finca');
        }

        $potrero->update($validated);

        return redirect()->route('potreros.show', $potrero)
            ->with('success', 'Potrero actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Potrero $potrero)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para eliminar este potrero');
        }

        // Verificar si tiene animales
        if ($potrero->animales_actuales > 0) {
            return back()->with('error', 'No puedes eliminar un potrero que tiene animales asignados. Primero reasigna los animales.');
        }

        $potrero->delete();

        return redirect()->route('potreros.index')
            ->with('success', 'Potrero eliminado exitosamente');
    }

    /**
     * Cambiar estado del potrero
     */
    public function cambiarEstado(Potrero $potrero, Request $request)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id()) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'estado' => 'required|in:activo,en_descanso,en_mantenimiento',
        ]);

        $potrero->update(['estado' => $validated['estado']]);

        return response()->json([
            'success' => true,
            'estado' => $potrero->estado,
            'estado_formateado' => $potrero->estado_formateado,
        ]);
    }

    /**
     * Ver potreros de una finca específica
     */
    public function porFinca(Finca $finca)
    {
        // Verificar permisos
        if ($finca->user_id !== Auth::id()) {
            abort(403, 'No tienes permiso para ver los potreros de esta finca');
        }

        $potreros = Potrero::where('finca_id', $finca->id)
            ->where('user_id', Auth::id())
            ->latest()
            ->get();

        return view('potreros.por-finca', compact('finca', 'potreros'));
    }
}