<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Animal;
use App\Models\Produccion;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();
        $inventorySummary = [
            'fincas' => \App\Models\Finca::delUsuario($userId)->count(),
            'potreros' => \App\Models\Potrero::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->count(),
            'ocupacion' => Animal::where('user_id', $userId)->where('estado', 'activo')->whereHas('potrero', fn ($q) => $q->where('user_id', $userId))->count(),
            'salud' => \App\Models\Salud::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->count(),
            'carencia' => \App\Models\Salud::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->where('fin_carencia', '>=', Carbon::today())->count(),
            'proximas' => \App\Models\Salud::where('user_id', $userId)->whereHas('finca', fn ($q) => $q->delUsuario($userId))->whereBetween('proxima_aplicacion', [Carbon::today(), Carbon::today()->addDays(7)])->count(),
        ];
        $hoy    = Carbon::today();

        // Alertas
        $alertasDashboard = AlertaController::generarAlertas($userId, 5);
        $totalAlertas     = AlertaController::generarAlertas($userId)->count();

        // Producción del mes
        $produccionMes = (float) Produccion::where('user_id', $userId)
            ->whereMonth('fecha', $hoy->month)
            ->whereYear('fecha', $hoy->year)
            ->sum('litros');

        // Datos del gráfico (últimos 7 días)
        $chartProduccion = [];
        $chartDiasLabel  = [];
        for ($i = 6; $i >= 0; $i--) {
            $dia = $hoy->copy()->subDays($i);
            $chartDiasLabel[]  = $dia->locale('es')->isoFormat('ddd');
            $chartProduccion[] = (float) Produccion::where('user_id', $userId)
                ->whereDate('fecha', $dia)
                ->sum('litros');
        }

        return view('dashboard', compact(
            'alertasDashboard', 'totalAlertas',
            'produccionMes', 'chartProduccion', 'chartDiasLabel', 'inventorySummary'
        ));
    }
}
