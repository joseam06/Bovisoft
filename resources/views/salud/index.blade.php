@extends('layouts.dashboard')

@section('title', 'Salud Animal - Panel General')

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-heart-pulse text-red-700 text-2xl"></i>
                </span>
                Salud Animal
            </h1>
            <p class="text-red-100 mt-1 ml-16">Panel general de gestión sanitaria del ganado</p>
        </div>
        <a href="{{ route('salud.create') }}"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white shadow-xl transition-all
                  bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 hover:-translate-y-0.5">
            <i class="fa-solid fa-plus"></i> Nuevo Registro
        </a>
    </div>

    {{-- Alert --}}
    @if(session('success'))
    <div id="alert-ok"
         class="flex items-center gap-3 p-4 rounded-xl glass-effect border-4 border-white/50 text-green-700 shadow-xl animate-fade-in">
        <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- Estadísticas Globales --}}
    @if(isset($estadisticas))
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @php
            $stats = [
                ['val' => $estadisticas['total'],          'label' => 'Total Registros',   'color' => 'text-gray-800',   'icon' => 'fa-clipboard-list'],
                ['val' => $estadisticas['vacunaciones'],   'label' => 'Vacunaciones',      'color' => 'text-blue-700',   'icon' => 'fa-syringe'],
                ['val' => $estadisticas['tratamientos'],   'label' => 'Tratamientos',      'color' => 'text-purple-700', 'icon' => 'fa-pills'],
                ['val' => $estadisticas['proximas_7dias'], 'label' => 'Próximas (7 días)', 'color' => 'text-yellow-600', 'icon' => 'fa-calendar-check'],
                ['val' => $estadisticas['en_carencia'],    'label' => 'En Carencia',       'color' => 'text-red-600',    'icon' => 'fa-triangle-exclamation'],
            ];
        @endphp
        @foreach($stats as $i => $s)
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl text-center transition-all hover:scale-105
                    {{ $i >= 3 && $s['val'] > 0 ? 'ring-2 ring-yellow-400' : '' }}
                    {{ $i === 4 && $s['val'] > 0 ? 'ring-red-400' : '' }}">
            <i class="fa-solid {{ $s['icon'] }} {{ $s['color'] }} text-2xl mb-2"></i>
            <p class="text-3xl font-bold {{ $s['color'] }}">{{ $s['val'] }}</p>
            <p class="text-gray-600 text-xs font-medium mt-1">{{ $s['label'] }}</p>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Tarjetas de Categorías (Clicables) --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        @foreach($categorias as $cat)
        <a href="{{ $cat['url'] }}"
           class="glass-effect border-4 border-white/50 rounded-2xl p-6 shadow-xl
                  hover:shadow-2xl hover:scale-[1.02] transition-all duration-300 cursor-pointer group
                  {{ $cat['color_borde'] ?? 'border-gray-300' }}">
            
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center gap-4">
                    <span class="w-16 h-16 bg-gradient-to-br {{ $cat['gradiente'] }} rounded-xl flex items-center justify-center shadow-lg group-hover:scale-110 transition-transform">
                        <i class="fa-solid {{ $cat['icono'] }} text-white text-2xl"></i>
                    </span>
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800 group-hover:text-gray-900">{{ $cat['nombre'] }}</h2>
                        <p class="text-gray-600 text-sm mt-1">{{ $cat['descripcion'] }}</p>
                    </div>
                </div>
                @if(isset($cat['count']) && $cat['count'] > 0)
                <span class="bg-white backdrop-blur-sm rounded-full px-4 py-2 text-lg font-bold text-gray-700 shadow-md border-2 border-gray-200 group-hover:scale-110 transition-transform">
                    {{ $cat['count'] }}
                </span>
                @endif
            </div>

            <ul class="text-gray-700 text-sm space-y-2 mb-4 pl-2">
                @foreach($cat['items'] as $item)
                <li class="flex items-center gap-2">
                    <i class="fa-solid fa-check-circle text-green-500 text-xs"></i> 
                    <span class="group-hover:text-gray-900 transition-colors">{{ $item }}</span>
                </li>
                @endforeach
            </ul>

            <div class="mt-4 pt-4 border-t-2 border-gray-200 flex items-center justify-between">
                <span class="text-sm font-semibold text-gray-600 group-hover:text-gray-800">Ver sección completa</span>
                <i class="fa-solid fa-arrow-right text-gray-600 group-hover:text-gray-800 group-hover:translate-x-1 transition-all"></i>
            </div>
        </a>
        @endforeach
    </div>

    {{-- Acceso Rápido a Todas las Funciones --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl p-6 shadow-xl">
        <h3 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
            <i class="fa-solid fa-grip text-red-600"></i>
            Acceso Rápido
        </h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
            @php
                $accesos = [
                    ['url' => route('salud.create'), 'icon' => 'fa-plus', 'text' => 'Nuevo', 'color' => 'from-red-600 to-red-700'],
                    ['url' => route('salud.preventivo'), 'icon' => 'fa-shield-virus', 'text' => 'Preventivo', 'color' => 'from-blue-500 to-blue-700'],
                    ['url' => route('salud.clinico'), 'icon' => 'fa-stethoscope', 'text' => 'Clínico', 'color' => 'from-purple-500 to-purple-700'],
                    ['url' => route('salud.reproductivo'), 'icon' => 'fa-heart', 'text' => 'Reproductivo', 'color' => 'from-pink-500 to-pink-700'],
                    ['url' => route('salud.seguimiento'), 'icon' => 'fa-chart-line', 'text' => 'Seguimiento', 'color' => 'from-orange-500 to-orange-700'],
                    ['url' => route('salud.index'), 'icon' => 'fa-list', 'text' => 'Ver Todo', 'color' => 'from-gray-600 to-gray-800'],
                ];
            @endphp
            @foreach($accesos as $acc)
            <a href="{{ $acc['url'] }}"
               class="flex flex-col items-center gap-2 p-4 rounded-xl bg-white border-2 border-gray-200 hover:border-gray-300 shadow-md hover:shadow-lg transition-all group">
                <div class="w-12 h-12 bg-gradient-to-br {{ $acc['color'] }} rounded-xl flex items-center justify-center shadow-md group-hover:scale-110 transition-transform">
                    <i class="fa-solid {{ $acc['icon'] }} text-white text-lg"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700 group-hover:text-gray-900">{{ $acc['text'] }}</span>
            </a>
            @endforeach
        </div>
    </div>

</div>

@push('scripts')
<script>
    setTimeout(() => { 
        const a = document.getElementById('alert-ok'); 
        if(a) {
            a.style.opacity = '0';
            a.style.transition = 'opacity 0.5s';
            setTimeout(() => a.remove(), 500);
        }
    }, 5000);
</script>
@endpush
@endsection