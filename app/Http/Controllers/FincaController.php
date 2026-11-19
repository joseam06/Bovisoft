<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use App\Services\WorkspaceRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class FincaController extends Controller
{
    public function index()
    {
        $fincas = Finca::delUsuario(Auth::id())->orderBy('created_at', 'desc')->get();

        return view('fincas.index', compact('fincas'));
    }

    public function create()
    {
        $codigo = Finca::generarCodigo();

        return view('fincas.create', compact('codigo'));
    }

    public function store(Request $request, WorkspaceRecords $records)
    {
        $records->write($request->user(), 'fincas', 'create', $request->all());

        return redirect()->route('dashboard')->with('success', 'Finca registrada exitosamente');
    }

    public function show(Finca $finca)
    {
        Gate::authorize('view', $finca);

        return view('fincas.show', compact('finca'));
    }

    public function edit(Finca $finca)
    {
        Gate::authorize('update', $finca);

        return view('fincas.edit', compact('finca'));
    }

    public function update(Request $request, Finca $finca, WorkspaceRecords $records)
    {
        Gate::authorize('update', $finca);
        $records->write($request->user(), 'fincas', 'update', $request->all(), $finca);

        return redirect()->route('fincas.show', $finca)->with('success', 'Finca actualizada exitosamente');
    }

    public function destroy(Request $request, Finca $finca, WorkspaceRecords $records)
    {
        Gate::authorize('delete', $finca);
        $records->write($request->user(), 'fincas', 'delete', $request->all(), $finca);

        return redirect()->route('fincas.index')->with('success', 'Finca eliminada exitosamente');
    }

    public function getFincasParaMapa()
    {
        $fincas = Finca::delUsuario(Auth::id())->activas()->conUbicacion()->get()->map(fn ($finca) => [
            'id' => $finca->id, 'nombre' => $finca->nombre, 'codigo' => $finca->codigo,
            'latitud' => (float) $finca->latitud, 'longitud' => (float) $finca->longitud,
            'area' => $finca->area, 'direccion' => $finca->direccion, 'total_animales' => 0,
        ]);

        return response()->json($fincas);
    }

    public function toggleEstado(Request $request, Finca $finca, WorkspaceRecords $records)
    {
        Gate::authorize('toggleEstado', $finca);
        $updated = $records->write($request->user(), 'fincas', 'toggle', $request->only('base_version'), $finca);

        return response()->json(['success' => true, 'activa' => $updated->activa,
            'message' => $updated->activa ? 'Finca activada' : 'Finca desactivada']);
    }
}
