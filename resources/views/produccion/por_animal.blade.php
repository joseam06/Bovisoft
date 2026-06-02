@extends('layouts.dashboard')

@section('title', 'Producción — ' . ($animal->nombre ?? $animal->codigo))

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('animales.show', $animal) }}"
                class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg flex-shrink-0">
                        <i class="fa-solid fa-droplet text-purple-700 text-2xl"></i>
                    </span>
                    Producción de {{ $animal->nombre ?? $animal->codigo }}
                </h1>
                <p class="text-red-100 mt-1 ml-16">{{ $animal->finca->nombre ?? '' }} · {{ ucfirst($animal->tipo) }}</p>
            </div>
        </div>
        <a href="{{ route('produccion.create', ['animal_id' => $animal->id]) }}"
            class="inline-flex items-center gap-2 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:from-red-700 hover:to-red-800 transition-all">
            <i class="fa-solid fa-plus"></i> Nuevo registro
        </a>
    </div>

    {{-- Stats del animal --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total acumulado</p>
            <p class="text-4xl font-extrabold text-purple-700">{{ number_format($totalAnimal, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">litros en total</p>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Promedio por registro</p>
            <p class="text-4xl font-extrabold text-blue-700">{{ number_format($promedioAnimal, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">litros promedio</p>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5 text-center">
            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Registros</p>
            <p class="text-4xl font-extrabold text-gray-700">{{ $totalRegistros }}</p>
            <p class="text-xs text-gray-500 mt-1">total histórico</p>
        </div>
    </div>

    {{-- Tabla --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-purple-600"></i> Historial completo
            </h2>
            <span class="text-sm text-gray-500">{{ $registros->total() }} registro(s)</span>
        </div>
        @if($registros->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase">
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Sesión</th>
                            <th class="px-4 py-3 text-right">Litros</th>
                            <th class="px-4 py-3 text-left">Calidad</th>
                            <th class="px-4 py-3 text-left">Observaciones</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($registros as $r)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 font-medium">{{ $r->codigo }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-700">{{ $r->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $r->sesion_formateada }}</td>
                            <td class="px-4 py-3 text-right">
                                <span class="font-extrabold text-purple-700">{{ number_format($r->litros, 1) }}</span>
                                <span class="text-xs text-gray-500 ml-0.5">L</span>
                            </td>
                            <td class="px-4 py-3 text-gray-600 text-xs">{{ $r->calidad_formateada }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs max-w-xs truncate">{{ $r->observaciones ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('produccion.show', $r) }}"
                                        class="p-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('produccion.edit', $r) }}"
                                        class="p-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    <form action="{{ route('produccion.destroy', $r) }}" method="POST"
                                        onsubmit="return confirm('¿Eliminar este registro?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="p-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors">
                                            <i class="fa-solid fa-trash text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-4 py-3 bg-gray-50 border-t border-gray-200">
                {{ $registros->links() }}
            </div>
        @else
            <div class="py-14 text-center bg-white">
                <div class="w-16 h-16 bg-purple-100 rounded-full flex items-center justify-center mx-auto mb-3 shadow">
                    <i class="fa-solid fa-droplet text-purple-400 text-2xl"></i>
                </div>
                <p class="text-gray-600 font-medium mb-4">Sin registros de producción para este animal</p>
                <a href="{{ route('produccion.create', ['animal_id' => $animal->id]) }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:from-red-700 hover:to-red-800 transition-all text-sm">
                    <i class="fa-solid fa-plus"></i> Agregar primer registro
                </a>
            </div>
        @endif
    </div>

</div>
@endsection