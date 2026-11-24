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
        $records = Salud::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))
            ->whereHas('animal', fn ($q) => $q->where('user_id',$userId))->with(['animal','finca'])->get()->map(fn ($r) =>
                \App\Services\WorkspaceJournal::serialize($r)+['animal_nombre'=>$r->animal->nombre??$r->animal->codigo,'finca_nombre'=>$r->finca->nombre])->all();
        $paddocks=\App\Models\Potrero::where('user_id',$userId)->whereHas('finca',fn($q)=>$q->delUsuario($userId))->with('finca')->get()->map(fn($p)=>\App\Services\WorkspaceJournal::serialize($p)+['finca_nombre'=>$p->finca->nombre])->all();
        $alerts = collect(\App\Services\AlertRules::generate($records, Carbon::today()->format('Y-m-d'),$paddocks));
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
