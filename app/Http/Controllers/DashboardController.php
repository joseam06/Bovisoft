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
            'produccionMes', 'chartProduccion', 'chartDiasLabel'
        ));
    }
}