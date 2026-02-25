<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Animal extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'animales';

    protected $fillable = [
        'codigo',
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
        'user_id',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'peso_actual' => 'decimal:2',
    ];

    // Relaciones
    public function finca()
    {
        return $this->belongsTo(Finca::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
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

    // Accessors
    public function getEdadAttribute()
    {
        if (!$this->fecha_nacimiento) {
            return null;
        }

        $nacimiento = Carbon::parse($this->fecha_nacimiento);
        $ahora = Carbon::now();
        
        $años = $nacimiento->diffInYears($ahora);
        $meses = $nacimiento->copy()->addYears($años)->diffInMonths($ahora);
        
        if ($años > 0) {
            return $años . ' año' . ($años > 1 ? 's' : '') . 
                   ($meses > 0 ? ' y ' . $meses . ' mes' . ($meses > 1 ? 'es' : '') : '');
        }
        
        return $meses . ' mes' . ($meses > 1 ? 'es' : '');
    }

    public function getTipoFormateadoAttribute()
    {
        $tipos = [
            'vaca' => 'Vaca',
            'ternero' => 'Ternero',
            'toro' => 'Toro',
            'novilla' => 'Novilla',
        ];

        return $tipos[$this->tipo] ?? $this->tipo;
    }

    // Método estático para generar código
    public static function generarCodigo()
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'ANI-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    // Tipos y opciones estáticas
    public static function getTipos()
    {
        return [
            'vaca' => 'Vaca',
            'ternero' => 'Ternero',
            'toro' => 'Toro',
            'novilla' => 'Novilla',
        ];
    }

    public static function getRazas()
    {
        return [
            'Holstein' => 'Holstein',
            'Jersey' => 'Jersey',
            'Brahman' => 'Brahman',
            'Angus' => 'Angus',
            'Simmental' => 'Simmental',
            'Charolais' => 'Charolais',
            'Gyr' => 'Gyr',
            'Normando' => 'Normando',
            'Criollo' => 'Criollo',
            'Mestizo' => 'Mestizo',
            'Otra' => 'Otra',
        ];
    }
}