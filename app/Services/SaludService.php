<?php

namespace App\Services;

use App\Models\{Animal, Egreso, Finca, Salud, User};
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class SaludService
{
    public function write(User $user, string $type, array $input, ?Salud $record, ?string $uuid): Salud
    {
        // Salud currently has a separate, explicit "Registrar egreso" flow in Finanzas.
        // Do not invent a second automatic charge or alter an already linked expense.
        $linked = $record && Egreso::where('salud_id', $record->id)->lockForUpdate()->exists();
        if ($type === 'delete') {
            if ($linked) throw ValidationException::withMessages(['egreso' => 'Revisa el egreso vinculado en Finanzas antes de eliminar este evento.']);
            $record->delete();
            return $record;
        }
        $data = Validator::make($input, [
            'animal_id' => 'required|integer|min:1', 'finca_id' => 'required|integer|min:1',
            'tipo' => 'required|in:'.implode(',', array_keys(Salud::getTipos())),
            'nombre_producto' => 'required|string|max:255', 'enfermedad_prevenida' => 'nullable|string|max:255',
            'diagnostico' => 'nullable|string|max:2000', 'fecha_aplicacion' => 'required|date_format:Y-m-d|before_or_equal:today',
            'dosis' => 'nullable|numeric|min:0|max:999999.99', 'unidad_dosis' => 'nullable|string|max:20',
            'via_aplicacion' => 'nullable|string|max:50', 'lote_medicamento' => 'nullable|string|max:100',
            'laboratorio' => 'nullable|string|max:255', 'veterinario' => 'nullable|string|max:255',
            'costo' => 'nullable|numeric|min:0|max:99999999.99|decimal:0,2',
            'proxima_aplicacion' => 'nullable|date_format:Y-m-d|after:fecha_aplicacion', 'dias_carencia' => 'nullable|integer|min:0|max:36500',
            'estado' => 'required|in:completado,en_tratamiento,pendiente,cancelado', 'observaciones' => 'nullable|string|max:1000',
        ])->validate();
        $animal = Animal::where('user_id', $user->id)->lockForUpdate()->findOrFail($data['animal_id']);
        $farm = Finca::delUsuario($user->id)->lockForUpdate()->findOrFail($data['finca_id']);
        $sameHistory = $record && (int) $record->animal_id === $animal->id && (int) $record->finca_id === $farm->id;
        if (! $sameHistory && ((int) $animal->finca_id !== $farm->id || $animal->estado !== 'activo')) {
            throw ValidationException::withMessages(['animal_id' => 'Selecciona un animal activo que pertenezca a la finca autorizada.']);
        }
        if ($linked) {
            foreach (['animal_id', 'finca_id', 'costo', 'fecha_aplicacion', 'nombre_producto'] as $field) {
                $before = $record->$field instanceof \DateTimeInterface ? $record->$field->format('Y-m-d') : $record->$field;
                $equal = $field === 'costo' ? (float) ($data[$field] ?? 0) === (float) $before : (string) ($data[$field] ?? '') === (string) $before;
                if (! $equal) throw ValidationException::withMessages([$field => 'Este dato tiene un egreso vinculado. Requiere revisión en Finanzas con conexión.']);
            }
        }
        $data['fin_carencia'] = ! empty($data['dias_carencia']) ? Carbon::parse($data['fecha_aplicacion'])->addDays((int) $data['dias_carencia'])->format('Y-m-d') : null;
        $data['dias_carencia'] = (int) ($data['dias_carencia'] ?? 0);
        $data['categoria'] = Salud::getCategoriaPorTipo($data['tipo']);
        if (! $record) {
            $record = new Salud(['user_id' => $user->id, 'codigo' => app(WorkspaceJournal::class)->nextCode('salud')]);
            $record->client_uuid = $uuid;
        }
        $record->fill($data)->save();
        return $record;
    }
}
