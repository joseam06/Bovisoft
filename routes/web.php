<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\FincaController;
use App\Http\Controllers\AnimalController;
use App\Http\Controllers\PotreroController;
use App\Http\Controllers\SaludController;
use App\Http\Controllers\FinanzasController;
use App\Http\Controllers\ReportesController;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');
    
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


// Perfil de usuario
Route::get('/perfil', [App\Http\Controllers\ProfileController::class, 'edit'])->name('profile.edit');
Route::patch('/perfil', [App\Http\Controllers\ProfileController::class, 'update'])->name('profile.update');
Route::patch('/perfil/password', [App\Http\Controllers\ProfileController::class, 'updatePassword'])->name('profile.password');
Route::post('/perfil/foto', [App\Http\Controllers\ProfileController::class, 'updatePhoto'])->name('profile.photo');
Route::delete('/perfil/foto', [App\Http\Controllers\ProfileController::class, 'deletePhoto'])->name('profile.photo.delete');


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
   
// Rutas adicionales por categoría (deben ir ANTES del resource)
Route::get('/salud/preventivo', [SaludController::class, 'preventivo'])
     ->name('salud.preventivo')
     ->middleware('auth');

Route::get('/salud/clinico', [SaludController::class, 'clinico'])
     ->name('salud.clinico')
     ->middleware('auth');

Route::get('/salud/reproductivo', [SaludController::class, 'reproductivo'])
     ->name('salud.reproductivo')
     ->middleware('auth');

Route::get('/salud/seguimiento', [SaludController::class, 'seguimiento'])
     ->name('salud.seguimiento')
     ->middleware('auth');

     Route::get('/animales/{animal}/salud', [SaludController::class, 'porAnimal'])
    ->name('salud.por-animal')
    ->middleware('auth');
 


// Rutas resource originales (ahora después)
Route::resource('salud', SaludController::class)->middleware('auth');

    
    // Producción
    Route::resource('produccion', App\Http\Controllers\ProduccionController::class);
    Route::get('/api/produccion/chart', [App\Http\Controllers\ProduccionController::class, 'apiChart'])->name('api.produccion.chart');
    Route::get('/animales/{animal}/produccion', [App\Http\Controllers\ProduccionController::class, 'porAnimal'])->name('produccion.por-animal');
    
    
    // Finanzas — panel principal
    Route::get('/finanzas', [FinanzasController::class, 'index'])->name('finanzas.index');

    // Ingresos
    Route::get('/finanzas/ingresos/nuevo',   [FinanzasController::class, 'createIngreso'])->name('finanzas.ingresos.create');
    Route::post('/finanzas/ingresos',        [FinanzasController::class, 'storeIngreso'])->name('finanzas.ingresos.store');
    Route::get('/finanzas/ingresos/{id}',    [FinanzasController::class, 'showIngreso'])->name('finanzas.ingresos.show');
    Route::delete('/finanzas/ingresos/{id}', [FinanzasController::class, 'destroyIngreso'])->name('finanzas.ingresos.destroy');

    // Egresos
    Route::get('/finanzas/egresos/nuevo',   [FinanzasController::class, 'createEgreso'])->name('finanzas.egresos.create');
    Route::post('/finanzas/egresos',        [FinanzasController::class, 'storeEgreso'])->name('finanzas.egresos.store');
    Route::get('/finanzas/egresos/{id}',    [FinanzasController::class, 'showEgreso'])->name('finanzas.egresos.show');
    Route::delete('/finanzas/egresos/{id}', [FinanzasController::class, 'destroyEgreso'])->name('finanzas.egresos.destroy');
    
    // Alertas
    Route::get('/alertas', [App\Http\Controllers\AlertaController::class, 'index'])->name('alertas.index');
    Route::get('/api/alertas/recientes', [App\Http\Controllers\AlertaController::class, 'apiRecientes'])->name('api.alertas.recientes');
    
   // ── Reportes ────────────────────────────────────────────────────────────────
Route::prefix('reportes')->name('reportes.')->group(function () {
    Route::get('/',                        [ReportesController::class, 'index'])->name('index');

    // Inventario
    Route::get('/preview/inventario',      [ReportesController::class, 'previewInventario'])->name('preview.inventario');
    Route::get('/descargar/inventario',    [ReportesController::class, 'descargarInventario'])->name('descargar.inventario');

    // Salud general
    Route::get('/preview/salud',           [ReportesController::class, 'previewSalud'])->name('preview.salud');
    Route::get('/descargar/salud',         [ReportesController::class, 'descargarSalud'])->name('descargar.salud');

    // Vacunacion
    Route::get('/preview/vacunacion',      [ReportesController::class, 'previewVacunacion'])->name('preview.vacunacion');
    Route::get('/descargar/vacunacion',    [ReportesController::class, 'descargarVacunacion'])->name('descargar.vacunacion');

    // Tratamientos
    Route::get('/preview/tratamientos',    [ReportesController::class, 'previewTratamientos'])->name('preview.tratamientos');
    Route::get('/descargar/tratamientos',  [ReportesController::class, 'descargarTratamientos'])->name('descargar.tratamientos');

    // Historial por animal
    Route::get('/preview/historial',       [ReportesController::class, 'previewHistorialAnimal'])->name('preview.historial');
    Route::get('/descargar/historial',     [ReportesController::class, 'descargarHistorialAnimal'])->name('descargar.historial');

    // Financiero
    Route::get('/preview/financiero',      [ReportesController::class, 'previewFinanciero'])->name('preview.financiero');
    Route::get('/descargar/financiero',    [ReportesController::class, 'descargarFinanciero'])->name('descargar.financiero');
});
    
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