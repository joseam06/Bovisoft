<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends Model
{
    use HasFactory, SoftDeletes, \App\Models\Concerns\SyncsWorkspace {
        \App\Models\Concerns\SyncsWorkspace::save as private saveWorkspace;
        \App\Models\Concerns\SyncsWorkspace::delete as private deleteWorkspace;
    }
    use \App\Models\Concerns\AuthorizesFinance;

    // Only the existing online Salud link participates in Workspace. This does
    // not make expenses an offline module or generate additional charges.
    public function save(array $options = [])
    {
        $ids = array_values(array_unique(array_filter([$this->getOriginal('salud_id'), $this->salud_id])));
        if (! $ids) return $this->saveWorkspace($options);

        return app(\App\Services\WorkspaceJournal::class)->transaction((int) $this->user_id, function () use ($options, $ids) {
            $records = Salud::withTrashed()->where('user_id', $this->user_id)->whereIn('id', $ids)->orderBy('id')->lockForUpdate()->get();
            $linked = $this->salud_id ? $records->firstWhere('id', $this->salud_id) : null;
            if ($this->salud_id && (! $linked || $linked->trashed()
                || ! Finca::delUsuario($this->user_id)->whereKey($linked->finca_id)->exists()
                || ! Animal::where('user_id', $this->user_id)->whereKey($linked->animal_id)->exists()
                || (int) $linked->finca_id !== (int) $this->finca_id
                || ($this->animal_id && (int) $linked->animal_id !== (int) $this->animal_id))) {
                throw \Illuminate\Validation\ValidationException::withMessages(['salud_id' => 'El evento sanitario no está disponible o no corresponde a esta finca y animal.']);
            }
            $saved = $this->saveWorkspace($options);
            if ($saved) foreach ($records as $record) $record->touch();
            return $saved;
        });
    }

    public function delete()
    {
        if (! $this->salud_id) return $this->deleteWorkspace();
        return app(\App\Services\WorkspaceJournal::class)->transaction((int) $this->user_id, function () {
            $health = Salud::withTrashed()->where('user_id', $this->user_id)->lockForUpdate()->find($this->salud_id);
            $deleted = $this->deleteWorkspace();
            if ($deleted && $health) $health->touch();
            return $deleted;
        });
    }

    protected $table = 'egresos';

    protected $fillable = [
        'codigo',
        'user_id',
        'finca_id',
        'animal_id',
        'categoria',
        'monto',
        'fecha',
        'descripcion',
        'observaciones',
        'salud_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'monto' => 'decimal:2',
    ];

    // ─── Relaciones ────────────────────────────────────────────────────────────

    public function user()   { return $this->belongsTo(User::class); }
    public function finca()  { return $this->belongsTo(Finca::class); }
    public function animal() { return $this->belongsTo(Animal::class); }
    public function salud()  { return $this->belongsTo(Salud::class); }

    // ─── Código auto-generado ─────────────────────────────────────────────────

    public static function generarCodigo(): string
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'EGR-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    // ─── Catálogos estáticos ──────────────────────────────────────────────────

    public static function getCategorias(): array
    {
        return [
            'salud_animal'       => 'Salud Animal',
            'compra_animal'      => 'Compra de Animal',
            'insumos_alimentacion' => 'Insumos y Alimentación',
            'mano_obra'          => 'Mano de Obra',
            'mantenimiento'      => 'Mantenimiento',
            'otro'               => 'Otro Gasto',
        ];
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getCategoriaFormateadaAttribute(): string
    {
        return self::getCategorias()[$this->categoria] ?? ucfirst($this->categoria);
    }
}
