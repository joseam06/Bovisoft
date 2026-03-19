@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

<!-- Mensajes de éxito -->
@if(session('success'))
<div class="mb-6 p-4 bg-green-50 border-2 border-green-200 rounded-xl shadow-lg animate-slide-up">
    <div class="flex items-center gap-3">
        <i class="fa-solid fa-check-circle text-green-600 text-2xl"></i>
        <p class="text-green-800 font-semibold">{{ session('success') }}</p>
    </div>
</div>
@endif

<!-- Page Header -->
<div class="mb-8 relative">
    <div class="absolute inset-0 bg-gradient-to-r from-red-600/10 to-black/10 rounded-3xl blur-3xl"></div>
    <div class="relative glass-effect rounded-2xl p-6 shadow-xl border-4 border-white/50">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold mb-3">
                    Panel de Control
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-2">Dashboard Bovisoft</h1>
                <p class="text-gray-600 text-lg">Gestión inteligente de tu operación ganadera</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('fincas.create') }}" class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-red-700 hover:to-red-800 transition-all">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Nueva Finca
                </a>
                <button class="px-6 py-3 bg-white border-2 border-red-200 text-red-700 font-bold rounded-xl shadow-lg hover:shadow-xl hover:bg-red-50 transition-all">
                    <i class="fa-solid fa-download mr-2"></i>
                    Exportar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    
    <!-- Total Animales Card -->
    <div class="stat-card rounded-2xl shadow-xl overflow-hidden card-hover border-4 border-white/50 animate-slide-up">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-cow text-white text-2xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-600 mb-1">Total Animales</p>
                    <h3 class="text-4xl font-extrabold bg-gradient-to-r from-red-700 to-red-900 bg-clip-text text-transparent">
                        {{ \App\Models\Animal::where('user_id', Auth::id())->count() }}
                    </h3>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t-2 border-red-100">
                <span class="text-xs text-gray-600 flex items-center">
                    <i class="fa-solid fa-circle text-green-500 text-xs mr-2"></i>
                    Sistema activo
                </span>
                <a href="{{ route('animales.index') }}" class="text-xs text-red-700 hover:text-red-800 font-semibold flex items-center group">
                    Ver todos
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
        <div class="h-2 bg-gradient-to-r from-red-600 to-red-800"></div>
    </div>

    <!-- Fincas Card -->
    <div class="stat-card rounded-2xl shadow-xl overflow-hidden card-hover border-4 border-white/50 animate-slide-up" style="animation-delay: 0.1s">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-600 to-red-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-map-location-dot text-white text-2xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-600 mb-1">Fincas Activas</p>
                    <h3 class="text-4xl font-extrabold bg-gradient-to-r from-orange-600 to-red-700 bg-clip-text text-transparent" id="contador-fincas-card">0</h3>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t-2 border-orange-100">
                <span class="text-xs text-gray-600 flex items-center">
                    <i class="fa-solid fa-check-circle text-green-500 text-xs mr-2"></i>
                    Todas operativas
                </span>
                <a href="{{ route('fincas.index') }}" class="text-xs text-orange-700 hover:text-orange-800 font-semibold flex items-center group">
                    Ver todas
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
        <div class="h-2 bg-gradient-to-r from-orange-600 to-red-700"></div>
    </div>

    <!-- Alertas Card -->
    <div class="stat-card rounded-2xl shadow-xl overflow-hidden card-hover border-4 border-white/50 animate-slide-up" style="animation-delay: 0.2s">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-bell text-white text-2xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-600 mb-1">Alertas Activas</p>
                    <h3 class="text-4xl font-extrabold bg-gradient-to-r from-yellow-600 to-orange-600 bg-clip-text text-transparent">0</h3>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t-2 border-yellow-100">
                <span class="text-xs text-gray-600 flex items-center">
                    <i class="fa-solid fa-check text-green-500 text-xs mr-2"></i>
                    Sin pendientes
                </span>
                <a href="#" class="text-xs text-yellow-700 hover:text-yellow-800 font-semibold flex items-center group">
                    Ver todas
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
        <div class="h-2 bg-gradient-to-r from-yellow-500 to-orange-600"></div>
    </div>

    <!-- Producción Card -->
    <div class="stat-card rounded-2xl shadow-xl overflow-hidden card-hover border-4 border-white/50 animate-slide-up" style="animation-delay: 0.3s">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-red-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-chart-line text-white text-2xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-600 mb-1">Producción (L)</p>
                    <h3 class="text-4xl font-extrabold bg-gradient-to-r from-purple-600 to-red-700 bg-clip-text text-transparent">0</h3>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t-2 border-purple-100">
                <span class="text-xs text-gray-600 flex items-center">
                    <i class="fa-solid fa-minus text-gray-400 text-xs mr-2"></i>
                    Este mes
                </span>
                <a href="#" class="text-xs text-purple-700 hover:text-purple-800 font-semibold flex items-center group">
                    Ver detalles
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
        <div class="h-2 bg-gradient-to-r from-purple-600 to-red-700"></div>
    </div>
</div>

<!-- Separador -->
<div class="section-divider my-8"></div>

<!-- Charts and Map Row -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
    
    <!-- Distribución del Ganado -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                    <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-chart-pie text-white"></i>
                    </span>
                    Distribución del Ganado
                </h2>
                <p class="text-sm text-gray-600 mt-1 ml-13">Por categoría</p>
            </div>
            <select class="px-4 py-2 bg-white border-2 border-red-200 rounded-xl text-sm font-semibold text-gray-700 focus:outline-none focus:ring-2 focus:ring-red-500 shadow-sm">
                <option>Este mes</option>
                <option>Este año</option>
                <option>Todo</option>
            </select>
        </div>
        
        @php
            $totalAnimales = \App\Models\Animal::where('user_id', Auth::id())->count();
            $vacas = \App\Models\Animal::where('user_id', Auth::id())->where('tipo', 'vaca')->count();
            $toros = \App\Models\Animal::where('user_id', Auth::id())->where('tipo', 'toro')->count();
            $terneros = \App\Models\Animal::where('user_id', Auth::id())->where('tipo', 'ternero')->count();
            $novillas = \App\Models\Animal::where('user_id', Auth::id())->where('tipo', 'novilla')->count();
            
            $porcentajeVacas = $totalAnimales > 0 ? round(($vacas / $totalAnimales) * 100, 1) : 0;
            $porcentajeToros = $totalAnimales > 0 ? round(($toros / $totalAnimales) * 100, 1) : 0;
            $porcentajeTerneros = $totalAnimales > 0 ? round(($terneros / $totalAnimales) * 100, 1) : 0;
            $porcentajeNovillas = $totalAnimales > 0 ? round(($novillas / $totalAnimales) * 100, 1) : 0;
        @endphp
        
        <div class="relative h-80">
            <canvas id="distribucionChart"></canvas>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mt-6">
            <div class="flex items-center p-3 bg-red-50 rounded-xl border-2 border-red-100">
                <div class="w-4 h-4 rounded-full bg-red-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Vacas</p>
                    <p class="font-bold text-gray-900">{{ $vacas }} ({{ $porcentajeVacas }}%)</p>
                </div>
            </div>
            <div class="flex items-center p-3 bg-blue-50 rounded-xl border-2 border-blue-100">
                <div class="w-4 h-4 rounded-full bg-blue-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Terneros</p>
                    <p class="font-bold text-gray-900">{{ $terneros }} ({{ $porcentajeTerneros }}%)</p>
                </div>
            </div>
            <div class="flex items-center p-3 bg-purple-50 rounded-xl border-2 border-purple-100">
                <div class="w-4 h-4 rounded-full bg-purple-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Toros</p>
                    <p class="font-bold text-gray-900">{{ $toros }} ({{ $porcentajeToros }}%)</p>
                </div>
            </div>
            <div class="flex items-center p-3 bg-yellow-50 rounded-xl border-2 border-yellow-100">
                <div class="w-4 h-4 rounded-full bg-yellow-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Novillas</p>
                    <p class="font-bold text-gray-900">{{ $novillas }} ({{ $porcentajeNovillas }}%)</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Mapa de Fincas -->
    <div id="mapa-fincas" class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
        <div class="mb-6">
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-orange-600 to-red-700 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-map-marked-alt text-white"></i>
                </span>
                Ubicación de Fincas
            </h2>
            <p class="text-sm text-gray-600 mt-1 ml-13">Visualización geográfica - <span id="contador-fincas" class="font-bold text-orange-600">0 fincas</span></p>
        </div>
        
        <div id="map" class="shadow-xl border-4 border-white/50"></div>
        
        <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border-2 border-orange-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-info-circle text-orange-600 text-2xl"></i>
                    <div>
                        <p class="font-bold text-gray-900 text-sm" id="mapa-info-titulo">Agrega tu primera finca</p>
                        <p class="text-xs text-gray-600" id="mapa-info-desc">Registra fincas para verlas en el mapa</p>
                    </div>
                </div>
                <a href="{{ route('fincas.create') }}" class="px-4 py-2 bg-gradient-to-r from-orange-600 to-red-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all">
                    Agregar Finca
                </a>
            </div>
        </div>
    </div>

</div>

<!-- Producción Semanal -->
<div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover mb-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-red-700 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-chart-bar text-white"></i>
                </span>
                Producción Lechera
            </h2>
            <p class="text-sm text-gray-600 mt-1 ml-13">Últimos 7 días</p>
        </div>
        <div class="flex gap-2">
            <button class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all">
                7 días
            </button>
            <button class="px-4 py-2 bg-white border-2 border-red-200 text-red-700 text-sm font-semibold rounded-lg hover:bg-red-50 transition-all">
                30 días
            </button>
            <button class="px-4 py-2 bg-white border-2 border-red-200 text-red-700 text-sm font-semibold rounded-lg hover:bg-red-50 transition-all">
                Este año
            </button>
        </div>
    </div>
    
    <div class="h-80">
        <canvas id="produccionChart"></canvas>
    </div>
</div>

<!-- Bottom Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    
    <!-- Alertas Recientes -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-bell text-white text-sm"></i>
            </span>
            Alertas Recientes
        </h2>
        
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fa-solid fa-check-circle text-green-600 text-3xl"></i>
            </div>
            <p class="text-gray-600 font-medium mb-2">Todo en orden</p>
            <p class="text-sm text-gray-500">No hay alertas pendientes por el momento</p>
        </div>
    </div>

    <!-- Actividad Reciente -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-br from-blue-600 to-purple-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-clock-rotate-left text-white text-sm"></i>
            </span>
            Actividad Reciente
        </h2>
        
        <div class="text-center py-12">
            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fa-solid fa-history text-gray-400 text-3xl"></i>
            </div>
            <p class="text-gray-600 font-medium mb-2">Sin actividad registrada</p>
            <p class="text-sm text-gray-500">Comienza a usar el sistema para ver el historial</p>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-br from-red-600 to-red-800 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-bolt text-white text-sm"></i>
            </span>
            Acciones Rápidas
        </h2>
        
        <div class="grid grid-cols-2 gap-3">
            <a href="{{ route('animales.create') }}" class="p-4 bg-gradient-to-br from-red-50 to-red-100 hover:from-red-100 hover:to-red-200 border-2 border-red-200 rounded-xl transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fa-solid fa-plus text-white text-xl"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Nuevo Animal</span>
                </div>
            </a>

            <button class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border-2 border-blue-200 rounded-xl transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fa-solid fa-syringe text-white text-xl"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Vacunación</span>
                </div>
            </button>

            <button class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 border-2 border-purple-200 rounded-xl transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fa-solid fa-chart-line text-white text-xl"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Producción</span>
                </div>
            </button>

            <a href="{{ route('fincas.create') }}" class="p-4 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border-2 border-green-200 rounded-xl transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fa-solid fa-map-marked text-white text-xl"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Nueva Finca</span>
                </div>
            </a>
        </div>
        
        <div class="mt-6 p-4 bg-gradient-to-r from-red-50 to-orange-50 rounded-xl border-2 border-red-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-lightbulb text-red-600 text-2xl"></i>
                    <div>
                        <p class="font-bold text-gray-900 text-sm">¿Necesitas ayuda?</p>
                        <p class="text-xs text-gray-600">Consulta la guía</p>
                    </div>
                </div>
                
                <button class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all">
                    Ver guía
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos PHP para gráficos
    var datosAnimales = {
        vacas: <?php echo $vacas; ?>,
        terneros: <?php echo $terneros; ?>,
        toros: <?php echo $toros; ?>,
        novillas: <?php echo $novillas; ?>,
        total: <?php echo $totalAnimales; ?>
    };

    // Gráfico Distribución
    var distribucionCtx = document.getElementById('distribucionChart');
    if (distribucionCtx) {
        new Chart(distribucionCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Vacas', 'Terneros', 'Toros', 'Novillas'],
                datasets: [{
                    data: [datosAnimales.vacas, datosAnimales.terneros, datosAnimales.toros, datosAnimales.novillas],
                    backgroundColor: [
                        'rgba(220, 38, 38, 0.8)',
                        'rgba(59, 130, 246, 0.8)',
                        'rgba(147, 51, 234, 0.8)',
                        'rgba(234, 179, 8, 0.8)'
                    ],
                    borderColor: [
                        'rgba(220, 38, 38, 1)',
                        'rgba(59, 130, 246, 1)',
                        'rgba(147, 51, 234, 1)',
                        'rgba(234, 179, 8, 1)'
                    ],
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                var label = context.label || '';
                                var value = context.parsed || 0;
                                var percentage = datosAnimales.total > 0 ? ((value / datosAnimales.total) * 100).toFixed(1) : 0;
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    // Gráfico Producción
    var produccionCtx = document.getElementById('produccionChart');
    if (produccionCtx) {
        new Chart(produccionCtx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Litros de leche',
                    data: [0, 0, 0, 0, 0, 0, 0],
                    backgroundColor: function(context) {
                        var ctx = context.chart.ctx;
                        var gradient = ctx.createLinearGradient(0, 0, 0, 400);
                        gradient.addColorStop(0, '#dc2626');
                        gradient.addColorStop(1, '#991b1b');
                        return gradient;
                    },
                    borderRadius: 8,
                    borderSkipped: false,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });
    }

    // Mapa
    var map = L.map('map').setView([8.7479, -75.8814], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);
    
    // Cargar fincas
    fetch('/api/fincas/mapa', {
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(function(response) { return response.json(); })
    .then(function(fincas) {
        if (fincas.length > 0) {
            document.getElementById('contador-fincas').textContent = fincas.length + ' ' + (fincas.length === 1 ? 'finca' : 'fincas');
            document.getElementById('contador-fincas-card').textContent = fincas.length;
            document.getElementById('mapa-info-titulo').textContent = 'Fincas registradas';
            document.getElementById('mapa-info-desc').textContent = 'Haz clic en los marcadores para ver detalles';
            
            var bounds = [];
            fincas.forEach(function(finca) {
                var marker = L.marker([finca.latitud, finca.longitud], {
                    icon: L.icon({
                        iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                        shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                        iconSize: [25, 41],
                        iconAnchor: [12, 41],
                        popupAnchor: [1, -34],
                        shadowSize: [41, 41]
                    })
                }).addTo(map);
                
                marker.bindPopup(
                    '<div class="p-3">' +
                    '<h3 class="font-bold text-red-700 mb-2 text-lg">' + finca.nombre + '</h3>' +
                    '<p class="text-sm text-gray-600 mb-1"><strong>Código:</strong> ' + finca.codigo + '</p>' +
                    '<p class="text-sm text-gray-600 mb-1"><strong>Área:</strong> ' + (finca.area || 'N/A') + ' ha</p>' +
                    (finca.direccion ? '<p class="text-xs text-gray-500 mt-2">' + finca.direccion + '</p>' : '') +
                    '</div>'
                );
                
                bounds.push([finca.latitud, finca.longitud]);
            });
            
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }
        }
    })
    .catch(function(error) {
        console.error('Error cargando fincas:', error);
    });
});
</script>

@endsection