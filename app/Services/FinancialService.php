<?php

namespace App\Services;

use App\Models\{Animal, Egreso, Finca, Ingreso, Salud, User};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FinancialService
{
    public static function authorized(string $class, int $owner)
    {
        return $class::autorizadasPara($owner);
    }

    public function write(User $user, string $module, string $type, array $input, ?Model $record, ?string $uuid): Model
    {
        if (\Illuminate\Support\Facades\DB::transactionLevel() === 0) {
            return app(WorkspaceJournal::class)->transaction($user->id, fn () => $this->write($user,$module,$type,$input,$record,$uuid));
        }
        // Called by WorkspaceRecords inside the shared owner transaction.
        $sale = $record instanceof Ingreso && $record->tipo === 'venta_animal' && $record->animal_id;
        if ($type === 'delete') {
            if ($sale) {
                $animal = Animal::where('user_id', $user->id)->lockForUpdate()->findOrFail($record->animal_id);
                if ((int) $animal->ingreso_venta_id !== $record->id || $animal->estado !== 'vendido') {
                    throw ValidationException::withMessages(['venta' => 'La venta no coincide con el vínculo actual del animal. Requiere revisión antes de revertirla.']);
                }
                $animal->fill(['estado' => 'activo', 'fecha_venta' => null, 'precio_venta' => null, 'ingreso_venta_id' => null])->save();
                // Free the nullable unique key in the same transaction; keep the original attribution.
                $record->active_sale_animal_id = null;
                \Illuminate\Support\Facades\DB::table('ingresos')->where('id', $record->id)->update(['active_sale_animal_id' => null]);
            }
            $record->delete();
            return $record;
        }
        if ($record) throw ValidationException::withMessages(['movimiento' => 'BoviSoft no tiene edición financiera implementada. Revisa el movimiento y su reversión desde sus acciones existentes.']);
        $income = $module === 'ingresos';
        $rules = ['finca_id' => 'required|integer|min:1', 'animal_id' => 'nullable|integer|min:1',
            'monto' => 'required|numeric|min:0|max:999999999999.99|decimal:0,2', 'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'descripcion' => 'nullable|string|max:255', 'observaciones' => 'nullable|string|max:1000'];
        $rules += $income ? ['tipo' => 'required|in:'.implode(',', array_keys(Ingreso::getTipos())), 'comprador_nombre' => 'nullable|string|max:255',
            'comprador_telefono' => 'nullable|string|max:50', 'comprador_documento' => 'nullable|string|max:50', 'animal_base_version' => 'nullable|integer|min:1']
            : ['categoria' => 'required|in:'.implode(',', array_keys(Egreso::getCategorias())), 'salud_id' => 'nullable|integer|min:1'];
        $data = Validator::make($input, $rules)->validate();
        $farm = Finca::delUsuario($user->id)->lockForUpdate()->findOrFail($data['finca_id']);
        $animal = empty($data['animal_id']) ? null : Animal::where('user_id', $user->id)->where('finca_id', $farm->id)->lockForUpdate()->findOrFail($data['animal_id']);
        if (! empty($data['salud_id'])) {
            $health = Salud::where('user_id', $user->id)->where('finca_id', $farm->id)->lockForUpdate()->findOrFail($data['salud_id']);
            if ($animal && (int) $health->animal_id !== $animal->id) throw ValidationException::withMessages(['salud_id' => 'El evento no corresponde al animal seleccionado.']);
            Animal::where('user_id', $user->id)->findOrFail($health->animal_id);
        }
        $selling = $income && $data['tipo'] === 'venta_animal' && $animal;
        if ($selling) {
            if ($animal->ingreso_venta_id || $animal->estado === 'vendido' || Ingreso::where('animal_id', $animal->id)->where('tipo', 'venta_animal')->exists()) {
                throw ValidationException::withMessages(['animal_id' => 'Este animal ya tiene una venta activa. No se reemplazará su vínculo.']);
            }
            if (! isset($data['animal_base_version']) || (int) $data['animal_base_version'] !== (int) $animal->version) {
                throw ValidationException::withMessages(['animal_base_version' => 'El animal cambió desde que preparaste la venta. Revisa la ficha actual y vuelve a preparar la operación.']);
            }
        }
        unset($data['animal_base_version']);
        $class = $income ? Ingreso::class : Egreso::class;
        $record = new $class($data + ['user_id' => $user->id, 'codigo' => app(WorkspaceJournal::class)->nextCode($module)]);
        if ($selling) $record->active_sale_animal_id = $animal->id;
        $record->client_uuid = $uuid;
        $record->save();
        if ($selling) $animal->fill(['estado' => 'vendido', 'fecha_venta' => $data['fecha'], 'precio_venta' => $data['monto'],
            'ingreso_venta_id' => $record->id, 'potrero_id' => null])->save();
        return $record;
    }

    public function submit(User $user, string $module, string $type, array $input, ?Model $record = null): array
    {
        $keys = Validator::make($input, ['operation_uuid' => 'required|uuid', 'record_uuid' => 'required|uuid', 'device_uuid' => 'required|uuid'])->validate();
        $data = array_intersect_key($input, array_flip(['finca_id','animal_id','salud_id','tipo','categoria','monto','fecha','descripcion','observaciones','comprador_nombre','comprador_telefono','comprador_documento','animal_base_version']));
        if ($type !== 'delete') {
            $farm = Finca::delUsuario($user->id)->findOrFail($input['finca_id'] ?? 0);$data['finca_uuid'] = $farm->client_uuid;unset($data['finca_id']);
            foreach (['animal' => Animal::class, 'salud' => Salud::class] as $field => $class) {
                $related = empty($input[$field.'_id']) ? null : $class::where('user_id', $user->id)->where('finca_id', $farm->id)->findOrFail($input[$field.'_id']);
                $data[$field.'_uuid'] = $related?->client_uuid;unset($data[$field.'_id']);
            }
        }
        $operation = ['operation_uuid' => $keys['operation_uuid'], 'record_uuid' => $record?->client_uuid ?? $keys['record_uuid'],
            'module' => $module, 'type' => $type, 'data' => $type === 'delete' ? [] : $data];
        if ($type !== 'create') $operation['base_version'] = $input['base_version'] ?? null;
        return app(WorkspaceSync::class)->apply($user, $keys['device_uuid'], $operation);
    }
}
