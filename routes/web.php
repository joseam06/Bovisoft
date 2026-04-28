<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\PotreroController;

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
    
    // Ruta para cambiar estado de finca
    Route::post('fincas/{finca}/toggle-estado', [FincaController::class, 'toggleEstado'])
    ->name('fincas.toggle-estado');
    
   // API para mapa
Route::get('/api/fincas/mapa', [FincaController::class, 'getFincasParaMapa'])
->name('api.fincas.mapa');

// NUEVA API: Potreros por finca (con disponibilidad)
Route::get('/api/fincas/{finca}/potreros', function($fincaId) {
    $potreros = \App\Models\Potrero::where('finca_id', $fincaId)
        ->where('user_id', Auth::id())
        ->where('estado', 'activo')
        ->get(['id', 'nombre', 'capacidad_animales', 'animales_actuales'])
        ->map(function($potrero) {
            $disponibilidad = $potrero->capacidad_animales
                ? max(0, $potrero->capacidad_animales - $potrero->animales_actuales)
                : 'Sin límite';

            return [
                'id' => $potrero->id,
                'nombre' => $potrero->nombre,
                'disponibilidad' => $disponibilidad
            ];
        });

    return response()->json($potreros);
})->name('api.fincas.potreros');

// Rutas de Animales
Route::resource('animales', AnimalController::class);
   
    // Rutas de Potreros (agregar después de las rutas de animales)
Route::middleware(['auth'])->group(function () {
    Route::resource('potreros', PotreroController::class);
    Route::post('potreros/{potrero}/cambiar-estado', [PotreroController::class, 'cambiarEstado'])->name('potreros.cambiar-estado');
    Route::get('fincas/{finca}/potreros', [PotreroController::class, 'porFinca'])->name('potreros.por-finca');
});

    // Ruta adicional para obtener animales por finca (AJAX)
    Route::get('fincas/{finca}/animales', [AnimalController::class, 'porFinca'])
        ->name('animales.por-finca');
});

    // Rutas de módulos en desarrollo
    Route::middleware(['auth:sanctum', config('jetstream.auth_session'), 'verified'])->group(function () {
    
    // Salud
    Route::get('/salud', function () {
        return view('en-desarrollo', [
            'modulo' => 'Salud',
            'progreso' => '20',
            'caracteristicas' => [
                'Calendario de vacunaciones',
                'Registro de tratamientos y medicamentos',
                'Control de enfermedades',
                'Historial clínico por animal',
                'Alertas de próximas vacunas',
                'Inventario de medicamentos',
            ]
        ]);
    })->name('salud.index');
    
    // Producción
    Route::get('/produccion', function () {
        return view('en-desarrollo', [
            'modulo' => 'Producción',
            'progreso' => '15',
            'caracteristicas' => [
                'Registro diario de producción lechera',
                'Control de calidad de leche',
                'Estadísticas y gráficas de producción',
                'Proyecciones de producción',
                'Comparativas por animal y por finca',
                'Exportación de datos',
            ]
        ]);
    })->name('produccion.index');
    
    // Finanzas
    Route::get('/finanzas', function () {
        return view('en-desarrollo', [
            'modulo' => 'Finanzas',
            'progreso' => '10',
            'caracteristicas' => [
                'Control de ingresos y egresos',
                'Registro de ventas de animales y productos',
                'Control de compras y gastos',
                'Balance financiero',
                'Reportes de rentabilidad',
                'Proyecciones financieras',
            ]
        ]);
    })->name('finanzas.index');
    
    // Alertas
    Route::get('/alertas', function () {
        return view('en-desarrollo', [
            'modulo' => 'Alertas',
            'progreso' => '25',
            'caracteristicas' => [
                'Notificaciones automáticas',
                'Alertas de vacunación pendiente',
                'Avisos de partos próximos',
                'Recordatorios de tratamientos',
                'Alertas personalizables',
                'Notificaciones por correo y SMS',
            ]
        ]);
    })->name('alertas.index');
    
    // Reportes
    Route::get('/reportes', function () {
        return view('en-desarrollo', [
            'modulo' => 'Reportes',
            'progreso' => '35',
            'caracteristicas' => [
                'Reportes predefinidos',
                'Reportes personalizables',
                'Exportación a PDF y Excel',
                'Gráficas y estadísticas',
                'Reportes por período',
                'Dashboard de KPIs',
            ]
        ]);
    })->name('reportes.index');
    
    // Configuración
    Route::get('/configuracion', function () {
        return view('en-desarrollo', [
            'modulo' => 'Configuración',
            'progreso' => '40',
            'caracteristicas' => [
                'Gestión de usuarios y roles',
                'Configuración de la cuenta',
                'Personalización del sistema',
                'Gestión de permisos',
                'Configuración de notificaciones',
                'Backup y restauración',
            ]
        ]);
    })->name('configuracion.index');
    
   
});

Route::post('/logout', function () {
    Auth::logout();
    request()->session()->invalidate();
    request()->session()->regenerateToken();
    return redirect('/');
})->name('logout');