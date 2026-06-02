<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Egreso extends Model
{
    use HasFactory, SoftDeletes;

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