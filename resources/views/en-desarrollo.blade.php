@extends('layouts.dashboard')

@section('title', 'Módulo en Desarrollo')

@section('content')

<div class="min-h-[70vh] flex items-center justify-center">
    <div class="text-center max-w-2xl mx-auto">
        
        <!-- Icono -->
        <div class="mb-8 relative">
            <div class="absolute inset-0 bg-gradient-to-r from-red-600/20 to-orange-600/20 rounded-full blur-3xl"></div>
            <div class="relative w-32 h-32 mx-auto bg-gradient-to-br from-yellow-400 to-red-500 rounded-full flex items-center justify-center shadow-2xl animate-pulse">
                <i class="fa-solid fa-tools text-white text-6xl"></i>
            </div>
        </div>

        <!-- Título -->
        <h1 class="text-5xl font-extrabold text-white mb-4">
            Módulo en Desarrollo
        </h1>
        
        <!-- Descripción -->
        <p class="text-xl text-white mb-2">
            <strong>{{ $modulo ?? 'Esta sección' }}</strong> estará disponible muy pronto
        </p>
        
        <p class="text-black mb-8">
            Estamos trabajando arduamente para traerte esta funcionalidad
        </p>

        <!-- Características del módulo -->
        @if(isset($caracteristicas))
        <div class="glass-effect rounded-2xl p-6 shadow-xl border-4 border-white/50 mb-8 text-left">
            <h3 class="text-lg font-bold text-gray-900 mb-4 flex items-center">
                <i class="fa-solid fa-list-check text-red-700 mr-2"></i>
                ¿Qué incluirá este módulo?
            </h3>
            <ul class="space-y-2">
                @foreach($caracteristicas as $caracteristica)
                <li class="flex items-start text-gray-700">
                    <i class="fa-solid fa-check-circle text-green-600 mr-3 mt-1"></i>
                    <span>{{ $caracteristica }}</span>
                </li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- Estado de desarrollo -->
        <div class="mb-8 inline-block">
            <div class="flex items-center gap-3 px-6 py-3 bg-gradient-to-r from-yellow-50 to-orange-50 border-2 border-yellow-300 rounded-xl">
                <div class="flex gap-2">
                    <span class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse"></span>
                    <span class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse" style="animation-delay: 0.2s"></span>
                    <span class="w-3 h-3 bg-yellow-500 rounded-full animate-pulse" style="animation-delay: 0.4s"></span>
                </div>
                <span class="font-bold text-yellow-800">En construcción</span>
            </div>
        </div>

        <!-- Progreso -->
        <div class="mb-8">
            <div class="flex items-center justify-between text-sm text-gray-600 mb-2">
                <span>Progreso estimado</span>
                <span class="font-bold">{{ $progreso ?? '25' }}%</span>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden shadow-inner">
                <div class="h-full bg-gradient-to-r from-red-600 to-red-700 rounded-full transition-all duration-1000 shadow-lg 
    w-[{{ $progreso ?? 25 }}%]">
</div>
            </div>
        </div>

        <!-- Botones de acción -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('dashboard') }}" 
               class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:shadow-xl hover:from-red-700 hover:to-red-800 transition-all">
                <i class="fa-solid fa-arrow-left mr-2"></i>
                Volver al Dashboard
            </a>
            
            <a href="mailto:soporte@bovisoft.com?subject=Consulta sobre módulo {{ $modulo ?? '' }}" 
               class="px-8 py-4 bg-white border-2 border-red-200 text-red-700 font-bold rounded-xl shadow-lg hover:shadow-xl hover:bg-red-50 transition-all">
                <i class="fa-solid fa-envelope mr-2"></i>
                Contactar Soporte
            </a>
        </div>

        <!-- Módulos disponibles -->
        <div class="mt-12 p-6 bg-gradient-to-r from-green-50 to-emerald-50 rounded-2xl border-2 border-green-200">
            <div class="flex items-center justify-center gap-3 mb-4">
                <i class="fa-solid fa-check-double text-green-600 text-2xl"></i>
                <h3 class="text-lg font-bold text-gray-900">Módulos ya disponibles</h3>
            </div>
            <div class="flex flex-wrap gap-3 justify-center">
                <a href="{{ route('dashboard') }}" class="px-4 py-2 bg-white border-2 border-green-300 text-green-700 font-semibold rounded-lg hover:bg-green-50 transition-all">
                    <i class="fa-solid fa-gauge-high mr-2"></i>Dashboard
                </a>
                <a href="{{ route('fincas.index') }}" class="px-4 py-2 bg-white border-2 border-green-300 text-green-700 font-semibold rounded-lg hover:bg-green-50 transition-all">
                    <i class="fa-solid fa-map-location-dot mr-2"></i>Fincas
                </a>
                <a href="{{ route('animales.index') }}" class="px-4 py-2 bg-white border-2 border-green-300 text-green-700 font-semibold rounded-lg hover:bg-green-50 transition-all">
                    <i class="fa-solid fa-map-location-dot mr-2"></i>Ganado
                </a>
            </div>
        </div>

    </div>
</div>

@endsection