<?php

namespace App\Services;

use App\Models\{Animal, Finca, Potrero, Produccion, Salud};
use Illuminate\Pagination\LengthAwarePaginator;

class OfflineModuleViews
{
    public function render(Finca $farm, Animal $animal): array
    {
        $potrero = (new Potrero)->forceFill(['id' => 0, 'user_id' => $farm->user_id, 'finca_id' => 0, 'codigo' => 'Código',
            'nombre' => 'Nombre', 'area' => 1, 'tipo_pasto' => 'Mixto', 'capacidad_animales' => 10, 'animales_actuales' => 1,
            'estado' => 'activo', 'dias_descanso' => 30, 'fecha_ultima_rotacion' => today()->subDays(40), 'observaciones' => 'Observaciones',
            'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $potrero->setRelation('finca', $farm)->setRelation('animales', collect([$animal]));
        $production = (new Produccion)->forceFill(['id' => 0, 'user_id' => $farm->user_id, 'animal_id' => 0, 'finca_id' => 0,
            'codigo' => 'Código', 'fecha' => today(), 'litros' => 1, 'sesion' => 'manana', 'calidad' => 'normal',
            'observaciones' => 'Observaciones', 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        $health = (new Salud)->forceFill(['id' => 0, 'user_id' => $farm->user_id, 'animal_id' => 0, 'finca_id' => 0,
            'codigo' => 'Código', 'tipo' => 'vacunacion', 'categoria' => 'preventivo', 'nombre_producto' => 'Producto',
            'fecha_aplicacion' => today(), 'proxima_aplicacion' => today()->addDays(3), 'dias_carencia' => 3, 'fin_carencia' => today()->addDays(3),
            'enfermedad_prevenida' => 'Prevención', 'diagnostico' => 'Diagnóstico', 'veterinario' => 'Veterinario', 'laboratorio' => 'Laboratorio', 'lote_medicamento' => 'Lote',
            'costo' => 1, 'dosis' => 1, 'unidad_dosis' => 'ml', 'via_aplicacion' => 'oral', 'estado' => 'completado',
            'observaciones' => 'Observaciones', 'version' => 1, 'created_at' => now(), 'updated_at' => now()]);
        foreach ([$production, $health] as $record) $record->setRelation('finca', $farm)->setRelation('animal', $animal);
        $top = clone $production;
        $top->forceFill(['total_litros' => 1, 'total_registros' => 1]);
        $paginator = fn ($rows) => new LengthAwarePaginator($rows, count($rows), 15, 1, ['path' => '/']);
        $healthIndex = app(\App\Http\Controllers\SaludController::class)->index()->getData();
        foreach ($healthIndex['categorias'] as &$category) $category['count'] = 1;
        unset($category);
        $result = [];
        foreach (['potreros' => $potrero, 'produccion' => $production, 'salud' => $health] as $module => $record) {
            foreach (['index', 'create', 'show', 'edit', 'index-empty', 'create-empty', 'por_animal', 'preventivo', 'clinico', 'reproductivo', 'seguimiento'] as $kind) {
                if ($module === 'potreros' && in_array($kind, ['por_animal','preventivo','clinico','reproductivo','seguimiento'])) continue;
                if ($module === 'produccion' && in_array($kind, ['preventivo','clinico','reproductivo','seguimiento'])) continue;
                $empty = str_ends_with($kind, '-empty');
                $view = str_replace('-empty', '', $kind);
                $vars = ['offlineTemplate' => true, 'offlineModule' => $module, 'offlineKind' => $view,
                    'finca' => $farm, 'animal' => $animal, 'animalPresel' => null, 'fincas' => collect([$farm]), 'animales' => collect([$animal]),
                    'codigo' => 'Código al sincronizar', 'registro' => $record, 'potrero' => $potrero,
                    'registros' => $paginator($empty ? [] : [$record]), 'potreros' => $paginator($empty ? [] : [$potrero]),
                    'tipos_pasto' => Potrero::getTiposPasto(), 'estados' => $module === 'potreros' ? Potrero::getEstados() : Salud::getEstados(),
                    'tipos' => Salud::getTipos(), 'vias' => Salud::getViasAplicacion(), 'unidades' => Salud::getUnidadesDosis(),
                    'sesiones' => Produccion::getSesiones(), 'calidades' => Produccion::getCalidades(), 'requiereRevisionFecha' => false,
                    'otrosRegistros' => collect(), 'historialAnimal' => collect([$record]), 'topAnimales' => collect([$top]), 'chartDias' => [], 'chartLitros' => [],
                    'total' => 0, 'activos' => 0, 'en_descanso' => 0, 'area_total' => 0, 'totalHoy' => 0, 'totalSemana' => 0,
                    'totalMes' => 0, 'totalRegistros' => 0, 'totalAnimal' => 0, 'promedioAnimal' => 0] + $healthIndex;
                if (in_array($kind, ['preventivo','clinico','reproductivo','seguimiento'])) {
                    $vars += ['categoria' => $kind, 'titulo' => ucfirst($kind), 'descripcion' => 'Historial sanitario'];
                    $view = 'categoria';
                }
                $result[$module][$kind] = view($module.'.'.$view, $vars)->render();
            }
        }
        return $result;
    }
}
