@extends('layouts.dashboard')

@section('title', $finca->nombre)

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('fincas.index') }}" class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center mr-4 transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center">
                    {{ $finca->nombre }}
                    @if($finca->activa)
                        <span class="ml-3 px-3 py-1 bg-green-500 text-white text-sm font-bold rounded-full shadow-lg">
                            <i class="fa-solid fa-check-circle mr-1"></i>Activa
                        </span>
                    @else
                        <span class="ml-3 px-3 py-1 bg-gray-500 text-white text-sm font-bold rounded-full shadow-lg">
                            <i class="fa-solid fa-pause-circle mr-1"></i>Inactiva
                        </span>
                    @endif
                </h1>
                <p class="text-red-100 mt-1">{{ $finca->codigo }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('fincas.edit', $finca) }}" class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl">
                <i class="fa-solid fa-edit mr-2"></i>Editar
            </a>
            <form action="{{ route('fincas.destroy', $finca) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar esta finca? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all font-bold shadow-xl">
                    <i class="fa-solid fa-trash mr-2"></i>Eliminar
                </button>
            </form>
        </div>
    </div>

    <!-- Información General -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Estadísticas de la Finca -->
        <div class="lg:col-span-2 glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-chart-pie text-white"></i>
                </span>
                Resumen General
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <!-- Área -->
                <div class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border-2 border-orange-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-ruler-combined text-orange-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Área Total</p>
                    <p class="text-2xl font-bold text-gray-800">{{ number_format($finca->area, 2) }}</p>
                    <p class="text-xs text-orange-600 font-medium">hectáreas</p>
                </div>

                <!-- Total Animales -->
                <div class="p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border-2 border-red-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-cow text-red-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Total Animales</p>
                    <p class="text-2xl font-bold text-gray-800">{{ $finca->total_animales }}</p>
                    <p class="text-xs text-red-600 font-medium">cabezas</p>
                </div>

                <!-- Potreros -->
                <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-border-all text-green-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Potreros</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                    <p class="text-xs text-green-600 font-medium">registrados</p>
                </div>

                <!-- Alertas -->
                <div class="p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border-2 border-yellow-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-bell text-yellow-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Alertas</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                    <p class="text-xs text-yellow-600 font-medium">pendientes</p>
                </div>

                <!-- Producción -->
                <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border-2 border-purple-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-droplet text-purple-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Producción</p>
                    <p class="text-2xl font-bold text-gray-800">0</p>
                    <p class="text-xs text-purple-600 font-medium">litros/día</p>
                </div>

                <!-- Estado -->
                <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-heartbeat text-blue-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Estado</p>
                    <p class="text-lg font-bold text-gray-800">
                        @if($finca->activa)
                            <span class="text-green-600">Operativa</span>
                        @else
                            <span class="text-gray-600">Inactiva</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <!-- Ubicación -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-map-marker-alt text-white"></i>
                </span>
                Ubicación
            </h2>

            <div class="space-y-3">
                @if($finca->direccion)
                <div class="flex items-start">
                    <i class="fa-solid fa-location-dot text-red-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Dirección</p>
                        <p class="font-medium text-gray-800">{{ $finca->direccion }}</p>
                    </div>
                </div>
                @endif

                @if($finca->municipio)
                <div class="flex items-start">
                    <i class="fa-solid fa-city text-blue-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Municipio</p>
                        <p class="font-medium text-gray-800">{{ $finca->municipio }}</p>
                    </div>
                </div>
                @endif

                @if($finca->departamento)
                <div class="flex items-start">
                    <i class="fa-solid fa-flag text-orange-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Departamento</p>
                        <p class="font-medium text-gray-800">{{ $finca->departamento }}</p>
                    </div>
                </div>
                @endif

                @if($finca->latitud && $finca->longitud)
                <div class="flex items-start">
                    <i class="fa-solid fa-map-pin text-green-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Coordenadas</p>
                        <p class="font-mono text-sm text-gray-700">{{ number_format($finca->latitud, 6) }}, {{ number_format($finca->longitud, 6) }}</p>
                    </div>
                </div>
                @endif

                <div class="pt-3 border-t-2 border-gray-200">
                    <p class="text-xs text-gray-500 mb-1">Registrada el</p>
                    <p class="font-medium text-gray-800">{{ $finca->created_at->format('d/m/Y H:i') }}</p>
                </div>
            </div>
        </div>
    </div>

    @if($finca->descripcion)
    <!-- Descripción -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-align-left text-white"></i>
            </span>
            Descripción
        </h2>
        <p class="text-gray-700 leading-relaxed">{{ $finca->descripcion }}</p>
    </div>
    @endif

    <!-- Mapa -->
    @if($finca->latitud && $finca->longitud)
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-map text-white"></i>
            </span>
            Ubicación en el Mapa
        </h2>
        <div id="map" class="rounded-xl shadow-lg border-4 border-gray-300" style="height: 400px;"></div>
    </div>
    @endif

    <!-- Acciones Rápidas -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-bolt text-white"></i>
            </span>
            Gestión de la Finca
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('animales.create') }}?finca={{ $finca->id }}" class="p-6 bg-gradient-to-br from-red-50 to-red-100 hover:from-red-100 hover:to-red-200 border-2 border-red-200 rounded-xl transition-all group text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-plus text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Agregar Animal</p>
                <p class="text-xs text-gray-600 mt-1">Registrar nuevo</p>
            </a>

            <button class="p-6 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border-2 border-green-200 rounded-xl transition-all group text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-border-all text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Potreros</p>
                <p class="text-xs text-gray-600 mt-1">Gestionar divisiones</p>
            </button>

            <button class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border-2 border-blue-200 rounded-xl transition-all group text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-chart-line text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Producción</p>
                <p class="text-xs text-gray-600 mt-1">Ver registros</p>
            </button>

            <button class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 border-2 border-purple-200 rounded-xl transition-all group text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-file-alt text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Reportes</p>
                <p class="text-xs text-gray-600 mt-1">Generar informes</p>
            </button>
        </div>
    </div>
</div>

@if($finca->latitud && $finca->longitud)
@php
    $fincaData = [
        'lat' => floatval($finca->latitud),
        'lng' => floatval($finca->longitud),
        'nombre' => $finca->nombre,
        'codigo' => $finca->codigo,
        'area' => number_format($finca->area, 2),
        'direccion' => $finca->direccion ?? ''
    ];
@endphp

<script>
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        var fincaData = <?php echo json_encode($fincaData); ?>;
        
        var map = L.map('map').setView([fincaData.lat, fincaData.lng], 15);
        
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap contributors',
            maxZoom: 18
        }).addTo(map);
        
        var marker = L.marker([fincaData.lat, fincaData.lng], {
            icon: L.icon({
                iconUrl: 'https://raw.githubusercontent.com/pointhi/leaflet-color-markers/master/img/marker-icon-2x-red.png',
                shadowUrl: 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.7.1/images/marker-shadow.png',
                iconSize: [25, 41],
                iconAnchor: [12, 41],
                popupAnchor: [1, -34],
                shadowSize: [41, 41]
            })
        }).addTo(map);
        
        var popupContent = '<div class="p-3 text-center">';
        popupContent += '<p class="font-bold text-red-700 mb-2 text-lg">' + fincaData.nombre + '</p>';
        popupContent += '<p class="text-sm text-gray-600 mb-1"><strong>Código:</strong> ' + fincaData.codigo + '</p>';
        popupContent += '<p class="text-sm text-gray-600 mb-1"><strong>Área:</strong> ' + fincaData.area + ' ha</p>';
        
        if (fincaData.direccion) {
            popupContent += '<p class="text-xs text-gray-500 mt-2">' + fincaData.direccion + '</p>';
        }
        
        popupContent += '</div>';
        
        marker.bindPopup(popupContent).openPopup();
    });
})();
</script>
@endif
@endsection