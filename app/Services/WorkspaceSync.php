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
    private const MODULES = WorkspaceModules::MODELS;

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
                if (! $this->receiptAuthorized($user, $op['module'], $savedResult)) {
                    return ['status' => 404, 'error' => 'record_unavailable'];
                }
                if (! isset($savedResult['record']) && ! isset($savedResult['current'])) {
                    return $savedResult;
                }
                $class = self::MODULES[$op['module']];
                $record = $class::withTrashed()->where('user_id', $user->id)->where('client_uuid', $op['record_uuid'])->first();
                if (! $record || ($op['module'] !== 'perfil' && ! $record instanceof Finca && ! Finca::withTrashed()->where('user_id', $user->id)->whereKey($record->finca_id)->exists())
                    || (in_array($op['module'], ['produccion', 'salud'], true) && ! Animal::withTrashed()->where('user_id', $user->id)->whereKey($record->animal_id)->exists())
                    || (in_array($op['module'], ['ingresos','egresos'], true) && $record->animal_id && ! Animal::withTrashed()->where('user_id', $user->id)->whereKey($record->animal_id)->exists())) {
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
                    if (! in_array($op['module'], ['fincas','perfil'], true) && $op['type'] !== 'delete') {
                        $ref = Validator::make($data, ['finca_uuid' => 'required|uuid'])->validate();
                        $finca = Finca::delUsuario($user->id)->where('client_uuid', strtolower($ref['finca_uuid']))->lockForUpdate()->first();
                        if (! $finca) {
                            throw ValidationException::withMessages(['finca_uuid' => 'La finca no está disponible. Sincroniza o resuelve primero su operación.']);
                        }
                        $data['finca_id'] = $finca->id;
                        if (in_array($op['module'], ['ingresos','egresos'], true)) {
                            Validator::make($data, ['animal_uuid' => 'nullable|uuid', 'salud_uuid' => 'nullable|uuid'])->validate();
                            $data['animal_id'] = empty($data['animal_uuid']) ? null : Animal::where('user_id', $user->id)->where('client_uuid', $data['animal_uuid'])->lockForUpdate()->firstOrFail()->id;
                            if ($op['module'] === 'egresos') $data['salud_id'] = empty($data['salud_uuid']) ? null : \App\Models\Salud::where('user_id', $user->id)->where('client_uuid', $data['salud_uuid'])->lockForUpdate()->firstOrFail()->id;
                        }
                        if (in_array($op['module'], ['produccion', 'salud'], true)) {
                            Validator::make($data, ['animal_uuid' => 'required|uuid'])->validate();
                            $data['animal_id'] = Animal::where('user_id', $user->id)->where('client_uuid', strtolower($data['animal_uuid']))->lockForUpdate()->firstOrFail()->id;
                        }
                        if ($op['module'] === 'animales' && array_key_exists('potrero_uuid', $data)) {
                            Validator::make($data, ['potrero_uuid' => 'nullable|uuid'])->validate();
                            $data['potrero_id'] = $data['potrero_uuid'] ? Potrero::where('user_id', $user->id)->where('finca_id', $finca->id)
                                ->where('client_uuid', strtolower($data['potrero_uuid']))->lockForUpdate()->firstOrFail()->id : null;
                        }
                    }
                    $animalId=in_array($op['module'],['ingresos','egresos'],true)?($data['animal_id']??$record?->animal_id):null;
                    $oldPotrero=$animalId?Animal::where('user_id',$user->id)->whereKey($animalId)->value('potrero_id'):null;
                    $saved = $this->records->write($user, $op['module'], $op['type'], $data + (isset($op['base_version']) ? ['base_version' => $op['base_version']] : []), $record, $op['record_uuid']);
                    $related=[];
                    if($animalId){$a=Animal::where('user_id',$user->id)->findOrFail($animalId);$related[]=['module'=>'animales','record'=>WorkspaceJournal::serialize($a)];
                        foreach(array_unique(array_filter([$oldPotrero,$a->potrero_id])) as $id){$p=Potrero::where('user_id',$user->id)->find($id);if($p)$related[]=['module'=>'potreros','record'=>WorkspaceJournal::serialize($p)];}}
                    if($saved instanceof \App\Models\Egreso && $saved->salud_id){$s=\App\Models\Salud::where('user_id',$user->id)->find($saved->salud_id);if($s)$related[]=['module'=>'salud','record'=>WorkspaceJournal::serialize($s)];}

                    return ['status' => $op['type'] === 'create' ? 201 : 200, 'record' => WorkspaceJournal::serialize($saved),'related'=>$related];
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

    public function snapshot(User $user, bool $chunkTransport = false): array
    {
        return $this->journal->transaction($user->id, function ($head) use ($user, $chunkTransport) {
            $catalog = $this->catalog($user);
            $id = (string) Str::uuid();
            $json = json_encode($catalog, JSON_THROW_ON_ERROR);
            $chunkStorage = \Illuminate\Support\Facades\Schema::hasTable('workspace_snapshot_chunks');
            abort_if(!$chunkStorage && strlen($json)>512*1024,503,'Falta aplicar la migración de bloques offline. La copia anterior se conserva.');
            $chunks = str_split($json, 128 * 1024);
            $metadata = ['chunk_storage'=>1,'parts'=>count($chunks),'bytes'=>strlen($json),'sha256'=>hash('sha256',$json),'chunk_transport'=>$chunkTransport];
            DB::table('workspace_sync_snapshots')->insert([
                'id' => $id, 'user_id' => $user->id, 'sequence' => $head,
                'payload' => $chunkStorage ? json_encode($metadata, JSON_THROW_ON_ERROR) : $json, 'expires_at' => now()->addDay(),
            ]);
            if($chunkStorage)foreach($chunks as $index=>$chunk)DB::table('workspace_snapshot_chunks')->insert(['snapshot_id'=>$id,'part'=>$index+1,'payload'=>base64_encode($chunk)]);

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
            $metadata = isset($data['chunk_storage']) ? $data : null;
            if($metadata){
                $parts=DB::table('workspace_snapshot_chunks')->where('snapshot_id',$id)->orderBy('part')->get();
                abort_unless($parts->count()===$metadata['parts'],409,'La copia está incompleta.');
                $json='';foreach($parts as $index=>$part){abort_unless($part->part===$index+1 || (int)$part->part===$index+1,409);$decoded=base64_decode($part->payload,true);abort_if($decoded===false,409);$json.=$decoded;}
                abort_unless(strlen($json)===$metadata['bytes'] && hash_equals($metadata['sha256'],hash('sha256',$json)),409);
                $data=json_decode($json,true,flags:JSON_THROW_ON_ERROR);
            }
            // Old materialized pages cannot retain records whose access was revoked.
            foreach (self::MODULES as $module => $class) {
                $ids = array_column(array_filter($data['records'], fn ($row) => $row['module'] === $module), 'record_uuid');
                $query = (in_array($module, ['ingresos','egresos'], true) ? FinancialService::authorized($class, $user->id) : $class::where('user_id', $user->id))->whereIn('client_uuid', $ids);
                if (! in_array($module, ['fincas','perfil'], true)) {
                    $query->whereHas('finca', fn ($q) => $q->delUsuario($user->id));
                }
                if (in_array($module, ['produccion', 'salud'], true)) $query->whereHas('animal', fn ($q) => $q->where('user_id', $user->id));
                abort_unless($query->count() === count($ids), 409, 'Los permisos o las referencias cambiaron. Repite la descarga.');
            }
            $potreros = array_column($data['references']['potreros'], 'id');
            abort_unless(Potrero::where('user_id', $user->id)->whereIn('id', $potreros)
                ->whereHas('finca', fn ($q) => $q->delUsuario($user->id))->count() === count($potreros), 409);
            $pages = $metadata && $metadata['chunk_transport'] ? $metadata['parts'] : max(1, (int) ceil(count($data['records']) / 100));
            abort_if($page < 1 || $page > $pages, 404);
            $complete = $page === $pages;
            if($metadata && $metadata['chunk_transport'])return ['account_id'=>$user->id,'snapshot_uuid'=>$id,'format'=>'catalog-chunks-v1',
                'total_records'=>count($data['records']),'total_bytes'=>$metadata['bytes'],'sha256'=>$metadata['sha256'],'part'=>$page,'parts'=>$pages,
                'chunk'=>$parts[$page-1]->payload,'complete'=>$complete,'next_page'=>$complete?null:$page+1,'cursor'=>$complete?$this->cursor($user,(int)$snapshot->sequence):null];

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
        \App\Models\WorkspaceProfile::firstOrCreate(['user_id'=>$user->id],['name'=>$user->name]);
        $records = [];
        foreach (self::MODULES as $module => $class) {
            $query = in_array($module, ['ingresos','egresos'], true) ? FinancialService::authorized($class, $user->id) : $class::where('user_id', $user->id);
            if (! in_array($module, ['fincas','perfil'], true)) $query->whereHas('finca', fn ($q) => $q->delUsuario($user->id));
            if (in_array($module, ['produccion', 'salud'], true)) $query->whereHas('animal', fn ($q) => $q->where('user_id', $user->id));
            $rows = $query->orderBy('id')->limit(10001)->get();
            abort_if($rows->count() > 10000, 422, 'La copia local admite hasta diez mil registros por módulo; la copia anterior se conserva.');
            foreach (WorkspaceJournal::serializeMany($rows) as $row) $records[] = ['module' => $module] + $row;
        }
        $potreros = array_values(array_filter($records, fn ($r) => $r['module'] === 'potreros'));
        return ['records' => $records, 'references' => ['potreros' => $potreros, 'tipos' => Animal::getTipos(), 'razas' => Animal::getRazas(),
            'workspace_schema' => 4, 'complete_modules' => array_keys(self::MODULES), 'alert_rules' => AlertRules::catalog(),
            'financial_types'=>\App\Models\Ingreso::getTipos(),'financial_categories'=>\App\Models\Egreso::getCategorias()]];
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

    private function receiptAuthorized(User $user, string $module, array $result): bool
    {
        $values = $result['related'] ?? [];
        foreach (['record', 'current'] as $field) {
            if (isset($result[$field])) $values[] = ['module' => $module, 'record' => $result[$field]];
        }
        $references = ['finca_uuid' => Finca::class, 'animal_uuid' => Animal::class,
            'potrero_uuid' => Potrero::class, 'salud_uuid' => \App\Models\Salud::class,
            'ingreso_venta_uuid' => \App\Models\Ingreso::class];
        foreach ($values as $value) {
            $class = self::MODULES[$value['module'] ?? ''] ?? null;
            if (! $class) return false;
            $record = $class::withTrashed()->where('user_id', $user->id)
                ->where('client_uuid', $value['record']['record_uuid'] ?? '')->first();
            if (! $record) return false;
            // Validate current references as well as the frozen response. A
            // receipt is not a permission grant for historical related data.
            foreach (['finca_id' => Finca::class, 'animal_id' => Animal::class,
                'potrero_id' => Potrero::class, 'salud_id' => \App\Models\Salud::class,
                'ingreso_venta_id' => \App\Models\Ingreso::class] as $field => $parent) {
                if ($record->$field && ! $parent::withTrashed()->where('user_id', $user->id)->whereKey($record->$field)->exists()) return false;
            }
            foreach ($references as $field => $parent) {
                if (! empty($value['record'][$field]) && ! $parent::withTrashed()->where('user_id', $user->id)->where('client_uuid', $value['record'][$field])->exists()) return false;
            }
        }
        return true;
    }
}
