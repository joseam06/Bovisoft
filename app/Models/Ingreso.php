<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingreso extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'ingresos';

    protected $fillable = [
        'codigo',
        'user_id',
        'finca_id',
        'animal_id',
        'tipo',
        'monto',
        'fecha',
        'descripcion',
        'comprador_nombre',
        'comprador_telefono',
        'comprador_documento',
        'observaciones',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'monto'  => 'decimal:2',
    ];

    // ─── Relaciones ────────────────────────────────────────────────────────────

    public function user()   { return $this->belongsTo(User::class); }
    public function finca()  { return $this->belongsTo(Finca::class); }
    public function animal() { return $this->belongsTo(Animal::class); }

    // ─── Código auto-generado ─────────────────────────────────────────────────

    public static function generarCodigo(): string
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'ING-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    // ─── Catálogos estáticos ──────────────────────────────────────────────────

    public static function getTipos(): array
    {
        return [
            'venta_animal' => 'Venta de Animal',
            'venta_leche'  => 'Venta de Leche',
            'subsidio'     => 'Subsidio',
            'arrendamiento'=> 'Arrendamiento de Potrero',
            'otro'         => 'Otro Ingreso',
        ];
    }

    // ─── Accessors ────────────────────────────────────────────────────────────

    public function getTipoFormateadoAttribute(): string
    {
        return self::getTipos()[$this->tipo] ?? ucfirst($this->tipo);
    }
}