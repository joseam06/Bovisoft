<?php

namespace App\Http\Controllers;

use App\Models\Animal;
use App\Models\Produccion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $hoy = Carbon::today();
        $health = \App\Models\Salud::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))
            ->selectRaw('COUNT(*) AS total, SUM(CASE WHEN fin_carencia >= ? THEN 1 ELSE 0 END) AS carencia, SUM(CASE WHEN proxima_aplicacion BETWEEN ? AND ? THEN 1 ELSE 0 END) AS proximas', [$hoy->toDateString(), $hoy->toDateString(), $hoy->copy()->addDays(7)->toDateString()])->first();
        $inventorySummary = [
            'fincas' => \App\Models\Finca::delUsuario($userId)->count(),
            'potreros' => \App\Models\Potrero::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->count(),
            'ocupacion' => Animal::where('user_id', $userId)->where('estado', 'activo')->whereHas('potrero', fn ($q) => $q->where('user_id', $userId))->count(),
            'salud' => (int) $health->total,
            'carencia' => (int) $health->carencia,
            'proximas' => (int) $health->proximas,
        ];
        $hoy = Carbon::today();

        // Alertas
        $allAlerts = AlertaController::generarAlertas($userId);
        $alertasDashboard = $allAlerts->take(5);
        $totalAlertas = $allAlerts->count();

        // Producción del mes
        $produccionMes = (float) Produccion::where('user_id', $userId)
            ->whereBetween('fecha', [$hoy->copy()->startOfMonth()->toDateString(), $hoy->copy()->endOfMonth()->toDateString()])
            ->sum('litros');

        // Datos del gráfico (últimos 7 días)
        $chartProduccion = [];
        $chartDiasLabel = [];
        $daily = Produccion::where('user_id', $userId)->whereBetween('fecha', [$hoy->copy()->subDays(6)->toDateString(), $hoy->toDateString()])
            ->selectRaw('fecha, SUM(litros) AS total')->groupBy('fecha')->toBase()->pluck('total', 'fecha');
        for ($i = 6; $i >= 0; $i--) {
            $dia = $hoy->copy()->subDays($i);
            $chartDiasLabel[] = $dia->locale('es')->isoFormat('ddd');
            $chartProduccion[] = (float) ($daily[$dia->toDateString()] ?? 0);
        }

        return view('dashboard', compact(
            'alertasDashboard', 'totalAlertas',
            'produccionMes', 'chartProduccion', 'chartDiasLabel', 'inventorySummary'
        ));
    }
}
