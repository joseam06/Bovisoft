<?php

namespace App\Services;

use App\Models\Animal;
use App\Models\Finca;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class WorkspaceJournal
{
    public function transaction(int $owner, callable $callback): mixed
    {
        return DB::transaction(function () use ($owner, $callback) {
            DB::table('workspace_sync_heads')->insertOrIgnore(['user_id' => $owner, 'sequence' => 0]);
            $head = DB::table('workspace_sync_heads')->where('user_id', $owner)->lockForUpdate()->first();

            return $callback((int) $head->sequence);
        }, 5);
    }

    public function record(Model $record): void
    {
        DB::table('workspace_sync_heads')->where('user_id', $record->user_id)->increment('sequence');
        DB::table('workspace_sync_changes')->insert([
            'user_id' => $record->user_id,
            'sequence' => DB::table('workspace_sync_heads')->where('user_id', $record->user_id)->value('sequence'),
            'module' => WorkspaceModules::name($record), 'record_uuid' => $record->client_uuid,
            'payload' => json_encode(self::serialize($record), JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }

    public function nextCode(string $module): string
    {
        $counter = DB::table('workspace_code_counters')->where('name', $module)->lockForUpdate()->first();
        $number = (int) $counter->value + 1;
        $prefix = ['fincas' => 'FIN-', 'animales' => 'ANI-', 'potreros' => 'POT-', 'salud' => 'SAL-', 'ingresos' => 'ING-', 'egresos' => 'EGR-'][$module];
        while (DB::table($module)->where('codigo', $prefix.str_pad($number, 6, '0', STR_PAD_LEFT))->exists()) {
            $number++;
        }
        DB::table('workspace_code_counters')->where('name', $module)->update(['value' => $number]);

        return $prefix.str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public static function serialize(Model $record, ?array $context = null): array
    {
        if ($record instanceof \App\Models\WorkspaceProfile) return ['id'=>$record->id,'record_uuid'=>$record->client_uuid,'version'=>(int)$record->version,'deleted'=>false,'name'=>$record->name];
        if ($record instanceof \App\Models\Ingreso || $record instanceof \App\Models\Egreso) {
            $value = ['id' => $record->id, 'record_uuid' => $record->client_uuid, 'version' => (int) $record->version, 'deleted' => $record->trashed()];
            foreach (array_diff($record->getFillable(), ['user_id','animal_id','finca_id','salud_id','active_sale_animal_id']) as $field) {
                $v = $record->$field;$value[$field] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
            }
            $value += self::references($record, false, $context);
            $value['animal_uuid'] = self::referenceUuid(Animal::class, $record->animal_id, $context);
            if ($record instanceof \App\Models\Egreso) $value['salud_uuid'] = self::referenceUuid(\App\Models\Salud::class, $record->salud_id, $context);
            return $value;
        }
        if ($record instanceof \App\Models\Produccion) {
            return ProductionJournal::serialize($record) + self::references($record, true, $context)
                + ['created_at' => $record->created_at?->toIso8601String(), 'updated_at' => $record->updated_at?->toIso8601String()];
        }
        if ($record instanceof \App\Models\Potrero || $record instanceof \App\Models\Salud) {
            $fields = $record->getFillable();
            $value = ['id' => $record->id, 'record_uuid' => $record->client_uuid, 'version' => (int) $record->version,
                'deleted' => $record->trashed(), 'created_at' => $record->created_at?->toIso8601String(), 'updated_at' => $record->updated_at?->toIso8601String()];
            foreach (array_diff($fields, ['user_id', 'animal_id', 'finca_id']) as $field) {
                $v = $record->$field;
                $value[$field] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
            }
            return $value + self::references($record, $record instanceof \App\Models\Salud, $context)
                + ($record instanceof \App\Models\Salud ? ['egreso_vinculado' => $context !== null ? isset($context['expenses'][$record->id]) : \App\Models\Egreso::where('salud_id', $record->id)->exists()] : []);
        }
        $fields = $record instanceof Finca
            ? ['nombre', 'codigo', 'area', 'descripcion', 'direccion', 'municipio', 'departamento', 'latitud', 'longitud', 'activa']
            : ['codigo', 'numero', 'nombre', 'tipo', 'sexo', 'raza', 'peso_actual', 'color', 'observaciones', 'estado', 'potrero_id'];
        $value = ['id' => $record->id, 'record_uuid' => $record->client_uuid,
            'created_at' => $record->created_at?->toIso8601String(), 'updated_at' => $record->updated_at?->toIso8601String(),
            'version' => (int) $record->version, 'deleted' => $record->trashed()];
        foreach ($fields as $field) {
            $value[$field] = $record->$field;
        }
        if ($record instanceof Animal) {
            $value['finca_uuid'] = self::referenceUuid(Finca::class, $record->finca_id, $context);
            $value['fecha_nacimiento'] = $record->fecha_nacimiento?->format('Y-m-d');
            $value['venta_registrada'] = $record->ingreso_venta_id !== null;
            $value['ingreso_venta_uuid'] = self::referenceUuid(\App\Models\Ingreso::class, $record->ingreso_venta_id, $context);
            $value['potrero_uuid'] = self::referenceUuid(\App\Models\Potrero::class, $record->potrero_id, $context);
        }

        return $value;
    }

    private static function references(Model $record, bool $animal, ?array $context = null): array
    {
        $refs = ['finca_uuid' => self::referenceUuid(Finca::class, $record->finca_id, $context)];
        if ($animal) $refs['animal_uuid'] = self::referenceUuid(Animal::class, $record->animal_id, $context);
        return $refs;
    }

    private static function referenceUuid(string $class, mixed $id, ?array $context): ?string
    {
        if (!$id) return null;
        return $context !== null ? ($context[$class][$id] ?? null) : $class::withTrashed()->whereKey($id)->value('client_uuid');
    }

    public static function serializeMany(\Illuminate\Support\Collection $rows): array
    {
        $context=[];
        foreach(['finca_id'=>Finca::class,'animal_id'=>Animal::class,'potrero_id'=>\App\Models\Potrero::class,'ingreso_venta_id'=>\App\Models\Ingreso::class,'salud_id'=>\App\Models\Salud::class] as $field=>$class){
            $ids=$rows->pluck($field)->filter()->unique()->values();
            $context[$class]=$ids->isEmpty()?[]:$class::withTrashed()->whereIn('id',$ids)->pluck('client_uuid','id')->all();
        }
        $health=$rows->filter(fn($r)=>$r instanceof \App\Models\Salud)->pluck('id');
        $context['expenses']=$health->isEmpty()?[]:array_fill_keys(\App\Models\Egreso::whereIn('salud_id',$health)->pluck('salud_id')->all(),true);
        return $rows->map(fn($record)=>self::serialize($record,$context))->all();
    }
}
