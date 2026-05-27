@extends('layouts.dashboard')

@section('title', 'Alertas')

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- Encabezado --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-bell text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Centro de Alertas</h1>
                    <p class="text-gray-500 text-sm mt-1">
                        Seguimiento de tratamientos, aplicaciones pendientes y períodos de carencia
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                @if($alertas->count() > 0)
                    <span class="px-4 py-2 bg-yellow-100 text-yellow-800 font-bold rounded-full text-sm border-2 border-yellow-300">
                        <i class="fa-solid fa-bell mr-1"></i>
                        {{ $alertas->count() }} alerta{{ $alertas->count() !== 1 ? 's' : '' }} activa{{ $alertas->count() !== 1 ? 's' : '' }}
                    </span>
                @endif
                <a href="{{ route('salud.index') }}"
                   class="flex items-center space-x-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                    <i class="fa-solid fa-heart-pulse"></i>
                    <span>Ir a Salud</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Resumen por nivel --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        {{-- Críticas --}}
        <div class="glass-effect border-4 border-red-300/60 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Críticas</p>
                    <p class="text-4xl font-extrabold text-red-600">{{ $criticas->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-circle-exclamation text-red-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Aplicaciones vencidas</p>
        </div>

        {{-- Urgentes --}}
        <div class="glass-effect border-4 border-orange-300/60 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Urgentes</p>
                    <p class="text-4xl font-extrabold text-orange-600">{{ $urgentes->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-bell text-orange-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Pendientes hoy</p>
        </div>

        {{-- Advertencias --}}
        <div class="glass-effect border-4 border-yellow-300/60 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Próximas</p>
                    <p class="text-4xl font-extrabold text-yellow-600">{{ $advertencias->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-yellow-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-clock text-yellow-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">En los próximos 7 días</p>
        </div>

        {{-- Informativas --}}
        <div class="glass-effect border-4 border-blue-300/60 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Informativas</p>
                    <p class="text-4xl font-extrabold text-blue-600">{{ $informativas->count() }}</p>
                </div>
                <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-info-circle text-blue-600 text-xl"></i>
                </div>
            </div>
            <p class="text-xs text-gray-500 mt-3">Tratamientos y carencias</p>
        </div>
    </div>

    {{-- Sin alertas --}}
    @if($alertas->isEmpty())
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-16 text-center">
            <div class="w-24 h-24 bg-gradient-to-br from-green-100 to-green-200 rounded-full flex items-center justify-center mx-auto mb-6 shadow-lg">
                <i class="fa-solid fa-check-circle text-green-500 text-4xl"></i>
            </div>
            <h3 class="text-2xl font-bold text-gray-800 mb-2">Todo en orden</h3>
            <p class="text-gray-500">No hay alertas activas en este momento.</p>
            <p class="text-sm text-gray-400 mt-2">Las alertas se generan automáticamente desde los registros del módulo Salud.</p>
        </div>
    @else

        {{-- Sección: Críticas --}}
        @if($criticas->count() > 0)
        <div class="glass-effect border-4 border-red-300/60 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-800 px-6 py-4 flex items-center space-x-3">
                <i class="fa-solid fa-circle-exclamation text-white text-xl"></i>
                <h2 class="text-white font-bold text-lg">Alertas Críticas — Aplicaciones Vencidas</h2>
                <span class="ml-auto bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">{{ $criticas->count() }}</span>
            </div>
            <div class="divide-y divide-red-100">
                @foreach($criticas as $alerta)
                    @include('alertas._fila', ['alerta' => $alerta, 'bgHover' => 'hover:bg-red-50'])
                @endforeach
            </div>
        </div>
        @endif

        {{-- Sección: Urgentes --}}
        @if($urgentes->count() > 0)
        <div class="glass-effect border-4 border-orange-300/60 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-orange-500 to-orange-700 px-6 py-4 flex items-center space-x-3">
                <i class="fa-solid fa-bell text-white text-xl"></i>
                <h2 class="text-white font-bold text-lg">Urgentes — Pendientes Hoy</h2>
                <span class="ml-auto bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">{{ $urgentes->count() }}</span>
            </div>
            <div class="divide-y divide-orange-100">
                @foreach($urgentes as $alerta)
                    @include('alertas._fila', ['alerta' => $alerta, 'bgHover' => 'hover:bg-orange-50'])
                @endforeach
            </div>
        </div>
        @endif

        {{-- Sección: Advertencias --}}
        @if($advertencias->count() > 0)
        <div class="glass-effect border-4 border-yellow-300/60 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-yellow-500 to-yellow-600 px-6 py-4 flex items-center space-x-3">
                <i class="fa-solid fa-clock text-white text-xl"></i>
                <h2 class="text-white font-bold text-lg">Próximas — Esta Semana</h2>
                <span class="ml-auto bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">{{ $advertencias->count() }}</span>
            </div>
            <div class="divide-y divide-yellow-100">
                @foreach($advertencias as $alerta)
                    @include('alertas._fila', ['alerta' => $alerta, 'bgHover' => 'hover:bg-yellow-50'])
                @endforeach
            </div>
        </div>
        @endif

        {{-- Sección: Informativas --}}
        @if($informativas->count() > 0)
        <div class="glass-effect border-4 border-blue-200/60 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-6 py-4 flex items-center space-x-3">
                <i class="fa-solid fa-info-circle text-white text-xl"></i>
                <h2 class="text-white font-bold text-lg">Informativas — Tratamientos y Carencias</h2>
                <span class="ml-auto bg-white/20 text-white text-xs font-bold px-3 py-1 rounded-full">{{ $informativas->count() }}</span>
            </div>
            <div class="divide-y divide-blue-100">
                @foreach($informativas as $alerta)
                    @include('alertas._fila', ['alerta' => $alerta, 'bgHover' => 'hover:bg-blue-50'])
                @endforeach
            </div>
        </div>
        @endif

    @endif

</div>
@endsection