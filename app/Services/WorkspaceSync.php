<?php

namespace App\Services;

use App\Exceptions\WorkspaceVersionConflict;
use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceSync
{
    // Phase 5 registers a module here only after its domain adapter/serialization exists.
    private const MODULES = ['fincas' => Finca::class, 'animales' => Animal::class];

    public function __construct(private readonly WorkspaceJournal $journal, private readonly WorkspaceRecords $records) {}

    public function apply(User $user, string $device, array $input): array
    {
        $op = Validator::make($input, [
            'operation_uuid' => 'required|uuid', 'record_uuid' => 'required|uuid',
            'module' => 'required|in:'.implode(',', array_keys(self::MODULES)), 'type' => 'required|in:create,update,delete',
            'base_version' => 'required_unless:type,create|prohibited_if:type,create|integer|min:1',
            'data' => 'present|array',
        ])->validate();
        $op['operation_uuid'] = strtolower($op['operation_uuid']);
        $op['record_uuid'] = strtolower($op['record_uuid']);
        $hash = hash('sha256', json_encode($this->canonical($op), JSON_THROW_ON_ERROR));

        return $this->journal->transaction($user->id, function () use ($user, $device, $op, $hash) {
            $receipt = DB::table('workspace_sync_receipts')->where('user_id', $user->id)->where('operation_uuid', $op['operation_uuid'])->first();
            if ($receipt) {
                if (! hash_equals($receipt->request_hash, $hash)) {
                    return ['status' => 409, 'error' => 'operation_uuid_reused'];
                }
                // Recheck ownership before replaying any previously authorized payload.
                $savedResult = json_decode($receipt->result, true, flags: JSON_THROW_ON_ERROR);
                if (! isset($savedResult['record']) && ! isset($savedResult['current'])) {
                    return $savedResult;
                }
                $class = self::MODULES[$op['module']];
                $record = $class::withTrashed()->where('user_id', $user->id)->where('client_uuid', $op['record_uuid'])->first();
                if (! $record || ($record instanceof Animal && ! Finca::withTrashed()->where('user_id', $user->id)->whereKey($record->finca_id)->exists())) {
                    return ['status' => 404, 'error' => 'record_unavailable'];
                }

                return $savedResult;
            }
            try {
                // Use a savepoint so validation/domain failures never leave side effects.
                $result = DB::transaction(function () use ($user, $op) {
                    $class = self::MODULES[$op['module']];
                    $record = null;
                    if ($op['type'] === 'create') {
                        if ($class::withTrashed()->where('client_uuid', $op['record_uuid'])->exists()) {
                            return ['status' => 409, 'error' => 'record_uuid_exists'];
                        }
                    } else {
                        $record = $this->records->find($user, $op['module'], $op['record_uuid'], uuid: true, trashed: true);
                        if ($record->trashed() || (int) $record->version !== (int) $op['base_version']) {
                            throw new WorkspaceVersionConflict($record);
                        }
                    }
                    $data = $op['data'];
                    unset($data['user_id'], $data['codigo'], $data['finca_id'], $data['version'], $data['client_uuid']);
                    if ($op['module'] === 'animales' && $op['type'] !== 'delete') {
                        $ref = Validator::make($data, ['finca_uuid' => 'required|uuid'])->validate();
                        $finca = Finca::delUsuario($user->id)->where('client_uuid', strtolower($ref['finca_uuid']))->lockForUpdate()->first();
                        if (! $finca) {
                            throw ValidationException::withMessages(['finca_uuid' => 'La finca no está disponible. Sincroniza o resuelve primero su operación.']);
                        }
                        $data['finca_id'] = $finca->id;
                    }
                    $saved = $this->records->write($user, $op['module'], $op['type'], $data + (isset($op['base_version']) ? ['base_version' => $op['base_version']] : []), $record, $op['record_uuid']);

                    return ['status' => $op['type'] === 'create' ? 201 : 200, 'record' => WorkspaceJournal::serialize($saved)];
                });
            } catch (WorkspaceVersionConflict $error) {
                $result = ['status' => 409, 'error' => 'version_conflict', 'current' => WorkspaceJournal::serialize($error->current)];
            } catch (ValidationException $error) {
                $result = ['status' => 422, 'error' => 'validation_failed', 'errors' => $error->errors()];
            } catch (ModelNotFoundException|AuthorizationException $error) {
                $result = ['status' => 404, 'error' => 'record_unavailable'];
            }
            DB::table('workspace_sync_receipts')->insert([
                'user_id' => $user->id, 'device_uuid' => $device, 'operation_uuid' => $op['operation_uuid'],
                'request_hash' => $hash, 'result' => json_encode($result, JSON_THROW_ON_ERROR), 'created_at' => now(),
            ]);

            return $result;
        });
    }

    public function snapshot(User $user): array
    {
        return $this->journal->transaction($user->id, function ($head) use ($user) {
            $catalog = $this->catalog($user);
            $id = (string) Str::uuid();
            DB::table('workspace_sync_snapshots')->insert([
                'id' => $id, 'user_id' => $user->id, 'sequence' => $head,
                'payload' => json_encode($catalog, JSON_THROW_ON_ERROR), 'expires_at' => now()->addDay(),
            ]);

            return $this->page($user, $id, 1);
        });
    }

    public function page(User $user, string $id, int $page): array
    {
        return $this->journal->transaction($user->id, function () use ($user, $id, $page) {
            $snapshot = DB::table('workspace_sync_snapshots')->where('user_id', $user->id)->where('id', $id)->first();
            abort_unless($snapshot, 404);
            abort_if(now()->greaterThan($snapshot->expires_at), 410);
            $data = json_decode($snapshot->payload, true, flags: JSON_THROW_ON_ERROR);
            // Old materialized pages cannot retain records whose access was revoked.
            foreach (self::MODULES as $module => $class) {
                $ids = array_column(array_filter($data['records'], fn ($row) => $row['module'] === $module), 'record_uuid');
                $query = $class::where('user_id', $user->id)->whereIn('client_uuid', $ids);
                if ($module === 'animales') {
                    $query->whereHas('finca', fn ($q) => $q->delUsuario($user->id));
                }
                abort_unless($query->count() === count($ids), 409, 'Los permisos o las referencias cambiaron. Repite la descarga.');
            }
            $potreros = array_column($data['references']['potreros'], 'id');
            abort_unless(Potrero::where('user_id', $user->id)->whereIn('id', $potreros)
                ->whereHas('finca', fn ($q) => $q->delUsuario($user->id))->count() === count($potreros), 409);
            $pages = max(1, (int) ceil(count($data['records']) / 100));
            abort_if($page < 1 || $page > $pages, 404);
            $complete = $page === $pages;

            return ['account_id' => $user->id, 'snapshot_uuid' => $id, 'total_records' => count($data['records']),
                'records' => array_slice($data['records'], ($page - 1) * 100, 100), 'references' => $data['references'],
                'complete' => $complete, 'next_page' => $complete ? null : $page + 1,
                'cursor' => $complete ? $this->cursor($user, (int) $snapshot->sequence) : null];
        });
    }

    public function changes(User $user, string $cursor): array
    {
        try {
            $position = json_decode(Crypt::decryptString($cursor), true, flags: JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw ValidationException::withMessages(['cursor' => 'El cursor no es válido.']);
        }
        abort_unless(($position['owner'] ?? null) === $user->id && ($position['kind'] ?? null) === 'workspace', 404);

        return $this->journal->transaction($user->id, function ($head) use ($user, $position) {
            $catalog = $this->catalog($user);
            $valid = array_fill_keys(array_keys(self::MODULES), []);
            foreach ($catalog['records'] as $row) {
                $valid[$row['module']][] = $row['record_uuid'];
            }
            $scan = DB::table('workspace_sync_changes')->where('user_id', $user->id)
                ->where('sequence', '>', $position['sequence'])->where('sequence', '<=', $head)->orderBy('sequence')->limit(200)->get();
            $changes = [];
            foreach ($scan as $event) {
                // Publish current authorized values, never a former owner's historical payload.
                $current = collect($catalog['records'])->first(fn ($r) => $r['module'] === $event->module && $r['record_uuid'] === $event->record_uuid);
                $changes[] = $current ? ['module' => $event->module, 'action' => 'upsert', 'record' => $current]
                    : ['module' => $event->module, 'action' => 'remove', 'record_uuid' => $event->record_uuid];
            }
            $sequence = $scan->isEmpty() ? $head : (int) $scan->last()->sequence;

            return ['account_id' => $user->id, 'changes' => $changes, 'valid' => $valid, 'references' => $catalog['references'],
                'cursor' => $this->cursor($user, $sequence), 'has_more' => $sequence < $head];
        });
    }

    private function catalog(User $user): array
    {
        $farms = Finca::delUsuario($user->id)->orderBy('id')->limit(10001)->get();
        $animals = Animal::where('user_id', $user->id)->whereHas('finca', fn ($q) => $q->delUsuario($user->id))->orderBy('id')->limit(10001)->get();
        $records = [];
        foreach (['fincas' => $farms, 'animales' => $animals] as $module => $rows) {
            abort_if($rows->count() > 10000, 422, 'El piloto admite hasta diez mil registros por módulo.');
            foreach ($rows as $row) {
                $records[] = ['module' => $module] + WorkspaceJournal::serialize($row);
            }
        }
        $potreros = Potrero::where('user_id', $user->id)->whereIn('finca_id', $farms->modelKeys())->get()
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'finca_uuid' => $farms->firstWhere('id', $p->finca_id)->client_uuid, 'animales_actuales' => $p->animales_actuales])->all();

        return ['records' => $records, 'references' => ['potreros' => $potreros, 'tipos' => Animal::getTipos(), 'razas' => Animal::getRazas()]];
    }

    private function cursor(User $user, int $sequence): string
    {
        return Crypt::encryptString(json_encode(['kind' => 'workspace', 'owner' => $user->id, 'sequence' => $sequence], JSON_THROW_ON_ERROR));
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
