<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use Illuminate\Http\Request;

class OfflineViewController extends Controller
{
    // Render the SAME Blade views used online. Prototype values are replaced
    // with authorized IndexedDB data; no second set of module templates exists.
    public function __invoke(Request $request)
    {
        abort_unless(config('offline.workspace.enabled'), 503);
        $farm = (new Finca)->forceFill(['id' => 0, 'user_id' => $request->user()->id,
            'nombre' => 'Nombre', 'codigo' => 'Código', 'activa' => true, 'version' => 1,
            'area' => 1, 'descripcion' => 'Descripción', 'direccion' => 'Dirección',
            'municipio' => 'Municipio', 'departamento' => 'Departamento',
            'latitud' => 1, 'longitud' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $animal = (new Animal)->forceFill(['id' => 0, 'user_id' => $request->user()->id,
            'finca_id' => 0, 'nombre' => 'Nombre', 'codigo' => 'Código', 'numero' => 'Número',
            'tipo' => 'vaca', 'sexo' => 'hembra', 'estado' => 'activo', 'version' => 1,
            'raza' => 'Holstein', 'color' => 'Color', 'observaciones' => 'Observaciones',
            'peso_actual' => 1, 'fecha_nacimiento' => now()->subYear(),
            'created_at' => now()->subDay(), 'updated_at' => now()]);
        $animal->setRelation('finca', $farm);
        $animal->setRelation('potrero', (new Potrero)->forceFill(['id' => 0, 'nombre' => 'Potrero']));
        $views = [];
        foreach (['fincas' => 'fincas', 'animales' => 'fincas.animales'] as $module => $prefix) {
            foreach (['index', 'create', 'show', 'edit', 'index-empty', 'create-empty'] as $kind) {
                $empty = str_ends_with($kind, '-empty');
                $view = str_replace('-empty', '', $kind);
                $views[$module][$kind] = view($prefix.'.'.$view, [
                    'offlineTemplate' => true, 'offlineModule' => $module, 'offlineKind' => $view,
                    'finca' => $farm, 'animal' => $animal, 'codigo' => 'Código al sincronizar',
                    'fincas' => $empty ? collect() : collect([$farm]),
                    'animales' => $empty ? collect() : collect([$animal]),
                    'tipos' => Animal::getTipos(), 'razas' => Animal::getRazas(), 'ultimosSanitarios' => collect(),
                ])->render();
            }
        }

        return response()->json(['account_id' => $request->user()->id, 'views' => $views])
            ->header('Cache-Control', 'no-store, private');
    }
}
