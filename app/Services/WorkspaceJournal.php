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

    public static function serialize(Model $record): array
    {
        if ($record instanceof \App\Models\WorkspaceProfile) return ['id'=>$record->id,'record_uuid'=>$record->client_uuid,'version'=>(int)$record->version,'deleted'=>false,'name'=>$record->name];
        if ($record instanceof \App\Models\Ingreso || $record instanceof \App\Models\Egreso) {
            $value = ['id' => $record->id, 'record_uuid' => $record->client_uuid, 'version' => (int) $record->version, 'deleted' => $record->trashed()];
            foreach (array_diff($record->getFillable(), ['user_id','animal_id','finca_id','salud_id','active_sale_animal_id']) as $field) {
                $v = $record->$field;$value[$field] = $v instanceof \DateTimeInterface ? $v->format('Y-m-d') : $v;
            }
            $value += self::references($record, false);
            $value['animal_uuid'] = $record->animal_id ? Animal::withTrashed()->whereKey($record->animal_id)->value('client_uuid') : null;
            if ($record instanceof \App\Models\Egreso) $value['salud_uuid'] = $record->salud_id ? \App\Models\Salud::withTrashed()->whereKey($record->salud_id)->value('client_uuid') : null;
            return $value;
        }
        if ($record instanceof \App\Models\Produccion) {
            return ProductionJournal::serialize($record) + self::references($record, true)
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
            return $value + self::references($record, $record instanceof \App\Models\Salud)
                + ($record instanceof \App\Models\Salud ? ['egreso_vinculado' => \App\Models\Egreso::where('salud_id', $record->id)->exists()] : []);
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
            $value['finca_uuid'] = Finca::withTrashed()->whereKey($record->finca_id)->value('client_uuid');
            $value['fecha_nacimiento'] = $record->fecha_nacimiento?->format('Y-m-d');
            $value['venta_registrada'] = $record->ingreso_venta_id !== null;
            $value['ingreso_venta_uuid'] = $record->ingreso_venta_id ? \App\Models\Ingreso::withTrashed()->whereKey($record->ingreso_venta_id)->value('client_uuid') : null;
            $value['potrero_uuid'] = $record->potrero_id ? \App\Models\Potrero::withTrashed()->whereKey($record->potrero_id)->value('client_uuid') : null;
        }

        return $value;
    }

    private static function references(Model $record, bool $animal): array
    {
        $refs = ['finca_uuid' => Finca::withTrashed()->whereKey($record->finca_id)->value('client_uuid')];
        if ($animal) $refs['animal_uuid'] = Animal::withTrashed()->whereKey($record->animal_id)->value('client_uuid');
        return $refs;
    }
}
