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
            'module' => $record->getTable(), 'record_uuid' => $record->client_uuid,
            'payload' => json_encode(self::serialize($record), JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }

    public function nextCode(string $module): string
    {
        $counter = DB::table('workspace_code_counters')->where('name', $module)->lockForUpdate()->first();
        $number = (int) $counter->value + 1;
        $prefix = $module === 'fincas' ? 'FIN-' : 'ANI-';
        while (DB::table($module)->where('codigo', $prefix.str_pad($number, 6, '0', STR_PAD_LEFT))->exists()) {
            $number++;
        }
        DB::table('workspace_code_counters')->where('name', $module)->update(['value' => $number]);

        return $prefix.str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public static function serialize(Model $record): array
    {
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
        }

        return $value;
    }
}
