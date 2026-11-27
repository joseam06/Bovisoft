<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Finca;
use App\Services\WorkspaceRecords;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AnimalController extends Controller
{
    public function index()
    {
        $animales = $this->authorized()->with(['finca', 'potrero'])->orderBy('created_at', 'desc')->get();

        return view('fincas.animales.index', compact('animales'));
    }

    private function authorized()
    {
        return Animal::where('user_id', Auth::id())->whereHas('finca', fn ($q) => $q->delUsuario(Auth::id()));
    }

    public function create()
    {
        $codigo = Animal::generarCodigo();
        $fincas = Finca::delUsuario(Auth::id())->activas()->orderBy('nombre')->get();
        $tipos = Animal::getTipos();
        $razas = Animal::getRazas();

        return view('fincas.animales.create', compact('codigo', 'fincas', 'tipos', 'razas'));
    }

    public function store(Request $request, WorkspaceRecords $records)
    {
        $records->write($request->user(), 'animales', 'create', $request->except('estado'));

        return redirect()->route('animales.index')->with('success', 'Animal registrado exitosamente');
    }

    public function show(int $id)
    {
        $animal = $this->authorized()->with(['finca', 'potrero', 'user'])->findOrFail($id);
        $ultimosSanitarios = $animal->salud()->where('user_id', Auth::id())->orderBy('fecha_aplicacion', 'desc')->take(5)->get();

        return view('fincas.animales.show', compact('animal', 'ultimosSanitarios'));
    }

    public function edit(int $id)
    {
        $animal = $this->authorized()->findOrFail($id);
        $fincas = Finca::delUsuario(Auth::id())->where(fn ($q) => $q->activas()->orWhere('id', $animal->finca_id))->orderBy('nombre')->get();
        $tipos = Animal::getTipos();
        $razas = Animal::getRazas();

        return view('fincas.animales.edit', compact('animal', 'fincas', 'tipos', 'razas'));
    }

    public function update(Request $request, int $id, WorkspaceRecords $records)
    {
        $animal = $this->authorized()->findOrFail($id);
        $records->write($request->user(), 'animales', 'update', $request->all(), $animal);

        return redirect()->route('animales.show', $animal->id)->with('success', 'Animal actualizado exitosamente');
    }

    public function destroy(Request $request, int $id, WorkspaceRecords $records)
    {
        $animal = $this->authorized()->findOrFail($id);
        $records->write($request->user(), 'animales', 'delete', $request->all(), $animal);

        return redirect()->route('animales.index')->with('success', 'Animal eliminado exitosamente');
    }

    public function porFinca(Finca $finca)
    {
        Gate::authorize('view', $finca);

        return response()->json($this->authorized()->where('finca_id', $finca->id)->activos()->get());
    }
}
