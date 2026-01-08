@extends('layouts.dashboard')

@section('title', 'Dashboard')

@section('content')

<!-- Page Header con efecto degradado -->
<div class="mb-8 relative">
    <div class="absolute inset-0 bg-gradient-to-r from-red-600/10 to-black/10 rounded-3xl blur-3xl"></div>
    <div class="relative glass-effect rounded-2xl p-6 shadow-xl border-4 border-white/50">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <span class="inline-block px-4 py-2 bg-red-100 text-red-800 rounded-full text-sm font-semibold mb-3">
                    🐄 Panel de Control
                </span>
                <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 mb-2">Dashboard Bovisoft</h1>
                <p class="text-gray-600 text-lg">Gestión inteligente de tu operación ganadera</p>
            </div>
            <div class="flex gap-3">
                <button class="px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-red-700 hover:to-red-800 transition-all">
                    <i class="fa-solid fa-plus mr-2"></i>
                    Nuevo Registro
                </button>
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
                    <h3 class="text-4xl font-extrabold bg-gradient-to-r from-red-700 to-red-900 bg-clip-text text-transparent">0</h3>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t-2 border-red-100">
                <span class="text-xs text-gray-600 flex items-center">
                    <i class="fa-solid fa-circle text-green-500 text-xs mr-2"></i>
                    Sistema activo
                </span>
                <a href="#" class="text-xs text-red-700 hover:text-red-800 font-semibold flex items-center group">
                    Ver más
                    <i class="fa-solid fa-arrow-right ml-1 group-hover:translate-x-1 transition-transform"></i>
                </a>
            </div>
        </div>
        <div class="h-2 bg-gradient-to-r from-red-600 to-red-800"></div>
    </div>

    <!-- Fincas Registradas Card -->
    <div class="stat-card rounded-2xl shadow-xl overflow-hidden card-hover border-4 border-white/50 animate-slide-up" style="animation-delay: 0.1s">
        <div class="p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-600 to-red-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-map-location-dot text-white text-2xl"></i>
                </div>
                <div class="text-right">
                    <p class="text-sm font-semibold text-gray-600 mb-1">Fincas Activas</p>
                    <h3 class="text-4xl font-extrabold bg-gradient-to-r from-orange-600 to-red-700 bg-clip-text text-transparent">0</h3>
                </div>
            </div>
            <div class="flex items-center justify-between pt-4 border-t-2 border-orange-100">
                <span class="text-xs text-gray-600 flex items-center">
                    <i class="fa-solid fa-check-circle text-green-500 text-xs mr-2"></i>
                    Todas operativas
                </span>
                <a href="#mapa-fincas" class="text-xs text-orange-700 hover:text-orange-800 font-semibold flex items-center group">
                    Ver mapa
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

<!-- Separador decorativo -->
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
        
        <div class="relative h-80">
            <canvas id="distribucionChart"></canvas>
        </div>
        
        <div class="grid grid-cols-2 gap-3 mt-6">
            <div class="flex items-center p-3 bg-red-50 rounded-xl border-2 border-red-100">
                <div class="w-4 h-4 rounded-full bg-red-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Vacas</p>
                    <p class="font-bold text-gray-900">0 (0%)</p>
                </div>
            </div>
            <div class="flex items-center p-3 bg-blue-50 rounded-xl border-2 border-blue-100">
                <div class="w-4 h-4 rounded-full bg-blue-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Terneros</p>
                    <p class="font-bold text-gray-900">0 (0%)</p>
                </div>
            </div>
            <div class="flex items-center p-3 bg-purple-50 rounded-xl border-2 border-purple-100">
                <div class="w-4 h-4 rounded-full bg-purple-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Toros</p>
                    <p class="font-bold text-gray-900">0 (0%)</p>
                </div>
            </div>
            <div class="flex items-center p-3 bg-yellow-50 rounded-xl border-2 border-yellow-100">
                <div class="w-4 h-4 rounded-full bg-yellow-500 mr-3"></div>
                <div>
                    <p class="text-xs text-gray-600">Novillas</p>
                    <p class="font-bold text-gray-900">0 (0%)</p>
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
            <p class="text-sm text-gray-600 mt-1 ml-13">Visualización geográfica</p>
        </div>
        
        <div id="map" class="shadow-xl border-4 border-white/50"></div>
        
        <div class="mt-6 p-4 bg-gradient-to-r from-orange-50 to-red-50 rounded-xl border-2 border-orange-200">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <i class="fa-solid fa-info-circle text-orange-600 text-2xl"></i>
                    <div>
                        <p class="font-bold text-gray-900 text-sm">¿No hay fincas registradas?</p>
                        <p class="text-xs text-gray-600">Agrega tu primera finca para verla en el mapa</p>
                    </div>
                </div>
                <button class="px-4 py-2 bg-gradient-to-r from-orange-600 to-red-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all">
                    Agregar Finca
                </button>
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
    
    <!-- Alertas y Notificaciones -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
        <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center">
            <span class="w-8 h-8 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-lg flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-bell text-white text-sm"></i>
            </span>
            Alertas Recientes
        </h2>
        
        <div class="space-y-3">
            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-check-circle text-green-600 text-3xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-2">¡Todo en orden!</p>
                <p class="text-sm text-gray-500">No hay alertas pendientes por el momento</p>
            </div>
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
            <button class="p-4 bg-gradient-to-br from-red-50 to-red-100 hover:from-red-100 hover:to-red-200 border-2 border-red-200 rounded-xl transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fa-solid fa-plus text-white text-xl"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Nuevo Animal</span>
                </div>
            </button>

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

            <button class="p-4 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border-2 border-green-200 rounded-xl transition-all group">
                <div class="flex flex-col items-center text-center">
                    <div class="w-12 h-12 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center mb-3 group-hover:scale-110 transition-transform shadow-lg">
                        <i class="fa-solid fa-map-marked text-white text-xl"></i>
                    </div>
                    <span class="text-sm font-bold text-gray-800">Nueva Finca</span>
                </div>
            </button>
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

<!-- Scripts para las gráficas -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    // Configuración de colores
    const colors = {
        red: {
            solid: '#dc2626',
            gradient: ['#dc2626', '#991b1b'],
            light: 'rgba(220, 38, 38, 0.1)'
        },
        blue: {
            solid: '#3b82f6',
            light: 'rgba(59, 130, 246, 0.1)'
        },
        purple: {
            solid: '#9333ea',
            light: 'rgba(147, 51, 234, 0.1)'
        },
        yellow: {
            solid: '#eab308',
            light: 'rgba(234, 179, 8, 0.1)'
        }
    };

    // Gráfico de Distribución (Donut)
    const distribucionCtx = document.getElementById('distribucionChart');
    if (distribucionCtx) {
        new Chart(distribucionCtx, {
            type: 'doughnut',
            data: {
                labels: ['Vacas', 'Terneros', 'Toros', 'Novillas'],
                datasets: [{
                    data: [0, 0, 0, 0], // Valores en 0
                    backgroundColor: [
                        colors.red.solid,
                        colors.blue.solid,
                        colors.purple.solid,
                        colors.yellow.solid
                    ],
                    borderWidth: 4,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.label + ': ' + context.parsed + ' animales';
                            }
                        },
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        }
                    }
                },
                cutout: '70%'
            }
        });
    }

    // Gráfico de Producción (Barras)
    const produccionCtx = document.getElementById('produccionChart');
    if (produccionCtx) {
        new Chart(produccionCtx, {
            type: 'bar',
            data: {
                labels: ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'],
                datasets: [{
                    label: 'Litros de leche',
                    data: [0, 0, 0, 0, 0, 0, 0], // Valores en 0
                    backgroundColor: function(context) {
                        const ctx = context.chart.ctx;
                        const gradient = ctx.createLinearGradient(0, 0, 0, 400);
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
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        cornerRadius: 8,
                        titleFont: {
                            size: 14,
                            weight: 'bold'
                        },
                        bodyFont: {
                            size: 13
                        },
                        callbacks: {
                            label: function(context) {
                                return context.parsed.y + ' litros';
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)',
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            color: '#6b7280'
                        }
                    },
                    x: {
                        grid: {
                            display: false,
                            drawBorder: false
                        },
                        ticks: {
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            color: '#6b7280'
                        }
                    }
                }
            }
        });
    }

    // Inicializar mapa de Leaflet
    const map = L.map('map').setView([8.7479, -75.8814], 13); // Coordenadas de Montería
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 18,
    }).addTo(map);
    
    // Agregar marcador de ejemplo (puedes comentar esto si no quieres el marcador)
    const marker = L.marker([8.7479, -75.8814]).addTo(map);
    marker.bindPopup('<div class="text-center p-2"><b class="text-red-700">Montería, Córdoba</b><br><span class="text-sm text-gray-600">Ubicación predeterminada</span></div>');
    
    // Aquí puedes agregar más marcadores cuando tengas fincas
    // Ejemplo:
    // const finca1 = L.marker([lat, lng]).addTo(map);
    // finca1.bindPopup('<b>Nombre de la Finca</b><br>Descripción');
});
</script>

@endsection