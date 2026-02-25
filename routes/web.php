<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\AnimalController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');
    
    // Rutas de Fincas
    Route::resource('fincas', FincaController::class);
    
    // API para mapa
    Route::get('/api/fincas/mapa', [FincaController::class, 'getFincasParaMapa'])
        ->name('api.fincas.mapa');
    
    // Rutas de Animales
    Route::resource('animales', AnimalController::class);
    
    // Ruta adicional para obtener animales por finca (AJAX)
    Route::get('fincas/{finca}/animales', [AnimalController::class, 'porFinca'])
        ->name('animales.por-finca');
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');