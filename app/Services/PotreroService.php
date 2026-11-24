<?php

namespace App\Services;

use App\Models\{Finca, Potrero, User};
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class PotreroService
{
    public function write(User $user, string $type, array $input, ?Potrero $record, ?string $uuid): Potrero
    {
        if ($type === 'delete') {
            if ($record->animales()->exists()) throw ValidationException::withMessages(['potrero' => 'Reasigna primero los animales de este potrero.']);
            $record->delete();
            return $record;
        }
        $data = Validator::make($input, [
            'nombre' => 'required|string|max:255', 'area' => 'nullable|numeric|min:0|max:99999.99',
            'tipo_pasto' => 'nullable|string|max:100', 'capacidad_animales' => 'nullable|integer|min:0|max:9999',
            'dias_descanso' => 'nullable|integer|min:0|max:365', 'fecha_ultima_rotacion' => 'nullable|date_format:Y-m-d|before_or_equal:today',
            'observaciones' => 'nullable|string|max:20000', 'finca_id' => 'required|integer|min:1',
            'estado' => 'required|in:activo,en_descanso,en_mantenimiento',
        ])->validate();
        $farm = Finca::delUsuario($user->id)->lockForUpdate()->findOrFail($data['finca_id']);
        if ($record && (int) $record->finca_id !== $farm->id && $record->animales()->exists()) {
            throw ValidationException::withMessages(['finca_id' => 'Reasigna los animales antes de trasladar este potrero a otra finca.']);
        }
        $occupied = $record ? $record->animales()->where('estado', 'activo')->count() : 0;
        if (! empty($data['capacidad_animales']) && $data['capacidad_animales'] < $occupied) {
            throw ValidationException::withMessages(['capacidad_animales' => 'La capacidad no puede ser menor que la ocupación actual.']);
        }
        if (! $record) {
            $record = new Potrero(['user_id' => $user->id, 'codigo' => app(WorkspaceJournal::class)->nextCode('potreros')]);
            $record->client_uuid = $uuid;
        }
        $record->fill($data + ['animales_actuales' => $occupied])->save();
        return $record;
    }
}
