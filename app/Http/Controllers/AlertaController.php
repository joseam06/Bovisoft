<?php

namespace App\Http\Controllers;

use App\Models\Salud;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class AlertaController extends Controller
{
    /**
     * Genera alertas dinámicamente desde registros de salud.
     * Retorna colección de arrays con estructura uniforme.
     */
    public static function generarAlertas(int $userId, ?int $limite = null): \Illuminate\Support\Collection
    {
        $records = Salud::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))
            ->whereHas('animal', fn ($q) => $q->where('user_id', $userId))->with(['animal:id,nombre,codigo', 'finca:id,nombre'])
            ->get(['id', 'client_uuid', 'animal_id', 'finca_id', 'tipo', 'categoria', 'estado', 'fecha_aplicacion', 'proxima_aplicacion', 'fin_carencia'])
            ->map(fn ($r) => ['id' => $r->id, 'record_uuid' => $r->client_uuid, 'tipo' => $r->tipo, 'categoria' => $r->categoria, 'estado' => $r->estado,
                'fecha_aplicacion' => $r->fecha_aplicacion?->format('Y-m-d'), 'proxima_aplicacion' => $r->proxima_aplicacion?->format('Y-m-d'), 'fin_carencia' => $r->fin_carencia?->format('Y-m-d'),
                'animal_nombre' => $r->animal->nombre ?? $r->animal->codigo, 'finca_nombre' => $r->finca->nombre])->all();
        $paddocks = \App\Models\Potrero::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->with('finca:id,nombre')
            ->get(['id', 'client_uuid', 'finca_id', 'nombre', 'dias_descanso', 'fecha_ultima_rotacion'])->map(fn ($p) => [
                'id' => $p->id, 'record_uuid' => $p->client_uuid, 'nombre' => $p->nombre, 'dias_descanso' => $p->dias_descanso,
                'fecha_ultima_rotacion' => $p->fecha_ultima_rotacion?->format('Y-m-d'), 'finca_nombre' => $p->finca->nombre])->all();
        $alerts = collect(\App\Services\AlertRules::generate($records, Carbon::today()->format('Y-m-d'), $paddocks));

        return $limite ? $alerts->take($limite) : $alerts;
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
        $userId = Auth::id();
        $alertas = self::generarAlertas($userId);

        $criticas = $alertas->where('nivel', 'critico')->values();
        $urgentes = $alertas->where('nivel', 'urgente')->values();
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
