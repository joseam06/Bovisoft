<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Potrero extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'codigo',
        'nombre',
        'area',
        'tipo_pasto',
        'capacidad_animales',
        'animales_actuales',
        'estado',
        'fecha_ultima_rotacion',
        'dias_descanso',
        'observaciones',
        'finca_id',
        'user_id',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'fecha_ultima_rotacion' => 'date',
        'capacidad_animales' => 'integer',
        'animales_actuales' => 'integer',
        'dias_descanso' => 'integer',
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

    public function animales()
    {
        return $this->hasMany(Animal::class);
    }

    // Scopes
    public function scopeActivos($query)
    {
        return $query->where('estado', 'activo');
    }

    public function scopeEnDescanso($query)
    {
        return $query->where('estado', 'en_descanso');
    }

    public function scopePorFinca($query, $fincaId)
    {
        return $query->where('finca_id', $fincaId);
    }

    // Accessors
    public function getDisponibilidadAttribute()
    {
        if (!$this->capacidad_animales) {
            return 'N/A';
        }

        $disponibles = $this->capacidad_animales - $this->animales_actuales;
        return $disponibles > 0 ? $disponibles : 0;
    }

    public function getPorcentajeOcupacionAttribute()
    {
        if (!$this->capacidad_animales || $this->capacidad_animales == 0) {
            return 0;
        }

        return round(($this->animales_actuales / $this->capacidad_animales) * 100, 1);
    }

    public function getEstadoFormateadoAttribute()
    {
        $estados = [
            'activo' => 'Activo',
            'en_descanso' => 'En Descanso',
            'en_mantenimiento' => 'En Mantenimiento',
        ];

        return $estados[$this->estado] ?? ucfirst($this->estado);
    }

    public function getDiasDesdeUltimaRotacionAttribute()
    {
        if (!$this->fecha_ultima_rotacion) {
            return null;
        }

        return Carbon::parse($this->fecha_ultima_rotacion)->diffInDays(Carbon::now());
    }

    public function getRequiereRotacionAttribute()
    {
        if (!$this->fecha_ultima_rotacion || !$this->dias_descanso) {
            return false;
        }

        $diasTranscurridos = $this->dias_desde_ultima_rotacion;
        return $diasTranscurridos >= $this->dias_descanso;
    }

    public function getProximaRotacionAttribute()
    {
        if (!$this->fecha_ultima_rotacion || !$this->dias_descanso) {
            return 'No programada';
        }

        $proximaFecha = Carbon::parse($this->fecha_ultima_rotacion)->addDays($this->dias_descanso);
        
        if ($proximaFecha->isPast()) {
            return 'Requiere rotación';
        }

        $diasRestantes = Carbon::now()->diffInDays($proximaFecha);
        
        if ($diasRestantes == 0) {
            return 'Hoy';
        } elseif ($diasRestantes == 1) {
            return 'Mañana';
        } else {
            return "En {$diasRestantes} días";
        }
    }

    // Método estático para generar código
    public static function generarCodigo()
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'POT-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    // Tipos de pasto disponibles
    public static function getTiposPasto()
    {
        return [
            'Brachiaria' => 'Brachiaria',
            'Estrella' => 'Estrella',
            'Guinea' => 'Guinea',
            'Angleton' => 'Angleton',
            'Pangola' => 'Pangola',
            'Puntero' => 'Puntero',
            'Kikuyo' => 'Kikuyo',
            'Maralfalfa' => 'Maralfalfa',
            'King Grass' => 'King Grass',
            'Mixto' => 'Mixto',
            'Otro' => 'Otro',
        ];
    }

    // Estados disponibles
    public static function getEstados()
    {
        return [
            'activo' => 'Activo',
            'en_descanso' => 'En Descanso',
            'en_mantenimiento' => 'En Mantenimiento',
        ];
    }
}