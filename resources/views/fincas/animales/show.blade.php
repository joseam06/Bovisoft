@extends('layouts.dashboard')

@section('title', $animal->nombre ?: $animal->codigo)

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('animales.index') }}" class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center mr-4 transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center">
                    {{ $animal->nombre ?: 'Animal ' . $animal->codigo }}
                    <span class="ml-3 px-3 py-1 
                        @if($animal->tipo === 'vaca') bg-pink-500
                        @elseif($animal->tipo === 'toro') bg-blue-500
                        @elseif($animal->tipo === 'ternero') bg-green-500
                        @else bg-purple-500
                        @endif
                        text-white text-sm font-bold rounded-full shadow-lg">
                        <i class="fa-solid fa-{{ $animal->tipo === 'vaca' ? 'venus' : ($animal->tipo === 'toro' ? 'mars' : 'baby') }} mr-1"></i>
                        {{ ucfirst($animal->tipo) }}
                    </span>
                </h1>
                <p class="text-red-100 mt-1">{{ $animal->codigo }}</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('animales.edit', $animal) }}" class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl">
                <i class="fa-solid fa-edit mr-2"></i>Editar
            </a>
            <form action="{{ route('animales.destroy', $animal) }}" method="POST" class="inline" onsubmit="return confirm('¿Estás seguro de eliminar este animal? Esta acción no se puede deshacer.')">
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
                    <p class="text-lg font-bold text-gray-800">{{ $animal->codigo }}</p>
                </div>

                <!-- Nombre -->
                <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-tag text-blue-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Nombre</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->nombre ?: 'Sin nombre' }}</p>
                </div>

                <!-- Sexo -->
                <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border-2 border-purple-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-{{ $animal->sexo === 'macho' ? 'mars' : 'venus' }} text-purple-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Sexo</p>
                    <p class="text-lg font-bold text-gray-800">{{ ucfirst($animal->sexo) }}</p>
                </div>

                <!-- Raza -->
                <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-dna text-green-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Raza</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->raza ?: 'No especificada' }}</p>
                </div>

                <!-- Edad -->
                <div class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border-2 border-orange-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-calendar text-orange-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Edad</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->edad }}</p>
                </div>

                <!-- Peso -->
                <div class="p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border-2 border-yellow-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-weight-scale text-yellow-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Peso Actual</p>
                    <p class="text-lg font-bold text-gray-800">
                        @if($animal->peso_actual)
                            {{ $animal->peso_actual }} kg
                        @else
                            <span class="text-gray-400">N/A</span>
                        @endif
                    </p>
                </div>

                <!-- Color -->
                @if($animal->color)
                <div class="p-4 bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl border-2 border-pink-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-palette text-pink-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Color</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->color }}</p>
                </div>
                @endif

                <!-- Finca -->
                <div class="p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border-2 border-red-200 col-span-2">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-map-marker-alt text-red-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Ubicación - Finca</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->finca->nombre }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $animal->finca->codigo }}</p>
                </div>

                <!-- Estado -->
                <div class="p-4 bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl border-2 border-teal-200">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fa-solid fa-heartbeat text-teal-600 text-2xl"></i>
                    </div>
                    <p class="text-xs text-gray-600 mb-1">Estado</p>
                    <p class="text-lg font-bold text-gray-800">
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                            @if($animal->estado === 'activo') bg-green-100 text-green-800
                            @else bg-gray-100 text-gray-800
                            @endif">
                            {{ ucfirst($animal->estado) }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Datos de Nacimiento y Registro -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-calendar-check text-white"></i>
                </span>
                Fechas
            </h2>

            <div class="space-y-3">
                @if($animal->fecha_nacimiento)
                <div class="flex items-start">
                    <i class="fa-solid fa-birthday-cake text-blue-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Fecha de Nacimiento</p>
                        <p class="font-medium text-gray-800">{{ $animal->fecha_nacimiento->format('d/m/Y') }}</p>
                        <p class="text-xs text-blue-600 mt-1">{{ $animal->edad }}</p>
                    </div>
                </div>
                @endif

                <div class="flex items-start pt-3 border-t-2 border-gray-200">
                    <i class="fa-solid fa-clipboard-check text-green-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Registrado el</p>
                        <p class="font-medium text-gray-800">{{ $animal->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>

                @if($animal->updated_at != $animal->created_at)
                <div class="flex items-start">
                    <i class="fa-solid fa-clock-rotate-left text-orange-600 mt-1 mr-3"></i>
                    <div>
                        <p class="text-xs text-gray-500">Última actualización</p>
                        <p class="font-medium text-gray-800">{{ $animal->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>

    @if($animal->observaciones)
    <!-- Observaciones -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-notes-medical text-white"></i>
            </span>
            Observaciones
        </h2>
        <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
            <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $animal->observaciones }}</p>
        </div>
    </div>
    @endif

    <!-- Historial y Acciones -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Historial de Salud (Preparado para futuras funcionalidades) -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-syringe text-white"></i>
                </span>
                Historial de Salud
            </h2>

            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-heart-pulse text-gray-400 text-3xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-2">Sin registros de salud</p>
                <p class="text-sm text-gray-500 mb-4">Próximamente: vacunaciones y tratamientos</p>
                <button class="px-4 py-2 bg-gradient-to-r from-green-600 to-green-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all opacity-50 cursor-not-allowed">
                    <i class="fa-solid fa-plus mr-2"></i>Agregar Registro
                </button>
            </div>
        </div>

        <!-- Producción (Preparado para futuras funcionalidades) -->
        <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-chart-line text-white"></i>
                </span>
                Producción
            </h2>

            <div class="text-center py-12">
                <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-droplet text-gray-400 text-3xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-2">Sin registros de producción</p>
                <p class="text-sm text-gray-500 mb-4">Próximamente: registro de producción lechera</p>
                <button class="px-4 py-2 bg-gradient-to-r from-purple-600 to-purple-700 text-white text-sm font-bold rounded-lg shadow-lg hover:shadow-xl transition-all opacity-50 cursor-not-allowed">
                    <i class="fa-solid fa-plus mr-2"></i>Agregar Registro
                </button>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
        <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-bolt text-white"></i>
            </span>
            Gestión del Animal
        </h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <a href="{{ route('animales.edit', $animal) }}" class="p-6 bg-gradient-to-br from-blue-50 to-blue-100 hover:from-blue-100 hover:to-blue-200 border-2 border-blue-200 rounded-xl transition-all group text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-blue-600 to-blue-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-edit text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Editar Datos</p>
                <p class="text-xs text-gray-600 mt-1">Actualizar información</p>
            </a>

            <button class="p-6 bg-gradient-to-br from-green-50 to-green-100 hover:from-green-100 hover:to-green-200 border-2 border-green-200 rounded-xl transition-all group text-center opacity-50 cursor-not-allowed">
                <div class="w-16 h-16 bg-gradient-to-br from-green-600 to-green-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-syringe text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Vacunación</p>
                <p class="text-xs text-gray-600 mt-1">Próximamente</p>
            </button>

            <button class="p-6 bg-gradient-to-br from-purple-50 to-purple-100 hover:from-purple-100 hover:to-purple-200 border-2 border-purple-200 rounded-xl transition-all group text-center opacity-50 cursor-not-allowed">
                <div class="w-16 h-16 bg-gradient-to-br from-purple-600 to-purple-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-weight-scale text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Registro Peso</p>
                <p class="text-xs text-gray-600 mt-1">Próximamente</p>
            </button>

            <a href="{{ route('fincas.show', $animal->finca) }}" class="p-6 bg-gradient-to-br from-orange-50 to-orange-100 hover:from-orange-100 hover:to-orange-200 border-2 border-orange-200 rounded-xl transition-all group text-center">
                <div class="w-16 h-16 bg-gradient-to-br from-orange-600 to-orange-700 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform shadow-lg">
                    <i class="fa-solid fa-map-marked-alt text-white text-2xl"></i>
                </div>
                <p class="font-bold text-gray-800">Ver Finca</p>
                <p class="text-xs text-gray-600 mt-1">{{ $animal->finca->nombre }}</p>
            </a>
        </div>
    </div>
</div>
@endsection