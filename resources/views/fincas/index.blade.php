@extends('layouts.dashboard')

@section('title', 'Mis Fincas')

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center mr-4 shadow-lg">
                    <i class="fa-solid fa-map-location-dot text-red-700 text-2xl"></i>
                </span>
                Mis Fincas
            </h1>
            <p class="text-red-100 mt-2 ml-16">Administra todas tus propiedades ganaderas</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('fincas.create') }}" class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                <i class="fa-solid fa-plus mr-2"></i>Nueva Finca
            </a>
            <a href="{{ route('dashboard') }}" class="px-6 py-3 bg-white/20 hover:bg-white/30 text-white rounded-xl transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
                <i class="fa-solid fa-arrow-left mr-2"></i>Dashboard
            </a>
        </div>
    </div>

    <!-- Mensajes de éxito -->
    @if(session('success'))
        <div class="glass-effect rounded-xl p-4 mb-6 border-2 border-green-300 bg-green-50">
            <div class="flex items-center">
                <div class="w-10 h-10 bg-green-600 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-check-circle text-white text-lg"></i>
                </div>
                <p class="text-green-800 font-bold">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Estadísticas Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
        <!-- Total Fincas -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Total Fincas</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-green-600 to-green-800 bg-clip-text text-transparent">
                        {{ $fincas->count() }}
                    </h3>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-green-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-map-marked text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Fincas Activas -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Activas</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-blue-800 bg-clip-text text-transparent">
                        {{ $fincas->where('activa', true)->count() }}
                    </h3>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-check-circle text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Área Total -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Área Total</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-orange-600 to-orange-800 bg-clip-text text-transparent">
                        {{ number_format($fincas->sum('area'), 1) }}
                    </h3>
                    <p class="text-orange-600 text-xs font-medium">hectáreas</p>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-orange-600 to-orange-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-ruler-combined text-white text-3xl"></i>
                </div>
            </div>
        </div>

        <!-- Total Animales -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 card-hover">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm font-medium mb-1">Total Animales</p>
                    <h3 class="text-4xl font-bold bg-gradient-to-r from-red-600 to-red-800 bg-clip-text text-transparent">
                        {{ $fincas->sum('total_animales') }}
                    </h3>
                </div>
                <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-800 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-cow text-white text-3xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <div class="flex flex-col md:flex-row gap-4">
            <!-- Búsqueda -->
            <div class="flex-1">
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <i class="fa-solid fa-search text-gray-400 text-lg"></i>
                    </div>
                    <input type="text" 
                           id="buscar-finca" 
                           placeholder="Buscar por nombre, código o ubicación..."
                           class="w-full pl-12 pr-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all">
                </div>
            </div>

            <!-- Filtro Estado -->
            <select id="filtro-estado" class="px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all font-medium">
                <option value="todas">Todas las fincas</option>
                <option value="activas">Solo activas</option>
                <option value="inactivas">Solo inactivas</option>
            </select>

            <!-- Ordenar -->
            <select id="ordenar" class="px-4 py-3 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-red-500 focus:border-red-500 transition-all font-medium">
                <option value="reciente">Más recientes</option>
                <option value="nombre">Por nombre</option>
                <option value="area">Por área</option>
            </select>
        </div>
    </div>

    <!-- Grid de Fincas -->
    @if($fincas->count() > 0)
        <div id="fincas-grid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($fincas as $finca)
                <div class="finca-card glass-effect rounded-2xl shadow-xl overflow-hidden border-4 border-white/50 card-hover transition-all"
                     data-nombre="{{ strtolower($finca->nombre) }}"
                     data-codigo="{{ strtolower($finca->codigo) }}"
                     data-ubicacion="{{ strtolower($finca->municipio . ' ' . $finca->departamento) }}"
                     data-activa="{{ $finca->activa ? 'true' : 'false' }}"
                     data-area="{{ $finca->area }}">
                    
                    <!-- Header de la tarjeta -->
                    <div class="bg-gradient-to-r from-red-600 to-red-800 p-4">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-1">{{ $finca->nombre }}</h3>
                                <p class="text-red-100 text-sm font-medium">{{ $finca->codigo }}</p>
                            </div>
                            @if($finca->activa)
                                <span class="px-3 py-1 bg-green-500 text-white text-xs font-bold rounded-full shadow-lg">
                                    <i class="fa-solid fa-check-circle mr-1"></i>Activa
                                </span>
                            @else
                                <span class="px-3 py-1 bg-gray-500 text-white text-xs font-bold rounded-full shadow-lg">
                                    <i class="fa-solid fa-pause-circle mr-1"></i>Inactiva
                                </span>
                            @endif
                        </div>
                    </div>

                    <!-- Contenido -->
                    <div class="p-6">
                        <!-- Información Principal -->
                        <div class="space-y-3 mb-4">
                            <div class="flex items-center text-gray-700">
                                <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fa-solid fa-ruler-combined text-orange-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Área</p>
                                    <p class="font-bold">{{ number_format($finca->area, 2) }} hectáreas</p>
                                </div>
                            </div>

                            @if($finca->municipio || $finca->departamento)
                            <div class="flex items-center text-gray-700">
                                <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fa-solid fa-location-dot text-blue-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Ubicación</p>
                                    <p class="font-bold text-sm">{{ $finca->municipio }}{{ $finca->departamento ? ', ' . $finca->departamento : '' }}</p>
                                </div>
                            </div>
                            @endif

                            <div class="flex items-center text-gray-700">
                                <div class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center mr-3">
                                    <i class="fa-solid fa-cow text-red-600"></i>
                                </div>
                                <div>
                                    <p class="text-xs text-gray-500">Animales</p>
                                    <p class="font-bold">{{ $finca->total_animales }} cabezas</p>
                                </div>
                            </div>
                        </div>

                        @if($finca->descripcion)
                        <div class="mb-4 p-3 bg-gray-50 rounded-lg border-2 border-gray-200">
                            <p class="text-xs text-gray-600 line-clamp-2">{{ $finca->descripcion }}</p>
                        </div>
                        @endif

                        <!-- Botones de Acción -->
                        <div class="flex gap-2">
                            <a href="{{ route('fincas.show', $finca) }}" 
                               class="flex-1 px-4 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg hover:shadow-xl text-center">
                                <i class="fa-solid fa-eye mr-2"></i>Ver Detalles
                            </a>
                            <a href="{{ route('fincas.edit', $finca) }}" 
                               class="px-4 py-3 bg-white border-2 border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded-xl transition-all shadow-lg">
                                <i class="fa-solid fa-edit"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Footer con fecha -->
                    <div class="px-6 py-3 bg-gray-50 border-t-2 border-gray-200">
                        <p class="text-xs text-gray-500">
                            <i class="fa-solid fa-calendar mr-1"></i>
                            Registrada el {{ $finca->created_at->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Sin resultados de búsqueda -->
        <div id="sin-resultados" class="hidden text-center py-16">
            <div class="w-32 h-32 bg-gradient-to-br from-gray-300 to-gray-400 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                <i class="fas fa-search text-white text-6xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-white mb-2">No se encontraron fincas</h3>
            <p class="text-red-100 mb-6">Intenta con otros términos de búsqueda</p>
        </div>

    @else
        <!-- Estado vacío -->
        <div class="glass-effect rounded-2xl shadow-2xl p-16 border-4 border-white/50 text-center">
            <div class="w-32 h-32 bg-gradient-to-br from-red-600 to-red-800 rounded-3xl flex items-center justify-center mx-auto mb-6 shadow-2xl">
                <i class="fas fa-map-marked-alt text-white text-6xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-black mb-2">No tienes fincas registradas</h3>
            <p class="text-red-500 mb-6">Comienza agregando tu primera finca al sistema</p>
            <a href="{{ route('fincas.create') }}" class="inline-flex items-center px-8 py-4 bg-white text-red-700 font-bold rounded-xl transition-all shadow-xl hover:shadow-2xl transform hover:-translate-y-1">
                <i class="fas fa-plus mr-2"></i>Registrar Primera Finca
            </a>
        </div>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const buscarInput = document.getElementById('buscar-finca');
    const filtroEstado = document.getElementById('filtro-estado');
    const ordenarSelect = document.getElementById('ordenar');
    const fincasGrid = document.getElementById('fincas-grid');
    const sinResultados = document.getElementById('sin-resultados');
    
    function filtrarFincas() {
        const busqueda = buscarInput.value.toLowerCase();
        const estado = filtroEstado.value;
        let fincasVisibles = 0;
        
        document.querySelectorAll('.finca-card').forEach(card => {
            const nombre = card.dataset.nombre;
            const codigo = card.dataset.codigo;
            const ubicacion = card.dataset.ubicacion;
            const activa = card.dataset.activa === 'true';
            
            // Filtro de búsqueda
            const coincideBusqueda = !busqueda || 
                nombre.includes(busqueda) || 
                codigo.includes(busqueda) || 
                ubicacion.includes(busqueda);
            
            // Filtro de estado
            const coincideEstado = estado === 'todas' || 
                (estado === 'activas' && activa) || 
                (estado === 'inactivas' && !activa);
            
            if (coincideBusqueda && coincideEstado) {
                card.style.display = 'block';
                fincasVisibles++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // Mostrar/ocultar mensaje de sin resultados
        if (fincasVisibles === 0 && fincasGrid) {
            fincasGrid.style.display = 'none';
            sinResultados.style.display = 'block';
        } else if (fincasGrid) {
            fincasGrid.style.display = 'grid';
            sinResultados.style.display = 'none';
        }
    }
    
    function ordenarFincas() {
        const orden = ordenarSelect.value;
        const grid = fincasGrid;
        if (!grid) return;
        
        const cards = Array.from(document.querySelectorAll('.finca-card'));
        
        cards.sort((a, b) => {
            if (orden === 'nombre') {
                return a.dataset.nombre.localeCompare(b.dataset.nombre);
            } else if (orden === 'area') {
                return parseFloat(b.dataset.area) - parseFloat(a.dataset.area);
            }
            return 0; // reciente (orden original)
        });
        
        cards.forEach(card => grid.appendChild(card));
    }
    
    // Event listeners
    if (buscarInput) {
        buscarInput.addEventListener('input', filtrarFincas);
    }
    if (filtroEstado) {
        filtroEstado.addEventListener('change', filtrarFincas);
    }
    if (ordenarSelect) {
        ordenarSelect.addEventListener('change', ordenarFincas);
    }
});
</script>
@endsection