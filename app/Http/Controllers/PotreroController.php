<?php

namespace App\Http\Controllers;

use App\Models\Potrero;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PotreroController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->filled('finca_id')) {
            $request->validate(['finca_id' => 'integer|min:1']);
            $finca = Finca::findOrFail($request->input('finca_id'));
            Gate::authorize('view', $finca);
        }

        $query = Potrero::where('user_id', Auth::id())
            ->whereHas('finca', fn ($query) => $query->delUsuario(Auth::id()))
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
        $base = Potrero::where('user_id', Auth::id())
            ->whereHas('finca', fn ($query) => $query->delUsuario(Auth::id()));
        $total = (clone $base)->count();
        $activos = (clone $base)->activos()->count();
        $en_descanso = (clone $base)->enDescanso()->count();
        $area_total = (clone $base)->sum('area');

        // Fincas para el filtro
        $fincas = Finca::delUsuario(Auth::id())->orderBy('nombre')->get();

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
    public function store(Request $request, \App\Services\WorkspaceRecords $records)
    {
        $records->write($request->user(), 'potreros', 'create', $request->all());
        return redirect()->route('potreros.index')->with('success', 'Potrero registrado exitosamente');
    }

    /**
     * Display the specified resource.
     */
    public function show(Potrero $potrero)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id() || ! Finca::delUsuario(Auth::id())->whereKey($potrero->finca_id)->exists()) {
            abort(403, 'No tienes permiso para ver este potrero');
        }

        // Cargar relaciones
        $potrero->load('finca')->load(['animales' => fn ($q) => $q->where('user_id', Auth::id())]);

        return view('potreros.show', compact('potrero'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Potrero $potrero)
    {
        // Verificar permisos
        if ($potrero->user_id !== Auth::id() || ! Finca::delUsuario(Auth::id())->whereKey($potrero->finca_id)->exists()) {
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
    public function update(Request $request, Potrero $potrero, \App\Services\WorkspaceRecords $records)
    {
        $records->write($request->user(), 'potreros', 'update', $request->all(), $potrero);
        return redirect()->route('potreros.show', $potrero)->with('success', 'Potrero actualizado exitosamente');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Potrero $potrero, \App\Services\WorkspaceRecords $records)
    {
        $records->write($request->user(), 'potreros', 'delete', $request->all(), $potrero);
        return redirect()->route('potreros.index')->with('success', 'Potrero eliminado exitosamente');
    }

    /**
     * Cambiar estado del potrero
     */
    public function cambiarEstado(Potrero $potrero, Request $request, \App\Services\WorkspaceRecords $records)
    {
        $potrero = $records->find($request->user(), 'potreros', $potrero->id);
        $values = $potrero->only(['nombre','area','tipo_pasto','capacidad_animales','dias_descanso','observaciones','finca_id']);
        $values['fecha_ultima_rotacion'] = $potrero->fecha_ultima_rotacion?->format('Y-m-d');
        $records->write($request->user(), 'potreros', 'update', $request->only('estado','base_version') + $values, $potrero);
        return response()->json(['success' => true, 'estado' => $potrero->estado, 'estado_formateado' => $potrero->estado_formateado]);
    }

    /**
     * Ver potreros de una finca específica
     */
    public function porFinca(Request $request, Finca $finca)
    {
        Gate::authorize('view', $finca);
        $request->merge(['finca_id' => $finca->id]);

        return $this->index($request);
    }

    public function disponiblesPorFinca(Finca $finca)
    {
        Gate::authorize('view', $finca);

        $potreros = $finca->potreros()
            ->where('user_id', Auth::id())
            ->activos()
            ->get(['id', 'nombre', 'capacidad_animales', 'animales_actuales'])
            ->map(fn ($potrero) => [
                'id' => $potrero->id,
                'nombre' => $potrero->nombre,
                'disponibilidad' => $potrero->capacidad_animales
                    ? max(0, $potrero->capacidad_animales - $potrero->animales_actuales)
                    : 'Sin límite',
            ]);

        return response()->json($potreros);
    }
}
