<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Salud;
use Carbon\Carbon;

class AlertaController extends Controller
{
    /**
     * Genera alertas dinámicamente desde registros de salud.
     * Retorna colección de arrays con estructura uniforme.
     */
    public static function generarAlertas(int $userId, int $limite = null): \Illuminate\Support\Collection
    {
        $hoy = Carbon::today();

        $registros = Salud::with(['animal', 'finca'])
            ->where('user_id', $userId)
            ->whereNotIn('estado', ['cancelado'])
            ->where(function ($q) use ($hoy) {
                // Próxima aplicación vencida o en los próximos 15 días
                $q->whereNotNull('proxima_aplicacion')
                  ->where('proxima_aplicacion', '<=', $hoy->copy()->addDays(15))
                  // Tratamientos activos
                  ->orWhere('estado', 'en_tratamiento')
                  // En período de carencia
                  ->orWhere(function ($q2) use ($hoy) {
                      $q2->whereNotNull('fin_carencia')
                         ->where('fin_carencia', '>=', $hoy);
                  });
            })
            ->orderBy('proxima_aplicacion')
            ->get();

        $alertas = collect();

        foreach ($registros as $registro) {
            $animal = $registro->animal;
            $finca  = $registro->finca;

            // --- Alerta: próxima aplicación vencida ---
            if ($registro->proxima_aplicacion) {
                $diffDias = (int) $hoy->diffInDays($registro->proxima_aplicacion, false);

                if ($diffDias < 0) {
                    $alertas->push([
                        'id'          => 'sal-vencida-' . $registro->id,
                        'tipo'        => 'vencida',
                        'nivel'       => 'critico',
                        'titulo'      => 'Aplicación vencida: ' . self::nombreTipo($registro->tipo),
                        'descripcion' => ($animal ? $animal->nombre ?? $animal->codigo : 'Animal') .
                                         ' — venció hace ' . abs($diffDias) . ' día' . (abs($diffDias) !== 1 ? 's' : ''),
                        'animal'      => $animal ? ($animal->nombre ?? $animal->codigo) : 'Sin animal',
                        'finca'       => $finca ? $finca->nombre : 'Sin finca',
                        'fecha'       => $registro->proxima_aplicacion->format('d/m/Y'),
                        'salud_id'    => $registro->id,
                        'categoria'   => $registro->categoria,
                        'icono'       => 'fa-circle-exclamation',
                        'color'       => 'red',
                    ]);
                } elseif ($diffDias === 0) {
                    $alertas->push([
                        'id'          => 'sal-hoy-' . $registro->id,
                        'tipo'        => 'hoy',
                        'nivel'       => 'urgente',
                        'titulo'      => 'Aplicación pendiente HOY: ' . self::nombreTipo($registro->tipo),
                        'descripcion' => ($animal ? $animal->nombre ?? $animal->codigo : 'Animal') .
                                         ' — programado para hoy',
                        'animal'      => $animal ? ($animal->nombre ?? $animal->codigo) : 'Sin animal',
                        'finca'       => $finca ? $finca->nombre : 'Sin finca',
                        'fecha'       => $registro->proxima_aplicacion->format('d/m/Y'),
                        'salud_id'    => $registro->id,
                        'categoria'   => $registro->categoria,
                        'icono'       => 'fa-bell',
                        'color'       => 'orange',
                    ]);
                } elseif ($diffDias <= 7) {
                    $alertas->push([
                        'id'          => 'sal-proxima-' . $registro->id,
                        'tipo'        => 'proxima',
                        'nivel'       => 'advertencia',
                        'titulo'      => 'Próxima aplicación: ' . self::nombreTipo($registro->tipo),
                        'descripcion' => ($animal ? $animal->nombre ?? $animal->codigo : 'Animal') .
                                         ' — en ' . $diffDias . ' día' . ($diffDias !== 1 ? 's' : ''),
                        'animal'      => $animal ? ($animal->nombre ?? $animal->codigo) : 'Sin animal',
                        'finca'       => $finca ? $finca->nombre : 'Sin finca',
                        'fecha'       => $registro->proxima_aplicacion->format('d/m/Y'),
                        'salud_id'    => $registro->id,
                        'categoria'   => $registro->categoria,
                        'icono'       => 'fa-clock',
                        'color'       => 'yellow',
                    ]);
                } elseif ($diffDias <= 15) {
                    $alertas->push([
                        'id'          => 'sal-proximo15-' . $registro->id,
                        'tipo'        => 'pronto',
                        'nivel'       => 'info',
                        'titulo'      => 'Aplicación en ' . $diffDias . ' días: ' . self::nombreTipo($registro->tipo),
                        'descripcion' => ($animal ? $animal->nombre ?? $animal->codigo : 'Animal') .
                                         ' — ' . $registro->proxima_aplicacion->format('d/m/Y'),
                        'animal'      => $animal ? ($animal->nombre ?? $animal->codigo) : 'Sin animal',
                        'finca'       => $finca ? $finca->nombre : 'Sin finca',
                        'fecha'       => $registro->proxima_aplicacion->format('d/m/Y'),
                        'salud_id'    => $registro->id,
                        'categoria'   => $registro->categoria,
                        'icono'       => 'fa-calendar-check',
                        'color'       => 'blue',
                    ]);
                }
            }

            // --- Alerta: tratamiento activo ---
            if ($registro->estado === 'en_tratamiento') {
                // Evitar duplicado si ya generamos alerta de proxima_aplicacion para este registro
                $yaExiste = $alertas->firstWhere('salud_id', $registro->id);
                if (!$yaExiste) {
                    $alertas->push([
                        'id'          => 'sal-tratamiento-' . $registro->id,
                        'tipo'        => 'tratamiento',
                        'nivel'       => 'info',
                        'titulo'      => 'Tratamiento activo: ' . self::nombreTipo($registro->tipo),
                        'descripcion' => ($animal ? $animal->nombre ?? $animal->codigo : 'Animal') .
                                         ' — en tratamiento',
                        'animal'      => $animal ? ($animal->nombre ?? $animal->codigo) : 'Sin animal',
                        'finca'       => $finca ? $finca->nombre : 'Sin finca',
                        'fecha'       => $registro->fecha_aplicacion
                                            ? $registro->fecha_aplicacion->format('d/m/Y')
                                            : '—',
                        'salud_id'    => $registro->id,
                        'categoria'   => $registro->categoria,
                        'icono'       => 'fa-heart-pulse',
                        'color'       => 'purple',
                    ]);
                }
            }

            // --- Alerta: período de carencia activo ---
            if ($registro->fin_carencia && $hoy->lte($registro->fin_carencia)) {
                $diasRestantes = (int) $hoy->diffInDays($registro->fin_carencia, false);
                $alertas->push([
                    'id'          => 'sal-carencia-' . $registro->id,
                    'tipo'        => 'carencia',
                    'nivel'       => 'info',
                    'titulo'      => 'Período de carencia activo',
                    'descripcion' => ($animal ? $animal->nombre ?? $animal->codigo : 'Animal') .
                                     ' — vence en ' . $diasRestantes . ' día' . ($diasRestantes !== 1 ? 's' : ''),
                    'animal'      => $animal ? ($animal->nombre ?? $animal->codigo) : 'Sin animal',
                    'finca'       => $finca ? $finca->nombre : 'Sin finca',
                    'fecha'       => $registro->fin_carencia->format('d/m/Y'),
                    'salud_id'    => $registro->id,
                    'categoria'   => $registro->categoria,
                    'icono'       => 'fa-shield-halved',
                    'color'       => 'teal',
                ]);
            }
        }

        // Prioridad: critico > urgente > advertencia > info
        $orden = ['critico' => 0, 'urgente' => 1, 'advertencia' => 2, 'info' => 3];
        $alertas = $alertas->sortBy(fn($a) => $orden[$a['nivel']] ?? 99)->values();

        return $limite ? $alertas->take($limite) : $alertas;
    }

    /**
     * Cuenta total de alertas críticas + urgentes (para el badge de campanita).
     */
    public static function contarAlertasUrgentes(int $userId): int
    {
        return self::generarAlertas($userId)
            ->whereIn('nivel', ['critico', 'urgente'])
            ->count();
    }

    /**
     * Vista principal del módulo Alertas.
     */
    public function index()
    {
        $userId  = Auth::id();
        $alertas = self::generarAlertas($userId);

        $criticas    = $alertas->where('nivel', 'critico')->values();
        $urgentes    = $alertas->where('nivel', 'urgente')->values();
        $advertencias = $alertas->where('nivel', 'advertencia')->values();
        $informativas = $alertas->whereIn('nivel', ['info'])->values();

        return view('alertas.index', compact(
            'alertas', 'criticas', 'urgentes', 'advertencias', 'informativas'
        ));
    }

    /**
     * API JSON para la campanita (dropdown en header).
     */
    public function apiRecientes()
    {
        $alertas = self::generarAlertas(Auth::id(), 8);
        return response()->json($alertas);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private static function nombreTipo(string $tipo): string
    {
        return Salud::getTipos()[$tipo] ?? ucfirst($tipo);
    }
}