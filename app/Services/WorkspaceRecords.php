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
        $query = $module === 'fincas' ? Finca::query() : Animal::query();
        if ($trashed) {
            $query->withTrashed();
        }
        $record = $query->where('user_id', $user->id)->where($uuid ? 'client_uuid' : 'id', $key)->lockForUpdate()->firstOrFail();
        if ($record instanceof Finca) {
            Gate::forUser($user)->authorize('view', $record);
        } else {
            $finca = Finca::delUsuario($user->id)->findOrFail($record->finca_id);
            Gate::forUser($user)->authorize('view', $finca);
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
                $oldPotrero = $record instanceof Animal ? $record->potrero_id : null;
                $record->delete();
                $this->recount($user, [$oldPotrero]);

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
            $oldPotrero = null;
            if ($module === 'animales') {
                $finca = Finca::delUsuario($user->id)->lockForUpdate()->find($data['finca_id']);
                if (! $finca || (($creating || (int) $record->finca_id !== (int) $finca->id) && ! $finca->activa)) {
                    throw ValidationException::withMessages(['finca_id' => 'Selecciona una finca activa y autorizada de tu cuenta.']);
                }
                Gate::forUser($user)->authorize('view', $finca);
                if (! empty($data['potrero_id']) && ! Potrero::where('user_id', $user->id)->where('finca_id', $finca->id)->whereKey($data['potrero_id'])->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages(['potrero_id' => 'El potrero no pertenece a la finca seleccionada o no está autorizado.']);
                }
                $oldPotrero = $record?->potrero_id;
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
            if ($record instanceof Animal) {
                $this->recount($user, [$oldPotrero, $record->potrero_id]);
            }

            return $record;
        });
    }

    private function recount(User $user, array $ids): void
    {
        foreach (array_unique(array_filter($ids)) as $id) {
            Potrero::where('user_id', $user->id)->whereKey($id)->update([
                'animales_actuales' => Animal::where('user_id', $user->id)->where('potrero_id', $id)->where('estado', 'activo')->count(),
            ]);
        }
    }
}
