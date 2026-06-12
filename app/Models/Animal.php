<?php

namespace App\Models;
 
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;
use App\Models\Potrero;
 
class Animal extends Model
{
    use HasFactory, SoftDeletes;
 
    protected $table = 'animales';
 
    protected $fillable = [
        'codigo',
        'numero',
        'nombre',
        'tipo',
        'sexo',
        'raza',
        'fecha_nacimiento',
        'peso_actual',
        'color',
        'observaciones',
        'estado',
        'finca_id',
        'potrero_id',
        'user_id',
        // Campos de venta (agregados por módulo Finanzas)
        'fecha_venta',
        'precio_venta',
        'ingreso_venta_id',
    ];
 
    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_venta'      => 'date',
        'peso_actual'      => 'decimal:2',
        'precio_venta'     => 'decimal:2',
        'user_id'          => 'integer',
    ];
 
    // ─── Relaciones ────────────────────────────────────────────────────────────
 
    public function finca()
    {
        return $this->belongsTo(Finca::class);
    }
 
    public function potrero()
    {
        return $this->belongsTo(Potrero::class);
    }
 
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function salud()
{
    return $this->hasMany(\App\Models\Salud::class);
}
 
    public function ingresoVenta()
    {
        return $this->belongsTo(Ingreso::class, 'ingreso_venta_id');
    }
 
    
    public function ingresos()
    {
        return $this->hasMany(Ingreso::class);
    }
 
    public function egresos()
    {
        return $this->hasMany(Egreso::class);
    }
 
    // ─── Scopes ────────────────────────────────────────────────────────────────
 
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }
 
    public function scopePorTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }
 
    public function scopePorFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }
 
    // ─── Accessors ─────────────────────────────────────────────────────────────
 
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return 'N/A';
        }
 
        $nacimiento = Carbon::parse($this->fecha_nacimiento);
        $hoy = Carbon::now();
 
        $años  = (int) $nacimiento->diffInYears($hoy);
        $meses = (int) $nacimiento->copy()->addYears($años)->diffInMonths($hoy);
        $dias  = (int) $nacimiento->copy()->addYears($años)->addMonths($meses)->diffInDays($hoy);
 
        if ($años > 0) {
            if ($meses > 0) {
                return $años . ' año' . ($años != 1 ? 's' : '') .
                       ' y ' . $meses . ' mes' . ($meses != 1 ? 'es' : '');
            }
            return $años . ' año' . ($años != 1 ? 's' : '');
        } elseif ($meses > 0) {
            if ($dias > 7) {
                return $meses . ' mes' . ($meses != 1 ? 'es' : '') .
                       ' y ' . $dias . ' día' . ($dias != 1 ? 's' : '');
            }
            return $meses . ' mes' . ($meses != 1 ? 'es' : '');
        } else {
            return $dias . ' día' . ($dias != 1 ? 's' : '');
        }
    }
 
    public function getEdadEnMesesAttribute(): int
    {
        if (!$this->fecha_nacimiento) return 0;
        return (int) Carbon::parse($this->fecha_nacimiento)->diffInMonths(Carbon::now());
    }
 
    public function getEdadEnDiasAttribute(): int
    {
        if (!$this->fecha_nacimiento) return 0;
        return (int) Carbon::parse($this->fecha_nacimiento)->diffInDays(Carbon::now());
    }
 
    public function getTipoFormateadoAttribute(): string
    {
        $tipos = [
            'vaca'    => 'Vaca',
            'ternero' => 'Ternero',
            'toro'    => 'Toro',
            'novilla' => 'Novilla',
        ];
        return $tipos[$this->tipo] ?? ucfirst($this->tipo);
    }
 
    public function getEstadoFormateadoAttribute(): string
    {
        return match ($this->estado) {
            'activo'  => 'Activo',
            'vendido' => 'Vendido',
            'muerto'  => 'Muerto',
            default   => ucfirst($this->estado),
        };
    }
 
    // ─── Métodos estáticos ────────────────────────────────────────────────────
 
    public static function generarCodigo(): string
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'ANI-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
 
    public static function getTipos(): array
    {
        return [
            'vaca'    => 'Vaca',
            'ternero' => 'Ternero',
            'toro'    => 'Toro',
            'novilla' => 'Novilla',
        ];
    }
 
    public static function getRazas(): array
    {
        return [
            'Holstein'  => 'Holstein',
            'Jersey'    => 'Jersey',
            'Brahman'   => 'Brahman',
            'Angus'     => 'Angus',
            'Simmental' => 'Simmental',
            'Charolais' => 'Charolais',
            'Gyr'       => 'Gyr',
            'Normando'  => 'Normando',
            'Criollo'   => 'Criollo',
            'Mestizo'   => 'Mestizo',
            'Otra'      => 'Otra',
        ];
    }
}