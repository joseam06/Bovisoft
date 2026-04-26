<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Potrero;

class Finca extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'nombre',
        'codigo',
        'area',
        'descripcion',
        'direccion',
        'municipio',
        'departamento',
        'latitud',
        'longitud',
        'activa',
        'user_id',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'latitud' => 'decimal:8',
        'longitud' => 'decimal:8',
        'activa' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function animales()
    {
        return $this->hasMany(Animal::class);
    }

   public function potreros()
{
    return $this->hasMany(Potrero::class);
}
 
// Accessor para total de potreros
public function getTotalPotrerosAttribute()
{
    return $this->potreros()->count();
}
 

    public function scopeActivas($query)
    {
        return $query->where('activa', true);
    }

    public function scopeConUbicacion($query)
    {
        return $query->whereNotNull('latitud')->whereNotNull('longitud');
    }

    public function getTotalAnimalesAttribute()
    {
        return $this->animales()->count();
    }

    public function tieneUbicacion()
    {
        return !is_null($this->latitud) && !is_null($this->longitud);
    }

    public static function generarCodigo()
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'FIN-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }
}