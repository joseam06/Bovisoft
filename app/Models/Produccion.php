<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Produccion extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'producciones';

    protected $fillable = [
        'codigo', 'animal_id', 'finca_id', 'user_id',
        'fecha', 'sesion', 'litros', 'calidad', 'observaciones',
    ];

    protected $casts = [
        'fecha'  => 'date',
        'litros' => 'decimal:2',
    ];

    // ─── Relaciones ────────────────────────────────────────────────────────────

    public function animal() { return $this->belongsTo(Animal::class); }
    public function finca()  { return $this->belongsTo(Finca::class); }
    public function user()   { return $this->belongsTo(User::class); }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeDelUsuario($query)
    {
        return $query->where('user_id', auth()->id());
    }

    public function scopeHoy($query)
    {
        return $query->whereDate('fecha', Carbon::today());
    }

    public function scopeEstaSemana($query)
    {
        return $query->whereBetween('fecha', [
            Carbon::now()->startOfWeek(),
            Carbon::now()->endOfWeek(),
        ]);
    }

    public function scopeEsteMes($query)
    {
        return $query->whereMonth('fecha', Carbon::now()->month)
                     ->whereYear('fecha', Carbon::now()->year);
    }

    // ─── Código autogenerado ───────────────────────────────────────────────────

    public static function generarCodigo(): string
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'PROD-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    // ─── Catálogos estáticos ───────────────────────────────────────────────────

    public static function getSesiones(): array
    {
        return [
            'manana' => 'Mañana',
            'tarde'  => 'Tarde',
            'noche'  => 'Noche',
        ];
    }

    public static function getCalidades(): array
    {
        return [
            'normal'    => 'Normal',
            'buena'     => 'Buena',
            'excelente' => 'Excelente',
            'rechazada' => 'Rechazada',
        ];
    }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    public function getSesionFormateadaAttribute(): string
    {
        return self::getSesiones()[$this->sesion] ?? '—';
    }

    public function getCalidadFormateadaAttribute(): string
    {
        return self::getCalidades()[$this->calidad] ?? '—';
    }
}