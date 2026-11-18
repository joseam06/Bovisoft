<?php

namespace App\Services;

use App\Exceptions\ProduccionVersionConflict;
use App\Models\Animal;
use App\Models\Finca;
use App\Models\Produccion;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ProduccionService
{
    public function __construct(private readonly ProductionJournal $journal) {}

    public const TIPOS_PRODUCTIVOS = ['vaca', 'novilla'];

    public const REVISION_FECHA = 'Cambiar la fecha requiere revisión online porque el animal fue trasladado o está inactivo. Conserva la fecha original para guardar las demás correcciones.';

    public function animalesElegibles(User $user): Builder
    {
        return Animal::where('user_id', $user->id)
            ->where('estado', 'activo')
            ->whereIn('tipo', self::TIPOS_PRODUCTIVOS)
            ->whereHas('finca', fn ($query) => $query->delUsuario($user->id)->activas());
    }

    public function findForUser(User $user, int $id): Produccion
    {
        return Produccion::autorizadasPara($user->id)->with(['animal', 'finca'])->findOrFail($id);
    }

    public function requiereRevisionFecha(Produccion $registro): bool
    {
        return $registro->animal->estado !== 'activo'
            || (string) $registro->animal->finca_id !== (string) $registro->finca_id;
    }

    public function create(User $user, array $input, ?string $clientUuid = null): Produccion
    {
        $data = $this->validate($input);

        return $this->journal->transaction($user->id, function () use ($user, $data, $clientUuid) {
            $animal = Animal::where('user_id', $user->id)->lockForUpdate()->find($data['animal_id']);
            if (! $animal || $animal->estado !== 'activo' || ! in_array($animal->tipo, self::TIPOS_PRODUCTIVOS, true)) {
                throw ValidationException::withMessages(['animal_id' => 'Selecciona un animal activo y productivo de tu cuenta.']);
            }

            $finca = Finca::delUsuario($user->id)->lockForUpdate()->find($data['finca_id']);
            if (! $finca || ! $finca->activa) {
                throw ValidationException::withMessages(['finca_id' => 'Selecciona una finca activa de tu cuenta.']);
            }

            Gate::forUser($user)->authorize('view', $finca);
            if ((string) $animal->finca_id !== (string) $finca->id) {
                throw ValidationException::withMessages(['finca_id' => 'El animal no pertenece a la finca seleccionada.']);
            }

            $record = new Produccion($data + ['user_id' => $user->id, 'codigo' => $this->journal->nextCode()]);
            if ($clientUuid !== null) {
                $record->client_uuid = $clientUuid;
            }
            $record->save();
            $this->journal->record($record);

            return $record;
        });
    }

    public function update(User $user, int $id, array $input): Produccion
    {
        return $this->journal->transaction($user->id, function () use ($user, $id, $input) {
            $registro = Produccion::autorizadasPara($user->id)->lockForUpdate()->findOrFail($id);
            // Recheck the original references while locked; inactivity or a move
            // does not invalidate the original attribution of a historical record.
            $animal = Animal::where('user_id', $user->id)->lockForUpdate()->findOrFail($registro->animal_id);
            $finca = Finca::delUsuario($user->id)->lockForUpdate()->findOrFail($registro->finca_id);
            Gate::forUser($user)->authorize('view', $finca);
            $registro->setRelation('animal', $animal);
            $registro->setRelation('finca', $finca);

            $data = $this->validate($input, updating: true);
            if ((int) $data['base_version'] !== $registro->version) {
                throw new ProduccionVersionConflict($registro);
            }
            $errors = [];
            foreach (['animal_id', 'finca_id'] as $field) {
                if (array_key_exists($field, $data) && (string) $data[$field] !== (string) $registro->$field) {
                    $errors[$field] = 'Se debe conservar el animal y la finca originales del registro.';
                }
            }

            if ($data['fecha'] !== $registro->fecha->format('Y-m-d') && $this->requiereRevisionFecha($registro)) {
                $errors['fecha'] = self::REVISION_FECHA;
            }

            if ($errors) {
                throw ValidationException::withMessages($errors);
            }

            unset($data['animal_id'], $data['finca_id'], $data['base_version']);
            $registro->version++;
            $registro->update($data);
            $this->journal->record($registro);

            return $registro;
        });
    }

    public function delete(User $user, int $id, array $input): void
    {
        $this->journal->transaction($user->id, function () use ($user, $id, $input) {
            $record = Produccion::autorizadasPara($user->id)->lockForUpdate()->findOrFail($id);
            $data = Validator::make($input, ['base_version' => 'required|integer|min:1'])->validate();
            if ((int) $data['base_version'] !== $record->version) {
                throw new ProduccionVersionConflict($record);
            }
            $record->version++;
            $record->save();
            $record->delete();
            $this->journal->record($record, 'remove');
        });
    }

    private function validate(array $input, bool $updating = false): array
    {
        $referenceRule = $updating ? 'sometimes|required|integer|min:1' : 'required|integer|min:1';

        $rules = [
            'animal_id' => $referenceRule,
            'finca_id' => $referenceRule,
            'fecha' => 'required|date_format:Y-m-d|before_or_equal:today',
            'sesion' => 'nullable|in:manana,tarde,noche',
            'litros' => 'required|numeric|min:0.01|max:999.99|decimal:0,2',
            'calidad' => 'nullable|in:normal,buena,excelente,rechazada',
            'observaciones' => 'nullable|string|max:1000',
        ];
        if ($updating) {
            $rules['base_version'] = 'required|integer|min:1';
        }

        return Validator::make($input, $rules, [
            'animal_id.required' => 'Debes seleccionar un animal.',
            'finca_id.required' => 'Debes seleccionar una finca.',
            'fecha.required' => 'La fecha es obligatoria.',
            'fecha.date_format' => 'Indica una fecha válida con formato año-mes-día.',
            'fecha.before_or_equal' => 'La fecha no puede ser futura.',
            'litros.required' => 'Los litros producidos son obligatorios.',
            'litros.min' => 'La producción debe ser mayor a 0 litros.',
            'litros.max' => 'El valor máximo permitido es 999.99 litros.',
            'litros.decimal' => 'Los litros admiten como máximo dos decimales.',
        ])->validate();
    }
}
