@extends('layouts.dashboard')

@section('title', 'Detalle Salud — ' . $registro->codigo)

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- ── Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('salud.index') }}"
               class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white">{{ $registro->codigo }}</h1>
                <p class="text-red-100 mt-1">Detalle del registro de salud</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('salud.edit', $registro) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-white shadow-xl transition-all bg-yellow-500 hover:bg-yellow-600">
                <i class="fa-solid fa-pen"></i> Editar
            </a>
            <form action="{{ route('salud.destroy', $registro) }}" method="POST"
                  onsubmit="return confirm('¿Eliminar este registro de salud?')">
                @csrf @method('DELETE')
                <button type="submit"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-white shadow-xl transition-all bg-red-600 hover:bg-red-700">
                    <i class="fa-solid fa-trash"></i> Eliminar
                </button>
            </form>
        </div>
    </div>

    {{-- ── Alertas activas ── --}}
    @if($registro->en_carencia)
    <div class="flex items-center gap-3 p-4 rounded-xl bg-red-100 border-2 border-red-400 text-red-800 shadow-lg">
        <i class="fa-solid fa-triangle-exclamation text-2xl shrink-0"></i>
        <div>
            <p class="font-bold">Animal en periodo de carencia</p>
            <p class="text-sm">No comercializar leche ni carne hasta el
                <strong>{{ $registro->fin_carencia->format('d/m/Y') }}</strong>
                ({{ $registro->dias_carencia_restantes }} días restantes)
            </p>
        </div>
    </div>
    @endif

    @if($registro->alerta_proxima)
    <div class="flex items-center gap-3 p-4 rounded-xl bg-yellow-100 border-2 border-yellow-400 text-yellow-800 shadow-lg">
        <i class="fa-solid fa-bell text-2xl shrink-0"></i>
        <div>
            <p class="font-bold">Próxima dosis pronto</p>
            <p class="text-sm">Fecha: <strong>{{ $registro->proxima_aplicacion->format('d/m/Y') }}</strong>
                — {{ $registro->dias_para_proxima }}</p>
        </div>
    </div>
    @endif

    {{-- ── Contenido principal ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Columna izquierda: datos ──────────────────────────── --}}
        <div class="lg:col-span-2 space-y-5">

            {{-- Identificación --}}
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-blue-600 to-blue-800 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-id-card text-white text-sm"></i>
                    </span>
                    Identificación
                </h2>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 font-medium">Código</dt>
                        <dd class="font-mono font-bold text-gray-800">{{ $registro->codigo }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Tipo</dt>
                        <dd>
                            @php $tc=['vacunacion'=>'bg-blue-100 text-blue-800','desparasitacion'=>'bg-teal-100 text-teal-800','tratamiento'=>'bg-purple-100 text-purple-800','cirugia'=>'bg-red-100 text-red-800','revision'=>'bg-yellow-100 text-yellow-800','otro'=>'bg-gray-100 text-gray-800']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $tc[$registro->tipo] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Salud::getTipos()[$registro->tipo] ?? $registro->tipo }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Animal</dt>
                        <dd class="font-bold text-gray-800">
                            <a href="{{ route('animales.show', $registro->animal) }}" class="text-red-600 hover:underline">
                                {{ $registro->animal->nombre ?? $registro->animal->codigo ?? '—' }}
                            </a>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Finca</dt>
                        <dd class="text-gray-800">{{ $registro->finca->nombre ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Estado</dt>
                        <dd>
                            @php $ec=['completado'=>'bg-green-100 text-green-800','en_tratamiento'=>'bg-orange-100 text-orange-800','pendiente'=>'bg-yellow-100 text-yellow-800','cancelado'=>'bg-gray-100 text-gray-800']; @endphp
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ec[$registro->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Salud::getEstados()[$registro->estado] ?? $registro->estado }}
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Registrado</dt>
                        <dd class="text-gray-800">{{ $registro->created_at->format('d/m/Y H:i') }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Producto y aplicación --}}
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-syringe text-white text-sm"></i>
                    </span>
                    Producto y Aplicación
                </h2>
                <dl class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <dt class="text-gray-500 font-medium">Producto / Medicamento</dt>
                        <dd class="font-bold text-gray-800">{{ $registro->nombre_producto }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Enfermedad prevenida</dt>
                        <dd class="text-gray-800">{{ $registro->enfermedad_prevenida ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Dosis</dt>
                        <dd class="text-gray-800">
                            {{ $registro->dosis ? number_format($registro->dosis, 2) . ' ' . ($registro->unidad_dosis ?? '') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Vía de aplicación</dt>
                        <dd class="text-gray-800">
                            {{ $registro->via_aplicacion
                                ? (\App\Models\Salud::getViasAplicacion()[$registro->via_aplicacion] ?? $registro->via_aplicacion)
                                : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Lote</dt>
                        <dd class="font-mono text-gray-800">{{ $registro->lote_medicamento ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Laboratorio</dt>
                        <dd class="text-gray-800">{{ $registro->laboratorio ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Veterinario</dt>
                        <dd class="text-gray-800">{{ $registro->veterinario ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-gray-500 font-medium">Costo</dt>
                        <dd class="font-semibold text-gray-800">
                            {{ $registro->costo ? '$ ' . number_format($registro->costo, 2) : '—' }}
                        </dd>
                    </div>
                </dl>
            </div>

            {{-- Diagnóstico --}}
            @if($registro->diagnostico)
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-3 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-purple-600 to-purple-800 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-stethoscope text-white text-sm"></i>
                    </span>
                    Diagnóstico Veterinario
                </h2>
                <div class="p-4 bg-purple-50 rounded-xl border-2 border-purple-200">
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $registro->diagnostico }}</p>
                </div>
            </div>
            @endif

            {{-- Observaciones --}}
            @if($registro->observaciones)
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-3 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-orange-600 to-orange-800 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-clipboard text-white text-sm"></i>
                    </span>
                    Observaciones
                </h2>
                <div class="p-4 bg-gray-50 rounded-xl border-2 border-gray-200">
                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">{{ $registro->observaciones }}</p>
                </div>
            </div>
            @endif
        </div>

        {{-- Columna derecha: fechas + acciones ──────────────────── --}}
        <div class="space-y-5">

            {{-- Fechas --}}
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-calendar text-white text-sm"></i>
                    </span>
                    Fechas
                </h2>
                <div class="space-y-3 text-sm">
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Aplicación</span>
                        <span class="font-bold text-gray-800">
                            {{ $registro->fecha_aplicacion?->format('d/m/Y') ?? '—' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Próxima dosis</span>
                        <span class="font-bold {{ $registro->alerta_proxima ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $registro->proxima_aplicacion?->format('d/m/Y') ?? '—' }}
                        </span>
                    </div>
                    @if($registro->proxima_aplicacion)
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Estado dosis</span>
                        <span class="font-semibold {{ $registro->alerta_proxima ? 'text-red-600' : 'text-green-700' }}">
                            {{ $registro->dias_para_proxima }}
                        </span>
                    </div>
                    @endif
                    <div class="flex justify-between items-center py-2 border-b border-gray-100">
                        <span class="text-gray-500">Días carencia</span>
                        <span class="font-bold text-gray-800">{{ $registro->dias_carencia ?? 0 }} días</span>
                    </div>
                    @if($registro->fin_carencia)
                    <div class="flex justify-between items-center py-2">
                        <span class="text-gray-500">Fin de carencia</span>
                        <span class="font-bold {{ $registro->en_carencia ? 'text-red-600' : 'text-green-700' }}">
                            {{ $registro->fin_carencia->format('d/m/Y') }}
                        </span>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Acciones rápidas --}}
            <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-gray-600 to-gray-800 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-bolt text-white text-sm"></i>
                    </span>
                    Acciones rápidas
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('salud.create', ['animal_id' => $registro->animal_id]) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-gradient-to-r from-red-600 to-red-700
                              hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg text-sm">
                        <i class="fa-solid fa-plus w-4 text-center"></i> Nuevo registro para este animal
                    </a>
                    <a href="{{ route('salud.por-animal', $registro->animal_id) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-blue-100 hover:bg-blue-200
                              text-blue-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-clock-rotate-left w-4 text-center"></i> Ver historial del animal
                    </a>
                    <a href="{{ route('animales.show', $registro->animal) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-gray-100 hover:bg-gray-200
                              text-gray-700 font-bold rounded-xl transition-all text-sm">
                        <i class="fa-solid fa-cow w-4 text-center"></i> Ver ficha del animal
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Historial del animal ── --}}
    @if($historialAnimal->count() > 0)
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">
                📜 Otros registros de {{ $registro->animal->nombre ?? $registro->animal->codigo }}
            </h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase">
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">Tipo</th>
                        <th class="px-4 py-2 text-left">Producto</th>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Estado</th>
                        <th class="px-4 py-2 text-center">Ver</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($historialAnimal as $h)
                    @php $ec=['completado'=>'bg-green-100 text-green-800','en_tratamiento'=>'bg-orange-100 text-orange-800','pendiente'=>'bg-yellow-100 text-yellow-800','cancelado'=>'bg-gray-100 text-gray-800']; @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-gray-700 text-xs">{{ $h->codigo }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ \App\Models\Salud::getTipos()[$h->tipo] ?? $h->tipo }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $h->nombre_producto }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $h->fecha_aplicacion?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ec[$h->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Salud::getEstados()[$h->estado] ?? $h->estado }}
                            </span>
                        </td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('salud.show', $h) }}" class="text-blue-600 hover:text-blue-800 font-bold text-xs">Ver →</a>
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