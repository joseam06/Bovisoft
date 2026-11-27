@extends('layouts.dashboard')

@section('title', $potrero->nombre)

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('potreros.index') }}" class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center mr-4 transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center">
                    <span data-offline-owner="potreros" data-offline-field="nombre">{{ $potrero->nombre }}</span>
                    <span class="ml-3 px-3 py-1 
                        @if($potrero->estado === 'activo') bg-green-500
                        @elseif($potrero->estado === 'en_descanso') bg-orange-500
                        @else bg-gray-500
                        @endif
                        text-white text-sm font-bold rounded-full shadow-lg">
                        <span data-offline-owner="potreros" data-offline-field="estado">{{ ucfirst(str_replace('_', ' ', $potrero->estado)) }}</span>
                    </span>
                </h1>
                <p class="text-red-100 mt-1"><span data-offline-owner="potreros" data-offline-field="codigo">{{ $potrero->codigo }}</span></p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('potreros.edit', $potrero) }}" class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl">
                <i class="fa-solid fa-edit mr-2"></i>Editar
            </a>
            <form action="{{ route('potreros.destroy', $potrero) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este potrero? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE') <input type="hidden" name="base_version" value="{{ $potrero->version }}">
                <button type="submit" class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all font-bold shadow-xl">
                    <i class="fa-solid fa-trash mr-2"></i>Eliminar
                </button>
            </form>
        </div>
    </div>

    <!-- Información General -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
        <!-- Datos Principales -->
        <div class="lg:col-span-2 glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-info-circle text-white"></i>
                </span>
                Información General
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <!-- Código -->
                <div class="p-4 bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl border-2 border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-barcode text-gray-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Código</p>
                    <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="codigo">{{ $potrero->codigo }}</span></p>
                </div>

                <!-- Nombre -->
                <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-tag text-blue-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Nombre</p>
                    <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="nombre">{{ $potrero->nombre }}</span></p>
                </div>

                <!-- Área -->
                <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-ruler-combined text-green-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Área</p>
                    <p class="text-lg font-bold text-gray-800">
                        @if($potrero->area)
                            <span data-offline-owner="potreros" data-offline-field="area">{{ number_format($potrero->area, 2) }}</span> ha
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </p>
                </div>

                <!-- Tipo de Pasto -->
                <div class="p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border-2 border-yellow-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-leaf text-yellow-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Tipo de Pasto</p>
                    <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="tipo_pasto">{{ $potrero->tipo_pasto ?: 'No especificado' }}</span></p>
                </div>

                <!-- Capacidad -->
                <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border-2 border-purple-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-cow text-purple-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Capacidad</p>
                    <p class="text-lg font-bold text-gray-800">
                        @if($potrero->capacidad_animales)
                            <span data-offline-owner="potreros" data-offline-field="capacidad_animales">{{ $potrero->capacidad_animales }}</span> animales
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </p>
                </div>

                <!-- Animales Actuales -->
                <div class="p-4 bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl border-2 border-pink-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-users text-pink-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Animales Actuales</p>
                    <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="animales_actuales">{{ $potrero->animales_actuales }}</span></p>
                </div>

                <!-- Finca -->
                <div class="p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border-2 border-red-200 col-span-2">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-map-marker-alt text-red-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Finca</p>
                    <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="finca_nombre">{{ $potrero->finca->nombre }}</span></p>
                    <p class="text-xs text-gray-500 mt-1"><span data-offline-owner="potreros" data-offline-field="finca_nombre">{{ $potrero->finca->codigo }}</span></p>
                </div>

                <!-- Estado -->
                <div class="p-4 bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl border-2 border-teal-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-info-circle text-teal-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Estado</p>
                    <p class="text-lg font-bold text-gray-800">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                            @if($potrero->estado === 'activo') bg-green-100 text-green-800
                            @elseif($potrero->estado === 'en_descanso') bg-orange-100 text-orange-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            <span data-offline-owner="potreros" data-offline-field="estado">{{ ucfirst(str_replace('_', ' ', $potrero->estado)) }}</span>
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Estadísticas de Ocupación -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-chart-pie text-white"></i>
                </span>
                Ocupación
            </h2>

            <div class="space-y-4">
                <!-- Porcentaje de Ocupación -->
                <div class="text-center py-6">
                    <div class="relative inline-flex items-center justify-center w-32 h-32">
                        <svg class="transform -rotate-90 w-32 h-32">
                            <circle cx="64" cy="64" r="56" stroke="#e5e7eb" stroke-width="12" fill="none"/>
                            <circle cx="64" cy="64" r="56" 
                                stroke="{{ $potrero->porcentaje_ocupacion >= 90 ? '#ef4444' : ($potrero->porcentaje_ocupacion >= 70 ? '#f59e0b' : '#10b981') }}"
                                stroke-width="12" fill="none"
                                stroke-dasharray="{{ 2 * 3.14159 * 56 }}"
                                data-offline-occupancy-ring stroke-dashoffset="{{ 2 * 3.14159 * 56 * (1 - $potrero->porcentaje_ocupacion / 100) }}"
                                class="transition-all duration-1000"/>
                        </svg>
                        <span class="absolute text-2xl font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="porcentaje_ocupacion">{{ $potrero->porcentaje_ocupacion }}</span>%</span>
                    </div>
                    <p class="text-gray-600 font-medium mt-2">Ocupación</p>
                </div>

                <!-- Disponibilidad -->
                <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                    <p class="text-xs text-gray-600 mb-1">Disponibilidad</p>
                    <p class="text-2xl font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="disponibilidad">{{ $potrero->disponibilidad }}</span></p>
                    <p class="text-xs text-gray-500 mt-1">animales disponibles</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Rotación y Manejo -->
    @if($potrero->fecha_ultima_rotacion || $potrero->dias_descanso)
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-orange-600 to-orange-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-rotate text-white"></i>
            </span>
            Control de Rotación
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            @if($potrero->fecha_ultima_rotacion)
            <div data-offline-if="fecha_ultima_rotacion" class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-200">
                <i class="fa-solid fa-calendar text-blue-600 text-2xl mb-2"></i>
                <p class="text-xs text-gray-600 mb-1">Última Rotación</p>
                <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="fecha_ultima_rotacion">{{ $potrero->fecha_ultima_rotacion->format('d/m/Y') }}</span></p>
            </div>

            <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-200">
                <i class="fa-solid fa-clock text-green-600 text-2xl mb-2"></i>
                <p class="text-xs text-gray-600 mb-1">Días Transcurridos</p>
                <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="dias_desde_ultima_rotacion">{{ $potrero->dias_desde_ultima_rotacion }}</span> días</p>
            </div>
            @endif

            <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border-2 border-purple-200">
                <i class="fa-solid fa-moon text-purple-600 text-2xl mb-2"></i>
                <p class="text-xs text-gray-600 mb-1">Días de Descanso</p>
                <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="dias_descanso">{{ $potrero->dias_descanso }}</span> días</p>
            </div>

            <div class="p-4 bg-gradient-to-br 
                @if($potrero->requiere_rotacion) from-red-50 to-red-100 border-red-200
                @else from-yellow-50 to-yellow-100 border-yellow-200
                @endif rounded-xl border-2">
                <i class="fa-solid fa-bell 
                    @if($potrero->requiere_rotacion) text-red-600
                    @else text-yellow-600
                    @endif text-2xl mb-2"></i>
                <p class="text-xs text-gray-600 mb-1">Próxima Rotación</p>
                <p class="text-lg font-bold text-gray-800"><span data-offline-owner="potreros" data-offline-field="proxima_rotacion">{{ $potrero->proxima_rotacion }}</span></p>
            </div>
        </div>
    </div>
    @endif

    <!-- Observaciones -->
    @if($potrero->observaciones)
    <div data-offline-if="observaciones" class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-notes-medical text-white"></i>
            </span>
            Observaciones
        </h2>
        <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap"><span data-offline-owner="potreros" data-offline-field="observaciones">{{ $potrero->observaciones }}</span></p>
        </div>
    </div>
    @endif

    <!-- Animales en este Potrero -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-cow text-white"></i>
            </span>
            Animales en este Potrero (<span data-offline-owner="potreros" data-offline-field="animales_actuales">{{ $potrero->animales->count() }}</span>)
        </h2>

        @if($potrero->animales->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b-2 border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Código</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Nombre</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Tipo</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-700 uppercase">Edad</th>
                            <th class="px-6 py-3 text-center text-xs font-bold text-gray-700 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody data-offline-collection="animales" class="bg-white divide-y divide-gray-200">
                        @foreach($potrero->animales as $animal)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm font-mono text-gray-800"><span data-offline-owner="animales" data-offline-field="codigo">{{ $animal->codigo }}</span></td>
                                <td class="px-6 py-4 text-sm text-gray-800 font-medium"><span data-offline-owner="animales" data-offline-field="nombre">{{ $animal->nombre ?: 'Sin nombre' }}</span></td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                                        @if($animal->tipo === 'vaca') bg-pink-100 text-pink-800
                                        @elseif($animal->tipo === 'toro') bg-blue-100 text-blue-800
                                        @elseif($animal->tipo === 'ternero') bg-green-100 text-green-800
                                        @else bg-purple-100 text-purple-800
                                        @endif">
                                        <span data-offline-owner="animales" data-offline-field="tipo">{{ ucfirst($animal->tipo) }}</span>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-800"><span data-offline-owner="animales" data-offline-field="edad">{{ $animal->edad }}</span></td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('animales.show', $animal) }}" 
                                        class="inline-flex items-center px-3 py-1 bg-blue-500 hover:bg-blue-600 text-white rounded-lg text-xs font-bold transition-all">
                                        <i class="fa-solid fa-eye mr-1"></i>Ver
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-12 bg-white rounded-xl">
                <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-cow text-gray-400 text-3xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-2">No hay animales en este potrero</p>
                <p class="text-sm text-gray-500">Los animales aparecerán aquí cuando los asignes a este potrero</p>
            </div>
        @endif
    </div>
</div>
@endsection
