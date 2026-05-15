<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Salud extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'salud';

    protected $fillable = [
        'codigo', 'animal_id', 'finca_id', 'user_id',
        'tipo', 'categoria',                        // ← nuevo campo
        'nombre_producto', 'enfermedad_prevenida', 'diagnostico',
        'fecha_aplicacion', 'dosis', 'unidad_dosis', 'via_aplicacion',
        'lote_medicamento', 'laboratorio', 'veterinario', 'costo',
        'proxima_aplicacion', 'dias_carencia', 'fin_carencia',
        'estado', 'observaciones',
    ];

    protected $casts = [
        'fecha_aplicacion'   => 'date',
        'proxima_aplicacion' => 'date',
        'fin_carencia'       => 'date',
        'dosis'              => 'decimal:2',
        'costo'              => 'decimal:2',
        'dias_carencia'      => 'integer',
    ];

    protected $dates = ['deleted_at'];

    // ─── Relaciones ────────────────────────────────────────────────────────────

    public function animal()  { return $this->belongsTo(Animal::class); }
    public function finca()   { return $this->belongsTo(Finca::class); }
    public function user()    { return $this->belongsTo(User::class); }

    // ─── Accessors ─────────────────────────────────────────────────────────────

    public function getDiasParaProximaAttribute(): ?string
    {
        if (!$this->proxima_aplicacion) return null;
        $diff = (int) Carbon::today()->diffInDays($this->proxima_aplicacion, false);
        if ($diff < 0)   return 'Vencida';
        if ($diff === 0) return 'Hoy';
        return 'En ' . $diff . ' día' . ($diff !== 1 ? 's' : '');
    }

    public function getAlertaProximaAttribute(): bool
    {
        if (!$this->proxima_aplicacion) return false;
        return (int) Carbon::today()->diffInDays($this->proxima_aplicacion, false) <= 7;
    }

    public function getDiasCarenciaRestantesAttribute(): int
    {
        if (!$this->fin_carencia) return 0;
        return max(0, (int) Carbon::today()->diffInDays($this->fin_carencia, false));
    }

    public function getEnCarenciaAttribute(): bool
    {
        if (!$this->fin_carencia) return false;
        return Carbon::today()->lte($this->fin_carencia);
    }

    // ─── Scopes ────────────────────────────────────────────────────────────────

    public function scopeDelUsuario($query)
    {
        return $query->where('user_id', auth()->id());
    }

    // ─── Catálogos estáticos ───────────────────────────────────────────────────

    public static function generarCodigo(): string
    {
        $ultimo = static::withTrashed()->latest('id')->first();
        $numero = $ultimo ? $ultimo->id + 1 : 1;
        return 'SAL-' . str_pad($numero, 6, '0', STR_PAD_LEFT);
    }

    public static function getTipos(): array
    {
        return [
            // Preventivo
            'vacunacion'      => 'Vacunación',
            'vitaminizacion'  => 'Vitaminización',
            'desparasitacion' => 'Desparasitación',
            'bioseguridad'    => 'Bioseguridad',
            // Clínico
            'enfermedad'      => 'Enfermedad',
            'tratamiento'     => 'Tratamiento',
            'diagnostico'     => 'Diagnóstico',
            'cirugia'         => 'Cirugía',
            'revision'        => 'Revisión',
            // Reproductivo
            'sincronizacion'  => 'Sincronización',
            'hormonas'        => 'Hormonas',
            'protocolo'       => 'Protocolo',
            'preparacion_iatf'=> 'Preparación IATF',
            // Seguimiento
            'alerta'          => 'Alerta',
            'control'         => 'Control',
            'carencia'        => 'Carencia',
            // Genérico (sin categoría)
            'otro'            => 'Otro',
        ];
    }

    public static function getEstados(): array
    {
        return [
            'completado'     => 'Completado',
            'en_tratamiento' => 'En Tratamiento',
            'pendiente'      => 'Pendiente',
            'cancelado'      => 'Cancelado',
        ];
    }

    public static function getViasAplicacion(): array
    {
        return [
            'intramuscular' => 'Intramuscular',
            'subcutanea'    => 'Subcutánea',
            'intravenosa'   => 'Intravenosa',
            'oral'          => 'Oral',
            'topica'        => 'Tópica',
            'intranasal'    => 'Intranasal',
            'otra'          => 'Otra',
        ];
    }

    public static function getUnidadesDosis(): array
    {
        return ['ml', 'cc', 'mg', 'g', 'dosis', 'unidad'];
    }

    // ─── Mapeo tipo → categoría ────────────────────────────────────────────────

    public static function getCategoriaPorTipo(string $tipo): ?string
    {
        return match ($tipo) {
            'vacunacion',
            'vitaminizacion',
            'desparasitacion',
            'bioseguridad'      => 'preventivo',
            'enfermedad',
            'tratamiento',
            'diagnostico',
            'cirugia',
            'revision'          => 'clinico',
            'sincronizacion',
            'hormonas',
            'protocolo',
            'preparacion_iatf'  => 'reproductivo',
            'alerta',
            'control',
            'carencia'          => 'seguimiento',
            default => null,
        };
    }

    // ─── Asignar categoría automáticamente al crear ────────────────────────────

    protected static function booted(): void
    {
        static::creating(function (Salud $salud) {
            if (empty($salud->categoria) && $salud->tipo) {
                $salud->categoria = self::getCategoriaPorTipo($salud->tipo);
            }
        });
    }
}