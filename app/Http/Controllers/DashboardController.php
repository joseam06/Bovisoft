<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\Animal;

class DashboardController extends Controller
{
    public function index()
    {
        $userId = Auth::id();

        // Alertas para el dashboard (máx 5 en la tarjeta)
        $alertasDashboard = AlertaController::generarAlertas($userId, 5);
        $totalAlertas     = AlertaController::generarAlertas($userId)->count();

        return view('dashboard', compact('alertasDashboard', 'totalAlertas'));
    }
}