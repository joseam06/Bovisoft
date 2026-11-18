<?php

namespace App\Services;

use App\Exceptions\ProduccionVersionConflict;
use App\Models\Animal;
use App\Models\Finca;
use App\Models\Produccion;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductionSyncService
{
    public function __construct(private readonly ProductionJournal $journal, private readonly ProduccionService $production) {}

    public function applyOperation(User $user, string $device, array $input): array
    {
        try {
            $operation = Validator::make($input, [
                'operation_uuid' => 'required|uuid', 'record_uuid' => 'required|uuid',
                'type' => 'required|in:create,update',
                'base_version' => 'required_if:type,update|prohibited_if:type,create|integer|min:1',
                'data' => 'required|array:animal_id,finca_id,fecha,sesion,litros,calidad,observaciones',
            ])->validate();
        } catch (ValidationException $error) {
            return ['status' => 422, 'body' => ['error' => 'validation_failed', 'errors' => $error->errors()]];
        }
        $operation['operation_uuid'] = strtolower($operation['operation_uuid']);
        $operation['record_uuid'] = strtolower($operation['record_uuid']);
        $hash = hash('sha256', json_encode($this->canonical($operation), JSON_THROW_ON_ERROR));

        return $this->journal->transaction($user->id, function () use ($user, $device, $operation, $hash) {
            $receipt = DB::table('production_sync_receipts')->where('user_id', $user->id)
                ->where('operation_uuid', $operation['operation_uuid'])->first();
            if ($receipt) {
                if (! hash_equals($receipt->request_hash, $hash)) {
                    return ['status' => 409, 'body' => ['error' => 'operation_uuid_reused']];
                }
                $result = json_decode($receipt->result, true, flags: JSON_THROW_ON_ERROR);
                if (isset($result['body']['record']) || isset($result['body']['current'])) {
                    $accessible = Produccion::withTrashed()->where('user_id', $user->id)
                        ->where('client_uuid', $receipt->record_uuid)
                        ->whereHas('animal', fn ($query) => $query->where('user_id', $user->id))
                        ->whereHas('finca', fn ($query) => $query->delUsuario($user->id))->exists();
                    if (! $accessible) {
                        return ['status' => 404, 'body' => ['error' => 'record_unavailable']];
                    }
                }

                return $result;
            }
            try {
                if ($operation['type'] === 'create') {
                    if (Produccion::withTrashed()->where('client_uuid', $operation['record_uuid'])->exists()) {
                        $result = ['status' => 409, 'body' => ['error' => 'record_uuid_exists']];
                    } else {
                        $record = $this->production->create($user, $operation['data'], $operation['record_uuid']);
                        $result = ['status' => 201, 'body' => ['record' => ProductionJournal::serialize($record)]];
                    }
                } else {
                    $record = Produccion::autorizadasPara($user->id)->where('client_uuid', $operation['record_uuid'])->firstOrFail();
                    $record = $this->production->update($user, $record->id, $operation['data'] + ['base_version' => $operation['base_version']]);
                    $result = ['status' => 200, 'body' => ['record' => ProductionJournal::serialize($record)]];
                }
            } catch (ProduccionVersionConflict $error) {
                $result = ['status' => 409, 'body' => ['error' => 'version_conflict', 'current' => ProductionJournal::serialize($error->current)]];
            } catch (ValidationException $error) {
                $result = ['status' => 422, 'body' => ['error' => 'validation_failed', 'errors' => $error->errors()]];
            } catch (ModelNotFoundException|AuthorizationException $error) {
                $result = ['status' => 404, 'body' => ['error' => 'record_unavailable']];
            } catch (QueryException $error) {
                if ($error->getCode() !== '23505' && ($error->errorInfo[1] ?? null) !== 1062) {
                    throw $error;
                }
                $result = ['status' => 409, 'body' => ['error' => 'record_uuid_exists']];
            }
            DB::table('production_sync_receipts')->insert([
                'user_id' => $user->id, 'operation_uuid' => $operation['operation_uuid'],
                'device_uuid' => $device, 'record_uuid' => $operation['record_uuid'],
                'request_hash' => $hash, 'result' => json_encode($result, JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);

            return $result;
        });
    }

    public function snapshot(User $user, array $input): array
    {
        $data = Validator::make($input, [
            'finca_ids' => 'sometimes|array|max:50', 'finca_ids.*' => 'integer|min:1',
            'history_days' => 'sometimes|integer|min:1|max:'.config('offline.production.history_days'),
            'page_size' => 'sometimes|integer|min:1|max:200',
        ])->validate();

        return $this->journal->transaction($user->id, function ($sequence) use ($user, $data) {
            $ids = array_values(array_unique(array_map('intval', $data['finca_ids'] ?? Finca::delUsuario($user->id)->pluck('id')->all())));
            if (count($ids) > 50) {
                throw ValidationException::withMessages(['finca_ids' => 'Selecciona como máximo 50 fincas para preparar el piloto.']);
            }
            sort($ids);
            abort_unless(Finca::delUsuario($user->id)->whereIn('id', $ids)->count() === count($ids), 404);
            $scope = ['finca_ids' => $ids, 'since' => now()->startOfDay()->subDays(($data['history_days'] ?? config('offline.production.history_days')) - 1)->format('Y-m-d')];
            $catalog = $this->catalog($user, $scope);
            $snapshot = [
                'id' => (string) Str::uuid(), 'user_id' => $user->id, 'scope' => json_encode($scope, JSON_THROW_ON_ERROR),
                'sequence' => $sequence, 'payload' => json_encode($catalog, JSON_THROW_ON_ERROR),
                'page_size' => $data['page_size'] ?? 100, 'expires_at' => now()->addHours(config('offline.production.snapshot_hours')),
            ];
            DB::table('production_sync_snapshots')->insert($snapshot);

            return $this->snapshotPageData((object) $snapshot, $user, 1);
        }, consistentRead: true);
    }

    public function snapshotPage(User $user, string $id, int $page): array
    {
        return $this->journal->transaction($user->id, function () use ($user, $id, $page) {
            $snapshot = DB::table('production_sync_snapshots')->where('user_id', $user->id)->where('id', $id)->first();
            abort_unless($snapshot, 404);

            return $this->snapshotPageData($snapshot, $user, $page);
        }, consistentRead: true);
    }

    private function snapshotPageData(object $snapshot, User $user, int $page): array
    {
        abort_if(now()->greaterThan($snapshot->expires_at), 410, 'La descarga inicial caducó. Prepara una nueva descarga.');
        $payload = json_decode($snapshot->payload, true, flags: JSON_THROW_ON_ERROR);
        foreach (['fincas' => Finca::class, 'animales' => Animal::class] as $key => $model) {
            $ids = array_column($payload['references'][$key], 'id');
            abort_unless($model::where('user_id', $user->id)->whereIn('id', $ids)->count() === count($ids), 409, 'Los permisos o las referencias cambiaron. Repite la preparación.');
        }
        $recordUuids = array_column($payload['records'], 'record_uuid');
        abort_unless(Produccion::withTrashed()->where('user_id', $user->id)->whereIn('client_uuid', $recordUuids)->count() === count($recordUuids), 409, 'Los permisos cambiaron. Repite la preparación.');
        $pages = max(1, (int) ceil(count($payload['records']) / $snapshot->page_size));
        abort_if($page < 1 || $page > $pages, 404);
        $complete = $page === $pages;

        return [
            'protocol' => 1, 'account_id' => $user->id, 'snapshot_uuid' => $snapshot->id, 'page' => $page, 'total_records' => count($payload['records']),
            'records' => array_slice($payload['records'], ($page - 1) * $snapshot->page_size, $snapshot->page_size),
            'references' => $payload['references'], 'valid_record_uuids' => $payload['valid_record_uuids'],
            'complete' => $complete, 'next_page' => $complete ? null : $page + 1,
            'cursor' => $complete ? $this->cursor($user, json_decode($snapshot->scope, true), (int) $snapshot->sequence) : null,
        ];
    }

    public function changes(User $user, string $cursor, int $limit): array
    {
        try {
            $position = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable $error) {
            throw ValidationException::withMessages(['cursor' => 'El cursor no es válido.']);
        }
        abort_unless(($position['user_id'] ?? null) === $user->id && ($position['protocol'] ?? null) === 1, 404);
        abort_if(now()->timestamp > $position['expires'], 410, 'El cursor caducó. Prepara otra descarga conservando los pendientes.');

        return $this->journal->transaction($user->id, function ($head) use ($user, $position, $limit) {
            $catalog = $this->catalog($user, $position['scope']);
            $valid = array_flip($catalog['valid_record_uuids']);
            $scan = DB::table('production_sync_changes')->where('user_id', $user->id)
                ->where('sequence', '>', $position['sequence'])->where('sequence', '<=', $head)
                ->orderBy('sequence')->limit($limit)->get();
            $changes = [];
            foreach ($scan as $event) {
                $record = json_decode($event->payload, true, flags: JSON_THROW_ON_ERROR);
                if (! in_array((int) $record['finca_id'], $position['scope']['finca_ids'], true)) {
                    continue;
                }
                if ($event->action === 'remove' || ! isset($valid[$record['record_uuid']]) || $record['fecha'] < $position['scope']['since']) {
                    $changes[] = ['action' => 'remove', 'record_uuid' => $record['record_uuid'], 'version' => $record['version']];
                } else {
                    $changes[] = ['action' => 'upsert', 'record' => $record];
                }
            }
            $sequence = $scan->isEmpty() ? $head : (int) $scan->last()->sequence;

            return ['protocol' => 1, 'account_id' => $user->id, 'changes' => $changes, 'references' => $catalog['references'],
                'valid_record_uuids' => $catalog['valid_record_uuids'], 'has_more' => $sequence < $head,
                'cursor' => $this->cursor($user, $position['scope'], $sequence, $position['expires'])];
        }, consistentRead: true);
    }

    private function catalog(User $user, array $scope): array
    {
        $fincas = Finca::delUsuario($user->id)->whereIn('id', $scope['finca_ids'])->orderBy('id')
            ->get(['id', 'nombre', 'codigo', 'activa']);
        $records = Produccion::autorizadasPara($user->id)->whereIn('finca_id', $fincas->modelKeys())
            ->whereDate('fecha', '>=', $scope['since'])->orderBy('id')->limit(config('offline.production.max_records') + 1)->get();
        $animales = Animal::where('user_id', $user->id)->where(function ($query) use ($fincas, $records) {
            $query->whereIn('finca_id', $fincas->modelKeys())->orWhereIn('id', $records->pluck('animal_id'));
        })->orderBy('id')->limit(config('offline.production.max_records') + 1)->get(['id', 'codigo', 'nombre', 'finca_id', 'tipo', 'estado']);
        if ($records->count() > config('offline.production.max_records') || $animales->count() > config('offline.production.max_records')) {
            throw ValidationException::withMessages(['scope' => 'Selecciona menos fincas o un intervalo más corto.']);
        }

        return ['records' => $records->map(fn ($record) => ProductionJournal::serialize($record))->all(),
            'valid_record_uuids' => $records->pluck('client_uuid')->all(),
            'references' => ['authoritative' => true, 'fincas' => $fincas->toArray(), 'animales' => $animales->toArray()]];
    }

    private function cursor(User $user, array $scope, int $sequence, ?int $expires = null): string
    {
        return Crypt::encryptString(json_encode(['protocol' => 1, 'user_id' => $user->id, 'scope' => $scope,
            'sequence' => $sequence, 'expires' => $expires ?? now()->addDays(config('offline.production.cursor_days'))->timestamp], JSON_THROW_ON_ERROR));
    }

    private function canonical(array $value): array
    {
        ksort($value);
        foreach ($value as &$item) {
            if (is_array($item)) {
                $item = $this->canonical($item);
            }
        }

        return $value;
    }
}
