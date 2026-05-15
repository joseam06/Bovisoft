@extends('layouts.dashboard')

@section('title', 'Historial de Salud — ' . ($animal->nombre ?? $animal->codigo))

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- ── Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-4">
            <a href="{{ route('animales.show', $animal) }}"
               class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center
                      transition-all backdrop-blur-sm border-2 border-white/30 shadow-lg">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg shrink-0">
                        <i class="fa-solid fa-heart-pulse text-red-700 text-2xl"></i>
                    </span>
                    Historial de Salud
                </h1>
                <p class="text-red-100 mt-1 ml-16">
                    {{ $animal->nombre ?? $animal->codigo }}
                    @if($animal->numero) · N° {{ $animal->numero }} @endif
                    · {{ $animal->finca->nombre ?? '' }}
                </p>
            </div>
        </div>
        <a href="{{ route('salud.create', ['animal_id' => $animal->id]) }}"
           class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white shadow-xl transition-all
                  bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 hover:-translate-y-0.5">
            <i class="fa-solid fa-plus"></i> Nuevo Registro
        </a>
    </div>

    {{-- ── Alert ── --}}
    @if(session('success'))
    <div id="alert-ok"
         class="flex items-center gap-3 p-4 rounded-xl glass-effect border-4 border-white/50 text-green-700 shadow-xl">
        <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ── Tarjeta resumen del animal ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-4 text-sm">

            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-2 rounded-xl flex items-center justify-center shadow-md
                            @if($animal->tipo === 'vaca') bg-gradient-to-br from-pink-500 to-pink-700
                            @elseif($animal->tipo === 'toro') bg-gradient-to-br from-blue-500 to-blue-700
                            @elseif($animal->tipo === 'ternero') bg-gradient-to-br from-green-500 to-green-700
                            @else bg-gradient-to-br from-purple-500 to-purple-700
                            @endif">
                    <i class="fa-solid fa-cow text-white text-2xl"></i>
                </div>
                <p class="text-gray-500 text-xs">Tipo</p>
                <p class="font-bold text-gray-800">{{ ucfirst($animal->tipo) }}</p>
            </div>

            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-2 rounded-xl bg-gradient-to-br from-gray-500 to-gray-700 flex items-center justify-center shadow-md">
                    <i class="fa-solid fa-barcode text-white text-xl"></i>
                </div>
                <p class="text-gray-500 text-xs">Código</p>
                <p class="font-bold text-gray-800 font-mono text-xs">{{ $animal->codigo }}</p>
            </div>

            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-2 rounded-xl flex items-center justify-center shadow-md
                            {{ $animal->sexo === 'macho' ? 'bg-gradient-to-br from-blue-500 to-blue-700' : 'bg-gradient-to-br from-pink-500 to-pink-700' }}">
                    <i class="fa-solid fa-{{ $animal->sexo === 'macho' ? 'mars' : 'venus' }} text-white text-xl"></i>
                </div>
                <p class="text-gray-500 text-xs">Sexo</p>
                <p class="font-bold text-gray-800">{{ ucfirst($animal->sexo) }}</p>
            </div>

            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-2 rounded-xl bg-gradient-to-br from-green-500 to-green-700 flex items-center justify-center shadow-md">
                    <i class="fa-solid fa-dna text-white text-xl"></i>
                </div>
                <p class="text-gray-500 text-xs">Raza</p>
                <p class="font-bold text-gray-800">{{ $animal->raza ?? 'N/A' }}</p>
            </div>

            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-2 rounded-xl bg-gradient-to-br from-orange-500 to-orange-700 flex items-center justify-center shadow-md">
                    <i class="fa-solid fa-calendar text-white text-xl"></i>
                </div>
                <p class="text-gray-500 text-xs">Edad</p>
                <p class="font-bold text-gray-800 text-xs">{{ $animal->edad ?? 'N/A' }}</p>
            </div>

            <div class="text-center">
                <div class="w-14 h-14 mx-auto mb-2 rounded-xl flex items-center justify-center shadow-md
                            @if($animal->estado === 'activo') bg-gradient-to-br from-green-500 to-green-700
                            @elseif($animal->estado === 'vendido') bg-gradient-to-br from-blue-500 to-blue-700
                            @else bg-gradient-to-br from-gray-500 to-gray-700
                            @endif">
                    <i class="fa-solid fa-circle-check text-white text-xl"></i>
                </div>
                <p class="text-gray-500 text-xs">Estado</p>
                <p class="font-bold text-gray-800">{{ ucfirst($animal->estado) }}</p>
            </div>
        </div>
    </div>

    {{-- ── Estadísticas del historial ── --}}
    @php
        $totalRegistros   = $registros->total();
        $vacunaciones     = $registros->getCollection()->where('tipo', 'vacunacion')->count();
        $enCarencia       = $registros->getCollection()->filter(fn($r) => $r->en_carencia)->count();
        $proximasVencer   = $registros->getCollection()->filter(fn($r) => $r->alerta_proxima && $r->proxima_aplicacion)->count();
    @endphp
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl text-center">
            <i class="fa-solid fa-clipboard-list text-gray-600 text-2xl mb-1"></i>
            <p class="text-3xl font-bold text-gray-800">{{ $totalRegistros }}</p>
            <p class="text-gray-600 text-xs font-medium mt-1">Total registros</p>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl text-center">
            <i class="fa-solid fa-syringe text-blue-600 text-2xl mb-1"></i>
            <p class="text-3xl font-bold text-blue-700">{{ $vacunaciones }}</p>
            <p class="text-gray-600 text-xs font-medium mt-1">Vacunaciones (página)</p>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl text-center
                    {{ $proximasVencer > 0 ? 'ring-2 ring-yellow-400' : '' }}">
            <i class="fa-solid fa-calendar-check text-yellow-600 text-2xl mb-1"></i>
            <p class="text-3xl font-bold text-yellow-600">{{ $proximasVencer }}</p>
            <p class="text-gray-600 text-xs font-medium mt-1">Próximas (7 días)</p>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl text-center
                    {{ $enCarencia > 0 ? 'ring-2 ring-red-400' : '' }}">
            <i class="fa-solid fa-triangle-exclamation text-red-600 text-2xl mb-1"></i>
            <p class="text-3xl font-bold text-red-600">{{ $enCarencia }}</p>
            <p class="text-gray-600 text-xs font-medium mt-1">En carencia</p>
        </div>
    </div>

    {{-- ── Tabla de registros ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">

        {{-- Cabecera de la tabla --}}
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-lg font-bold text-gray-800 flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left text-red-600"></i>
                Historial completo
            </h2>
            <span class="text-sm text-gray-500">{{ $registros->total() }} registro(s)</span>
        </div>

        @if($registros->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left">Categoría / Tipo</th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-left">Veterinario</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Próxima dosis</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($registros as $r)
                    @php
                        $categoriaColor = [
                            'preventivo'   => 'bg-blue-100 text-blue-800',
                            'clinico'      => 'bg-purple-100 text-purple-800',
                            'reproductivo' => 'bg-pink-100 text-pink-800',
                            'seguimiento'  => 'bg-yellow-100 text-yellow-800',
                        ][$r->categoria ?? ''] ?? 'bg-gray-100 text-gray-800';

                        $tipoColor = [
                            'vacunacion'      => 'bg-blue-50 text-blue-700',
                            'vitaminizacion'  => 'bg-cyan-50 text-cyan-700',
                            'desparasitacion' => 'bg-teal-50 text-teal-700',
                            'bioseguridad'    => 'bg-indigo-50 text-indigo-700',
                            'enfermedad'      => 'bg-red-50 text-red-700',
                            'tratamiento'     => 'bg-purple-50 text-purple-700',
                            'diagnostico'     => 'bg-violet-50 text-violet-700',
                            'cirugia'         => 'bg-rose-50 text-rose-700',
                            'revision'        => 'bg-yellow-50 text-yellow-700',
                            'sincronizacion'  => 'bg-pink-50 text-pink-700',
                            'hormonas'        => 'bg-fuchsia-50 text-fuchsia-700',
                            'protocolo'       => 'bg-orange-50 text-orange-700',
                            'preparacion_iatf'=> 'bg-amber-50 text-amber-700',
                            'alerta'          => 'bg-yellow-50 text-yellow-700',
                            'control'         => 'bg-lime-50 text-lime-700',
                            'carencia'        => 'bg-red-50 text-red-700',
                            'otro'            => 'bg-gray-50 text-gray-700',
                        ][$r->tipo] ?? 'bg-gray-50 text-gray-700';

                        $estadoColor = [
                            'completado'     => 'bg-green-100 text-green-800',
                            'en_tratamiento' => 'bg-orange-100 text-orange-800',
                            'pendiente'      => 'bg-yellow-100 text-yellow-800',
                            'cancelado'      => 'bg-gray-100 text-gray-800',
                        ][$r->estado] ?? 'bg-gray-100 text-gray-800';
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors
                               {{ $r->en_carencia ? 'border-l-4 border-red-400' : '' }}
                               {{ $r->alerta_proxima && !$r->en_carencia ? 'border-l-4 border-yellow-400' : '' }}">

                        <td class="px-4 py-3 font-mono font-medium text-gray-700 text-xs whitespace-nowrap">
                            {{ $r->codigo }}
                        </td>

                        <td class="px-4 py-3">
                            @if($r->categoria)
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $categoriaColor }} block mb-1">
                                {{ ucfirst($r->categoria) }}
                            </span>
                            @endif
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $tipoColor }}">
                                {{ \App\Models\Salud::getTipos()[$r->tipo] ?? $r->tipo }}
                            </span>
                        </td>

                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $r->nombre_producto }}</div>
                            @if($r->enfermedad_prevenida)
                            <div class="text-gray-500 text-xs">{{ $r->enfermedad_prevenida }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-gray-700">
                            {{ $r->veterinario ?? '—' }}
                        </td>

                        <td class="px-4 py-3 text-gray-700 whitespace-nowrap">
                            {{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}
                        </td>

                        <td class="px-4 py-3">
                            @if($r->proxima_aplicacion)
                                <div class="text-gray-700 whitespace-nowrap">
                                    {{ $r->proxima_aplicacion->format('d/m/Y') }}
                                </div>
                                <div class="text-xs {{ $r->alerta_proxima ? 'text-red-600 font-bold' : 'text-gray-500' }}">
                                    {{ $r->dias_para_proxima }}
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">Sin programar</span>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $estadoColor }}">
                                {{ \App\Models\Salud::getEstados()[$r->estado] ?? $r->estado }}
                            </span>
                            @if($r->en_carencia)
                            <div class="mt-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700 whitespace-nowrap">
                                    <i class="fa-solid fa-triangle-exclamation mr-0.5"></i>
                                    Carencia: {{ $r->dias_carencia_restantes }}d
                                </span>
                            </div>
                            @endif
                        </td>

                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('salud.show', $r) }}"
                                   class="p-1.5 rounded-lg bg-blue-500 hover:bg-blue-600 text-white transition-colors"
                                   title="Ver detalle">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('salud.edit', $r) }}"
                                   class="p-1.5 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white transition-colors"
                                   title="Editar">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>
                                <form action="{{ route('salud.destroy', $r) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar este registro de salud?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors"
                                        title="Eliminar">
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

        {{-- Paginación --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $registros->links() }}
        </div>

        @else
        {{-- Estado vacío --}}
        <div class="py-16 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full
                        flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fa-solid fa-heart-pulse text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">Sin registros de salud</h3>
            <p class="text-gray-500 mb-6">
                {{ $animal->nombre ?? $animal->codigo }} no tiene eventos sanitarios registrados.
            </p>
            <a href="{{ route('salud.create', ['animal_id' => $animal->id]) }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white shadow-lg transition-all
                      bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 hover:-translate-y-0.5">
                <i class="fa-solid fa-plus"></i> Registrar primer evento
            </a>
        </div>
        @endif
    </div>

    {{-- ── Acciones del animal ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-bolt text-red-600"></i>
            Acciones rápidas
        </h3>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('salud.create', ['animal_id' => $animal->id]) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-white shadow-lg transition-all
                      bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 hover:-translate-y-0.5">
                <i class="fa-solid fa-plus"></i> Nuevo registro de salud
            </a>
            <a href="{{ route('animales.show', $animal) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-gray-700
                      bg-white border-2 border-gray-300 hover:bg-gray-50 shadow-md transition-all">
                <i class="fa-solid fa-cow"></i> Ver ficha del animal
            </a>
            <a href="{{ route('animales.edit', $animal) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-gray-700
                      bg-white border-2 border-gray-300 hover:bg-gray-50 shadow-md transition-all">
                <i class="fa-solid fa-pen"></i> Editar animal
            </a>
            <a href="{{ route('salud.index') }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl font-bold text-gray-700
                      bg-white border-2 border-gray-300 hover:bg-gray-50 shadow-md transition-all">
                <i class="fa-solid fa-heart-pulse"></i> Panel de Salud
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
    setTimeout(() => {
        const a = document.getElementById('alert-ok');
        if (a) {
            a.style.opacity = '0';
            a.style.transition = 'opacity 0.5s';
            setTimeout(() => a.remove(), 500);
        }
    }, 5000);
</script>
@endpush
@endsection