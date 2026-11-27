<?php

namespace App\Services;

use App\Exceptions\WorkspaceVersionConflict;
use App\Models\Animal;
use App\Models\Finca;
use App\Models\Potrero;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class WorkspaceRecords
{
    public function __construct(private readonly WorkspaceJournal $journal) {}

    public function find(User $user, string $module, int|string $key, bool $uuid = false, bool $trashed = false): Model
    {
        $class = WorkspaceModules::MODELS[$module] ?? throw new \InvalidArgumentException('Módulo no disponible.');
        $query = $class::query();
        if (in_array($module,['ingresos','egresos'],true)) $query=FinancialService::authorized($class,$user->id);
        if ($trashed) {
            $query->withTrashed();
        }
        $record = $query->where('user_id', $user->id)->where($uuid ? 'client_uuid' : 'id', $key)->lockForUpdate()->firstOrFail();
        if ($module === 'perfil') return $record;
        if (in_array($module, ['ingresos','egresos'], true)) {
            if ($record->animal_id) Animal::where('user_id', $user->id)->findOrFail($record->animal_id);
            if ($module === 'egresos' && $record->salud_id) \App\Models\Salud::where('user_id', $user->id)->findOrFail($record->salud_id);
        }
        if ($record instanceof Finca) {
            Gate::forUser($user)->authorize('view', $record);
        } else {
            $finca = Finca::delUsuario($user->id)->findOrFail($record->finca_id);
            Gate::forUser($user)->authorize('view', $finca);
            if (in_array($module, ['produccion', 'salud'], true)) Animal::where('user_id', $user->id)->findOrFail($record->animal_id);
        }

        return $record;
    }

    public function write(User $user, string $module, string $type, array $input, ?Model $record = null, ?string $uuid = null): Model
    {
        return $this->journal->transaction($user->id, function () use ($user, $module, $type, $input, $record, $uuid) {
            if ($record) {
                $record = $this->find($user, $module, $record->id);
                Validator::make($input, ['base_version' => 'required|integer|min:1'], [
                    'base_version.required' => 'Vuelve a abrir la edición para cargar la versión actual antes de guardar.',
                ])->validate();
                if ((int) $input['base_version'] !== (int) $record->version) {
                    throw new WorkspaceVersionConflict($record);
                }
            }
            if ($module === 'produccion') {
                $service = app(ProduccionService::class);
                try {
                    if ($type === 'create') return $service->create($user, $input, $uuid);
                    if ($type === 'update') return $service->update($user, $record->id, $input);
                    $service->delete($user, $record->id, $input);
                    return \App\Models\Produccion::withTrashed()->findOrFail($record->id);
                } catch (\App\Exceptions\ProduccionVersionConflict $error) {
                    throw new WorkspaceVersionConflict($error->current);
                }
            }
            if ($module === 'perfil') {
                if ($type !== 'update') throw ValidationException::withMessages(['perfil'=>'Solo se admite actualizar el nombre de la cuenta activa.']);
                $data = Validator::make($input, ['name'=>'required|string|max:255','email'=>'prohibited','password'=>'prohibited','current_password'=>'prohibited','photo'=>'prohibited'])->validate();
                $user->name = $data['name'];$user->save();
                $fresh=\App\Models\WorkspaceProfile::where('user_id',$user->id)->firstOrFail();
                if ((int)$fresh->version === (int)$record->version) $fresh->touch();
                return $fresh;
            }
            if ($module === 'potreros') return app(PotreroService::class)->write($user, $type, $input, $record, $uuid);
            if ($module === 'salud') return app(SaludService::class)->write($user, $type, $input, $record, $uuid);
            if (in_array($module, ['ingresos','egresos'], true)) return app(FinancialService::class)->write($user, $module, $type, $input, $record, $uuid);
            if ($type === 'toggle') {
                $record->activa = ! $record->activa;
                $record->save();

                return $record;
            }
            if ($type === 'delete') {
                if ($record instanceof Finca && ($record->animales()->exists() || $record->potreros()->exists())) {
                    throw ValidationException::withMessages(['finca' => 'Mueve o elimina primero los animales y potreros de esta finca.']);
                }
                $field = $record instanceof Finca ? 'finca_id' : 'animal_id';
                foreach (['producciones', 'salud', 'ingresos', 'egresos'] as $table) {
                    if (DB::table($table)->where($field, $record->id)->exists()) {
                        throw ValidationException::withMessages(['historial' => 'El registro tiene historial relacionado y se conserva. Para retirarlo del inventario cambia su estado; no se eliminará su historial.']);
                    }
                }
                $record->delete();

                return $record;
            }
            $creating = $record === null;
            $rules = $module === 'fincas' ? [
                'nombre' => 'required|string|max:255', 'area' => 'nullable|numeric|min:0|max:99999999.99',
                'descripcion' => 'nullable|string|max:20000', 'direccion' => 'nullable|string|max:255',
                'municipio' => 'nullable|string|max:100', 'departamento' => 'nullable|string|max:100',
                'latitud' => 'required|numeric|between:-90,90', 'longitud' => 'required|numeric|between:-180,180',
                'activa' => 'sometimes|boolean',
            ] : [
                'numero' => 'nullable|string|max:100', 'nombre' => 'nullable|string|max:255',
                'tipo' => 'required|in:vaca,ternero,toro,novilla', 'sexo' => 'required|in:macho,hembra',
                'raza' => 'nullable|string|max:100', 'fecha_nacimiento' => 'nullable|date_format:Y-m-d|before_or_equal:today',
                'peso_actual' => 'nullable|numeric|min:0|max:9999.99', 'color' => 'nullable|string|max:100',
                'observaciones' => 'nullable|string|max:20000', 'finca_id' => 'required|integer|min:1',
                'potrero_id' => 'nullable|integer|min:1', 'estado' => 'sometimes|required|string|max:50',
            ];
            if ($creating) {
                $rules['codigo'] = ['sometimes', 'required', 'string', 'max:255', Rule::unique($module, 'codigo')];
            }
            $data = Validator::make($input, $rules)->validate();
            if ($module === 'animales') {
                $finca = Finca::delUsuario($user->id)->lockForUpdate()->find($data['finca_id']);
                if (! $finca || (($creating || (int) $record->finca_id !== (int) $finca->id) && ! $finca->activa)) {
                    throw ValidationException::withMessages(['finca_id' => 'Selecciona una finca activa y autorizada de tu cuenta.']);
                }
                Gate::forUser($user)->authorize('view', $finca);
                if (! empty($data['potrero_id']) && ! Potrero::where('user_id', $user->id)->where('finca_id', $finca->id)->whereKey($data['potrero_id'])->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['potrero_id' => 'El potrero no pertenece a la finca seleccionada o no está autorizado.']);
                }
                if (! empty($data['potrero_id'])) {
                    $potrero = Potrero::where('user_id', $user->id)->lockForUpdate()->findOrFail($data['potrero_id']);
                    $assigning = ! $record || (int) $record->potrero_id !== $potrero->id || ($record->estado !== 'activo' && ($data['estado'] ?? '') === 'activo');
                    $count = Animal::where('user_id', $user->id)->where('potrero_id', $potrero->id)->where('estado', 'activo')->count();
                    if ($assigning && ($potrero->estado !== 'activo' || ($potrero->capacidad_animales && $count >= $potrero->capacidad_animales))) {
                        throw ValidationException::withMessages(['potrero_id' => 'El potrero no está activo o no tiene capacidad disponible.']);
                    }
                }
                $data['potrero_id'] ??= null;
                if ($record?->ingreso_venta_id && isset($data['estado']) && $data['estado'] !== $record->estado) {
                    throw ValidationException::withMessages(['estado' => 'Revisa la venta en Finanzas antes de cambiar el estado de este animal.']);
                }
            }
            if ($creating) {
                $class = $module === 'fincas' ? Finca::class : Animal::class;
                $data['codigo'] ??= $this->journal->nextCode($module);
                $record = new $class($data + ['user_id' => $user->id] + ($module === 'fincas' ? ['activa' => true] : ['estado' => 'activo']));
                if ($uuid) {
                    $record->client_uuid = $uuid;
                }
                $record->save();
            } else {
                $record->fill($data)->save();
            }

            return $record;
        });
    }

    public function refreshOccupancy(int $owner, array $ids): void
    {
        foreach (array_unique(array_filter($ids)) as $id) {
            $potrero = Potrero::where('user_id', $owner)->lockForUpdate()->find($id);
            if (! $potrero) continue;
            $count = Animal::where('user_id', $owner)->where('potrero_id', $id)->where('estado', 'activo')->count();
            if ($potrero->animales_actuales !== $count) $potrero->fill(['animales_actuales' => $count])->save();
        }
    }
}
