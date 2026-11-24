<?php

namespace App\Services;

use App\Models\{Animal, Finca, Potrero, Produccion, Salud};

final class WorkspaceModules
{
    public const MODELS = ['fincas' => Finca::class, 'animales' => Animal::class, 'potreros' => Potrero::class,
        'produccion' => Produccion::class, 'salud' => Salud::class, 'ingresos' => \App\Models\Ingreso::class, 'egresos' => \App\Models\Egreso::class,
        'perfil' => \App\Models\WorkspaceProfile::class];

    public static function name(object $record): string
    {
        return array_search($record::class, self::MODELS, true) ?: throw new \InvalidArgumentException('Módulo no registrado.');
    }
}
