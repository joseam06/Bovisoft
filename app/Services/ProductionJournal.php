<?php

namespace App\Services;

use App\Models\Produccion;
use Illuminate\Support\Facades\DB;

class ProductionJournal
{
    public function transaction(int $userId, callable $callback, bool $consistentRead = false): mixed
    {
        $nested = DB::transactionLevel() > 0;

        return DB::transaction(function () use ($userId, $callback, $consistentRead, $nested) {
            if ($consistentRead && ! $nested && DB::getDriverName() === 'pgsql') {
                DB::statement('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            }
            DB::table('production_sync_heads')->insertOrIgnore(['user_id' => $userId, 'sequence' => 0]);
            $head = DB::table('production_sync_heads')->where('user_id', $userId)->lockForUpdate()->first();

            return $callback((int) $head->sequence);
        }, 5);
    }

    public function record(Produccion $record, string $action = 'upsert'): void
    {
        DB::table('production_sync_heads')->where('user_id', $record->user_id)->increment('sequence');
        $sequence = DB::table('production_sync_heads')->where('user_id', $record->user_id)->value('sequence');
        DB::table('production_sync_changes')->insert([
            'user_id' => $record->user_id, 'sequence' => $sequence,
            'record_uuid' => $record->client_uuid, 'action' => $action,
            'payload' => json_encode(self::serialize($record), JSON_THROW_ON_ERROR), 'created_at' => now(),
        ]);
    }

    public function nextCode(): string
    {
        $counter = DB::table('production_code_counters')->where('name', 'production')->lockForUpdate()->first();
        $number = (int) $counter->value + 1;
        DB::table('production_code_counters')->where('name', 'production')->update(['value' => $number]);

        return 'PROD-'.str_pad($number, 6, '0', STR_PAD_LEFT);
    }

    public static function serialize(Produccion $record): array
    {
        return [
            'id' => $record->id, 'record_uuid' => $record->client_uuid, 'version' => (int) $record->version,
            'codigo' => $record->codigo, 'animal_id' => $record->animal_id, 'finca_id' => $record->finca_id,
            'fecha' => $record->fecha->format('Y-m-d'), 'sesion' => $record->sesion,
            'litros' => $record->litros, 'calidad' => $record->calidad, 'observaciones' => $record->observaciones,
            'deleted' => $record->trashed(),
        ];
    }
}
