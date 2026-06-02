@extends('layouts.dashboard')

@section('title', 'Producción — ' . $registro->codigo)

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('produccion.index') }}"
                class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white">{{ $registro->codigo }}</h1>
                <p class="text-red-100 mt-1">Detalle del registro de producción</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('produccion.edit', $registro) }}"
                class="inline-flex items-center gap-2 px-5 py-2.5 bg-yellow-500 hover:bg-yellow-600 text-white font-bold rounded-xl shadow-lg transition-all">
                <i class="fa-solid fa-pen"></i> Editar
            </a>
            <form action="{{ route('produccion.destroy', $registro) }}" method="POST"
                onsubmit="return confirm('¿Eliminar este registro?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-bold rounded-xl shadow-lg transition-all">
                    <i class="fa-solid fa-trash"></i> Eliminar
                </button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Datos principales --}}
        <div class="lg:col-span-2 glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                <span class="w-9 h-9 bg-gradient-to-br from-purple-600 to-red-700 rounded-xl flex items-center justify-center shadow">
                    <i class="fa-solid fa-droplet text-white text-sm"></i>
                </span>
                Datos del Registro
            </h2>
            <dl class="grid grid-cols-2 gap-5 text-sm">
                <div>
                    <dt class="text-gray-500 font-medium">Código</dt>
                    <dd class="font-mono font-bold text-gray-800 mt-0.5">{{ $registro->codigo }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Fecha</dt>
                    <dd class="font-bold text-gray-800 mt-0.5">{{ $registro->fecha->format('d/m/Y') }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Animal</dt>
                    <dd class="mt-0.5">
                        <a href="{{ route('animales.show', $registro->animal) }}" class="font-bold text-red-600 hover:underline">
                            {{ $registro->animal->nombre ?? $registro->animal->codigo ?? '—' }}
                        </a>
                        <span class="text-xs text-gray-500 ml-1">({{ ucfirst($registro->animal->tipo ?? '') }})</span>
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Finca</dt>
                    <dd class="font-bold text-gray-800 mt-0.5">{{ $registro->finca->nombre ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Sesión</dt>
                    <dd class="mt-0.5">
                        @if($registro->sesion)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $registro->sesion === 'manana' ? 'bg-yellow-100 text-yellow-800' : ($registro->sesion === 'tarde' ? 'bg-orange-100 text-orange-800' : 'bg-indigo-100 text-indigo-800') }}">
                                {{ $registro->sesion_formateada }}
                            </span>
                        @else
                            <span class="text-gray-400">No especificada</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Calidad</dt>
                    <dd class="mt-0.5">
                        @if($registro->calidad)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $registro->calidad === 'excelente' ? 'bg-green-100 text-green-800' : ($registro->calidad === 'buena' ? 'bg-blue-100 text-blue-800' : ($registro->calidad === 'rechazada' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-700')) }}">
                                {{ $registro->calidad_formateada }}
                            </span>
                        @else
                            <span class="text-gray-400">Sin clasificar</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-gray-500 font-medium">Registrado</dt>
                    <dd class="text-gray-800 mt-0.5">{{ $registro->created_at->format('d/m/Y H:i') }}</dd>
                </div>
            </dl>

            @if($registro->observaciones)
            <div class="mt-6 p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                <p class="text-xs font-semibold text-gray-500 mb-1 uppercase tracking-wider">Observaciones</p>
                <p class="text-gray-700 text-sm leading-relaxed whitespace-pre-wrap">{{ $registro->observaciones }}</p>
            </div>
            @endif
        </div>

        {{-- Panel de litros + acciones --}}
        <div class="space-y-5">
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6 text-center">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Producción registrada</p>
                <div class="flex items-end justify-center gap-1 mb-2">
                    <span class="text-6xl font-extrabold bg-gradient-to-br from-purple-600 to-red-700 bg-clip-text text-transparent">
                        {{ number_format($registro->litros, 1) }}
                    </span>
                    <span class="text-2xl font-bold text-gray-500 mb-2">L</span>
                </div>
                <p class="text-sm text-gray-500">litros — {{ $registro->fecha->format('d/m/Y') }}</p>
            </div>

            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <i class="fa-solid fa-bolt text-red-600"></i> Acciones rápidas
                </h3>
                <div class="space-y-2">
                    <a href="{{ route('produccion.create', ['animal_id' => $registro->animal_id]) }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all text-sm shadow-lg">
                        <i class="fa-solid fa-plus w-4 text-center"></i> Nuevo registro para este animal
                    </a>
                    <a href="{{ route('produccion.por-animal', $registro->animal_id) }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-purple-100 hover:bg-purple-200 text-purple-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-chart-line w-4 text-center"></i> Historial del animal
                    </a>
                    <a href="{{ route('animales.show', $registro->animal) }}"
                        class="flex items-center gap-3 w-full px-4 py-3 bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-cow w-4 text-center"></i> Ver ficha del animal
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- Otros registros del animal --}}
    @if($historialAnimal->count() > 0)
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">Otros registros de {{ $registro->animal->nombre ?? $registro->animal->codigo }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase">
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Sesión</th>
                        <th class="px-4 py-2 text-right">Litros</th>
                        <th class="px-4 py-2 text-left">Calidad</th>
                        <th class="px-4 py-2 text-center">Ver</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($historialAnimal as $h)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-xs text-gray-600">{{ $h->codigo }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $h->fecha->format('d/m/Y') }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $h->sesion_formateada }}</td>
                        <td class="px-4 py-2 text-right font-bold text-purple-700">{{ number_format($h->litros, 1) }} L</td>
                        <td class="px-4 py-2 text-gray-700">{{ $h->calidad_formateada }}</td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('produccion.show', $h) }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Ver →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

</div>
@endsection