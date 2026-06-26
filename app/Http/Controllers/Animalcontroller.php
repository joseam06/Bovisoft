<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AnimalController extends Controller
{
    public function index()
    {
        $animales = Animal::where('user_id', Auth::id())
            ->with(['finca', 'potrero'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('fincas.animales.index', compact('animales'));
    }

    public function create()
    {
        $codigo = Animal::generarCodigo();
        $fincas = Finca::where('user_id', Auth::id())
            ->where('activa', true)
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
            'numero' => 'nullable|string|max:100',
            'nombre' => 'nullable|string|max:255',
            'tipo' => 'required|in:vaca,ternero,toro,novilla',
            'sexo' => 'required|in:macho,hembra',
            'raza' => 'nullable|string|max:100',
            'fecha_nacimiento' => 'nullable|date|before_or_equal:today',
            'peso_actual' => 'nullable|numeric|min:0|max:9999.99',
            'color' => 'nullable|string|max:100',
            'observaciones' => 'nullable|string',
            'finca_id' => 'required|exists:fincas,id',
            'potrero_id' => 'nullable|exists:potreros,id',
        ]);

        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso para agregar animales a esta finca']);
        }

        if (!empty($validated['potrero_id'])) {
            $potrero = Potrero::where('id', $validated['potrero_id'])
                ->where('finca_id', $validated['finca_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$potrero) {
                return back()->withErrors(['potrero_id' => 'El potrero no pertenece a la finca seleccionada']);
            }

            $potrero->increment('animales_actuales');
        }

        $validated['user_id'] = Auth::id();
        $validated['estado'] = 'activo';

        Animal::create($validated);

        return redirect()->route('animales.index')
            ->with('success', 'Animal registrado exitosamente');
    }

    public function show(int $id)
    {
        $animal = Animal::with(['finca', 'potrero', 'user'])
            ->where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $ultimosSanitarios = $animal->salud()
            ->orderBy('fecha_aplicacion', 'desc')
            ->take(5)
            ->get();

        return view('fincas.animales.show', compact('animal', 'ultimosSanitarios'));
    }

    public function edit(int $id)
    {
        $animal = Animal::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $fincas = Finca::where('user_id', Auth::id())
            ->where('activa', true)
            ->orderBy('nombre')
            ->get();

        $tipos = Animal::getTipos();
        $razas = Animal::getRazas();

        return view('fincas.animales.edit', compact('animal', 'fincas', 'tipos', 'razas'));
    }

    public function update(Request $request, int $id)
    {
        $animal = Animal::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        $validated = $request->validate([
            'numero' => 'nullable|string|max:100',
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
            'potrero_id' => 'nullable|exists:potreros,id',
        ]);

        $finca = Finca::where('id', $validated['finca_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$finca) {
            return back()->withErrors(['finca_id' => 'No tienes permiso para mover el animal a esta finca']);
        }

        if (!empty($validated['potrero_id'])) {
            $potrero = Potrero::where('id', $validated['potrero_id'])
                ->where('finca_id', $validated['finca_id'])
                ->where('user_id', Auth::id())
                ->first();

            if (!$potrero) {
                return back()->withErrors(['potrero_id' => 'El potrero no pertenece a la finca seleccionada']);
            }
        }

        $potreroAnterior = $animal->potrero_id;
        $potreroNuevo = $validated['potrero_id'] ?? null;

        if ($potreroAnterior != $potreroNuevo) {
            if ($potreroAnterior) {
                Potrero::find($potreroAnterior)->decrement('animales_actuales');
            }
            if ($potreroNuevo) {
                Potrero::find($potreroNuevo)->increment('animales_actuales');
            }
        }

        $animal->update($validated);

        return redirect()->route('animales.show', $animal->id)
            ->with('success', 'Animal actualizado exitosamente');
    }

    public function destroy(int $id)
    {
        $animal = Animal::where('id', $id)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        if ($animal->potrero_id) {
            Potrero::find($animal->potrero_id)->decrement('animales_actuales');
        }

        $animal->delete();

        return redirect()->route('animales.index')
            ->with('success', 'Animal eliminado exitosamente');
    }

    public function porFinca($fincaId)
    {
        $animales = Animal::where('finca_id', $fincaId)
            ->where('user_id', Auth::id())
            ->activos()
            ->get();

        return response()->json($animales);
    }
}