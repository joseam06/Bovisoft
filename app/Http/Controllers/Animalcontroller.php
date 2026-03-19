<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Finca;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnimalController extends Controller
{
    public function index()
    {
        $animales = Animal::where('user_id', Auth::id())
            ->with('finca')
            ->orderBy('created_at', 'desc')
            ->get();

        return view('fincas.animales.index', compact('animales'));
    }

    public function create()
    {
        $codigo = Animal::generarCodigo();
        $fincas = Finca::where('user_id', Auth::id())
            ->activas()
            ->orderBy('nombre')
            ->get();
        
        $tipos = Animal::getTipos();
        $razas = Animal::getRazas();

        return view('fincas.animales.create', compact('codigo', 'fincas', 'tipos', 'razas'));
    }

    public function store(Request $request)
{
    $validated = $request->validate([
        'codigo' => 'required|string|unique:animales,codigo',
        'nombre' => 'nullable|string|max:255',
        'tipo' => 'required|in:vaca,ternero,toro,novilla',
        'sexo' => 'required|in:macho,hembra',
        'raza' => 'nullable|string|max:100',
        'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
        'peso_actual' => 'nullable|numeric|min:0|max:9999.99',
        'color' => 'nullable|string|max:100',
        'observaciones' => 'nullable|string',
        'finca_id' => 'required|exists:fincas,id',
    ], [
        'codigo.required' => 'El código es obligatorio',
        'codigo.unique' => 'Este código ya está en uso',
        'tipo.required' => 'El tipo de animal es obligatorio',
        'sexo.required' => 'El sexo es obligatorio',
        'finca_id.required' => 'Debes seleccionar una finca',
        'finca_id.exists' => 'La finca seleccionada no existe',
        'fecha_nacimiento.before_or_equal' => 'La fecha de nacimiento no puede ser futura',
        'peso_actual.numeric' => 'El peso debe ser un número',
    ]);

    $validated['user_id'] = Auth::id();
    $validated['estado'] = $validated['estado'] ?? 'activo';

    $animal = Animal::create($validated);

    return redirect()->route('animales.index')
        ->with('success', 'Animal registrado exitosamente');


        // Validar que la finca pertenezca al usuario
        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso para agregar animales a esta finca']);
        }

        $validated['user_id'] = Auth::id();
        $validated['estado'] = 'activo';

        $animal = Animal::create($validated);

        return redirect()->route('animales.index')
            ->with('success', 'Animal registrado exitosamente');
    }

public function show(Animal $animal)
{
    // Cargar relaciones necesarias
    $animal->load('finca', 'user');

    // Verificar que el animal pertenece al usuario autenticado
    if ($animal->user_id !== Auth::id()) {
        abort(403, 'No tienes permiso para ver este animal');
    }

    return view('animales.show', compact('animal'));
}


    public function edit(Animal $animal)
    {
        // Verificar que el animal pertenezca al usuario
        if ($animal->user_id !== Auth::id()) {
            abort(403);
        }

        $fincas = Finca::where('user_id', Auth::id())
            ->activas()
            ->orderBy('nombre')
            ->get();
        
        $tipos = Animal::getTipos();
        $razas = Animal::getRazas();

        return view('fincas.animales.edit', compact('animal', 'fincas', 'tipos', 'razas'));
    }

    public function update(Request $request, Animal $animal)
    {
        // Verificar que el animal pertenezca al usuario
        if ($animal->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'nombre' => 'nullable|string|max:255',
            'tipo' => 'required|in:vaca,ternero,toro,novilla',
            'sexo' => 'required|in:macho,hembra',
            'raza' => 'nullable|string|max:100',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'peso_actual' => 'nullable|numeric|min:0|max:9999.99',
            'color' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'estado' => 'required|string|max:50',
            'finca_id' => 'required|exists:fincas,id',
        ]);

        // Validar que la finca pertenezca al usuario
        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso para mover el animal a esta finca']);
        }

        $animal->update($validated);

        return redirect()->route('animales.show', $animal)
            ->with('success', 'Animal actualizado exitosamente');
    }

    public function destroy(Animal $animal)
    {
        // Verificar que el animal pertenezca al usuario
        if ($animal->user_id !== Auth::id()) {
            abort(403);
        }

        $animal->delete();

        return redirect()->route('animales.index')
            ->with('success', 'Animal eliminado exitosamente');
    }

    // Método para obtener animales por finca (AJAX)
    public function porFinca($fincaId)
    {
        $animales = Animal::where('finca_id', $fincaId)
            ->where('user_id', Auth::id())
            ->activos()
            ->get();

        return response()->json($animales);
    }
}