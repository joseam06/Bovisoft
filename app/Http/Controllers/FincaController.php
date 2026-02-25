<?php

namespace App\Http\Controllers;

use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FincaController extends Controller
{
    public function index()
    {
        $fincas = Finca::where('user_id', Auth::id())
           // ->with('animales')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('fincas.index', compact('fincas'));
    }

    public function create()
    {
        $codigo = Finca::generarCodigo();
        return view('fincas.create', compact('codigo'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'codigo' => 'required|string|unique:fincas,codigo',
            'area' => 'nullable|numeric|min:0',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
        ], [
            'nombre.required' => 'El nombre de la finca es obligatorio',
            'codigo.required' => 'El código es obligatorio',
            'codigo.unique' => 'Este código ya está en uso',
            'area.numeric' => 'El área debe ser un número',
            'latitud.required' => 'Debes seleccionar la ubicación en el mapa',
            'longitud.required' => 'Debes seleccionar la ubicación en el mapa',
        ]);

        $validated['user_id'] = Auth::id();
        $validated['activa'] = true;

        $finca = Finca::create($validated);

        return redirect()->route('dashboard')
            ->with('success', 'Finca registrada exitosamente');
    }

    public function show(Finca $finca)
    {
       // $finca->load('animales', 'potreros');
        return view('fincas.show', compact('finca'));
    }

    public function edit(Finca $finca)
    {
        return view('fincas.edit', compact('finca'));
    }

    public function update(Request $request, Finca $finca)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'area' => 'nullable|numeric|min:0',
            'descripcion' => 'nullable|string',
            'direccion' => 'nullable|string|max:255',
            'municipio' => 'nullable|string|max:100',
            'departamento' => 'nullable|string|max:100',
            'latitud' => 'required|numeric|between:-90,90',
            'longitud' => 'required|numeric|between:-180,180',
            'activa' => 'boolean',
        ]);

        $finca->update($validated);

        return redirect()->route('fincas.show', $finca)
            ->with('success', 'Finca actualizada exitosamente');
    }

    public function destroy(Finca $finca)
    {
        $finca->delete();
        return redirect()->route('fincas.index')
            ->with('success', 'Finca eliminada exitosamente');
    }

    public function getFincasParaMapa()
    {
        $fincas = Finca::where('user_id', Auth::id())
            ->activas()
            ->conUbicacion()
          //  ->with('animales')
            ->get()
            ->map(function ($finca) {
                return [
                    'id' => $finca->id,
                    'nombre' => $finca->nombre,
                    'codigo' => $finca->codigo,
                    'latitud' => (float) $finca->latitud,
                    'longitud' => (float) $finca->longitud,
                    'area' => $finca->area,
                    'direccion' => $finca->direccion,
                    'total_animales' => 0,
                ];
            });

        return response()->json($fincas);
    }

    public function toggleEstado(Finca $finca)
    {
        $finca->activa = !$finca->activa;
        $finca->save();

        return response()->json([
            'success' => true,
            'activa' => $finca->activa,
            'message' => $finca->activa ? 'Finca activada' : 'Finca desactivada'
        ]);
    }
}