@extends('layouts.dashboard')

@section('title', $animal->nombre ?: $animal->codigo)

@section('content')
<div class="animate-slide-up">
    <!-- Header -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <a href="{{ route('animales.index') }}"
               class="w-12 h-12 bg-white/20 hover:bg-white/30 text-white rounded-xl flex items-center justify-center mr-4 transition-all backdrop-blur-sm border-2 border-white/30">
                <i class="fa-solid fa-arrow-left text-xl"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    {{ $animal->nombre ?: 'Animal ' . $animal->codigo }}
                    <span class="px-3 py-1 text-white text-sm font-bold rounded-full shadow-lg
                        @if($animal->tipo === 'vaca')     bg-pink-500
                        @elseif($animal->tipo === 'toro') bg-blue-500
                        @elseif($animal->tipo === 'ternero') bg-green-500
                        @else bg-purple-500
                        @endif">
                        {{ ucfirst($animal->tipo) }}
                    </span>
                    {{-- Badge estado --}}
                    <span class="px-3 py-1 text-sm font-bold rounded-full shadow-lg
                        @if($animal->estado === 'activo')   bg-green-500 text-white
                        @elseif($animal->estado === 'vendido') bg-blue-500 text-white
                        @elseif($animal->estado === 'muerto')  bg-gray-600 text-white
                        @else bg-yellow-500 text-white
                        @endif">
                        {{ ucfirst($animal->estado) }}
                    </span>
                </h1>
                <p class="text-red-100 mt-1">{{ $animal->codigo }}@if($animal->numero) &nbsp;·&nbsp; N° {{ $animal->numero }}@endif</p>
            </div>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('animales.edit', $animal) }}"
               class="px-6 py-3 bg-white text-red-700 hover:bg-red-50 rounded-xl transition-all font-bold shadow-xl">
                <i class="fa-solid fa-edit mr-2"></i>Editar
            </a>
            <form action="{{ route('animales.destroy', $animal) }}" method="POST" class="inline"
                  onsubmit="return confirm('¿Estás seguro de eliminar este animal? Esta acción no se puede deshacer.')">
                @csrf
                @method('DELETE')
                <button type="submit"
                        class="px-6 py-3 bg-red-600 hover:bg-red-700 text-white rounded-xl transition-all font-bold shadow-xl">
                    <i class="fa-solid fa-trash mr-2"></i>Eliminar
                </button>
            </form>
        </div>
    </div>

    <!-- ── Información General ── -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">

        <!-- Datos Principales (2/3) -->
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
                    <i class="fa-solid fa-barcode text-gray-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Código</p>
                    <p class="text-lg font-bold text-gray-800 font-mono">{{ $animal->codigo }}</p>
                </div>

                <!-- Número -->
                <div class="p-4 bg-gradient-to-br from-indigo-50 to-indigo-100 rounded-xl border-2 border-indigo-200">
                    <i class="fa-solid fa-hashtag text-indigo-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Número</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->numero ?: '—' }}</p>
                </div>

                <!-- Nombre -->
                <div class="p-4 bg-gradient-to-br from-blue-50 to-blue-100 rounded-xl border-2 border-blue-200">
                    <i class="fa-solid fa-tag text-blue-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Nombre</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->nombre ?: 'Sin nombre' }}</p>
                </div>

                <!-- Sexo -->
                <div class="p-4 bg-gradient-to-br from-purple-50 to-purple-100 rounded-xl border-2 border-purple-200">
                    <i class="fa-solid fa-{{ $animal->sexo === 'macho' ? 'mars' : 'venus' }} text-purple-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Sexo</p>
                    <p class="text-lg font-bold text-gray-800">{{ ucfirst($animal->sexo) }}</p>
                </div>

                <!-- Raza -->
                <div class="p-4 bg-gradient-to-br from-green-50 to-green-100 rounded-xl border-2 border-green-200">
                    <i class="fa-solid fa-dna text-green-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Raza</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->raza ?: 'No especificada' }}</p>
                </div>

                <!-- Edad -->
                <div class="p-4 bg-gradient-to-br from-orange-50 to-orange-100 rounded-xl border-2 border-orange-200">
                    <i class="fa-solid fa-calendar text-orange-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Edad</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->edad }}</p>
                </div>

                <!-- Peso -->
                <div class="p-4 bg-gradient-to-br from-yellow-50 to-yellow-100 rounded-xl border-2 border-yellow-200">
                    <i class="fa-solid fa-weight-scale text-yellow-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Peso Actual</p>
                    <p class="text-lg font-bold text-gray-800">
                        {{ $animal->peso_actual ? number_format($animal->peso_actual, 2) . ' kg' : 'N/A' }}
                    </p>
                </div>

                <!-- Color -->
                @if($animal->color)
                <div class="p-4 bg-gradient-to-br from-pink-50 to-pink-100 rounded-xl border-2 border-pink-200">
                    <i class="fa-solid fa-palette text-pink-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Color</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->color }}</p>
                </div>
                @endif

                <!-- Estado -->
                <div class="p-4 bg-gradient-to-br from-teal-50 to-teal-100 rounded-xl border-2 border-teal-200">
                    <i class="fa-solid fa-heartbeat text-teal-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Estado</p>
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-bold
                        @if($animal->estado === 'activo')   bg-green-100 text-green-800
                        @elseif($animal->estado === 'vendido') bg-blue-100 text-blue-800
                        @elseif($animal->estado === 'muerto')  bg-gray-200 text-gray-700
                        @else bg-yellow-100 text-yellow-800
                        @endif">
                        {{ ucfirst($animal->estado) }}
                    </span>
                </div>

                <!-- Finca -->
                <div class="p-4 bg-gradient-to-br from-red-50 to-red-100 rounded-xl border-2 border-red-200 col-span-2">
                    <i class="fa-solid fa-map-marker-alt text-red-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Finca</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->finca->nombre }}</p>
                    <p class="text-xs text-gray-500 mt-1">{{ $animal->finca->codigo }}</p>
                </div>

                <!-- Potrero -->
                @if($animal->potrero)
                <div class="p-4 bg-gradient-to-br from-lime-50 to-lime-100 rounded-xl border-2 border-lime-200">
                    <i class="fa-solid fa-map text-lime-600 text-2xl mb-2"></i>
                    <p class="text-xs text-gray-600 mb-1">Potrero</p>
                    <p class="text-lg font-bold text-gray-800">{{ $animal->potrero->nombre }}</p>
                </div>
                @endif
            </div>
        </div>

        <!-- Fechas y Registro (1/3) -->
        <div class="space-y-4">
            <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
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

                    <div class="flex items-start pt-3 border-t-2 border-gray-100">
                        <i class="fa-solid fa-clipboard-check text-green-600 mt-1 mr-3"></i>
                        <div>
                            <p class="text-xs text-gray-500">Registrado el</p>
                            <p class="font-medium text-gray-800">{{ $animal->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>

                    @if($animal->updated_at->ne($animal->created_at))
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

            <!-- Acciones rápidas verticales -->
            <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50">
                <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                    <span class="w-10 h-10 bg-gradient-to-br from-red-600 to-red-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                        <i class="fa-solid fa-bolt text-white"></i>
                    </span>
                    Acciones
                </h2>
                <div class="space-y-2">
                    <a href="{{ route('animales.edit', $animal) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-blue-50 hover:bg-blue-100 border-2 border-blue-200 rounded-xl transition-all font-bold text-blue-700">
                        <i class="fa-solid fa-edit w-5 text-center"></i> Editar datos
                    </a>
                    <a href="{{ route('salud.create', ['animal_id' => $animal->id]) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-green-50 hover:bg-green-100 border-2 border-green-200 rounded-xl transition-all font-bold text-green-700">
                        <i class="fa-solid fa-syringe w-5 text-center"></i> Nueva vacuna / tratamiento
                    </a>
                    <a href="{{ route('salud.por-animal', $animal) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-purple-50 hover:bg-purple-100 border-2 border-purple-200 rounded-xl transition-all font-bold text-purple-700">
                        <i class="fa-solid fa-heart-pulse w-5 text-center"></i> Ver historial sanitario
                    </a>
                    <a href="{{ route('fincas.show', $animal->finca) }}"
                       class="flex items-center gap-3 w-full px-4 py-3 bg-orange-50 hover:bg-orange-100 border-2 border-orange-200 rounded-xl transition-all font-bold text-orange-700">
                        <i class="fa-solid fa-map-marked-alt w-5 text-center"></i> Ver finca
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- ── Observaciones ── -->
    @if($animal->observaciones)
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

    <!-- ── Últimos registros sanitarios ── -->
    @if(isset($ultimosSanitarios) && $ultimosSanitarios->count() > 0)
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-2xl font-bold text-gray-800 flex items-center">
                <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                    <i class="fa-solid fa-syringe text-white"></i>
                </span>
                Últimos registros sanitarios
            </h2>
            <a href="{{ route('salud.por-animal', $animal) }}"
               class="text-sm font-bold text-red-600 hover:text-red-800 transition-colors">
                Ver todos <i class="fa-solid fa-arrow-right ml-1"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase">
                        <th class="px-4 py-2 text-left">Código</th>
                        <th class="px-4 py-2 text-left">Tipo</th>
                        <th class="px-4 py-2 text-left">Producto</th>
                        <th class="px-4 py-2 text-left">Fecha</th>
                        <th class="px-4 py-2 text-left">Próxima</th>
                        <th class="px-4 py-2 text-center">Ver</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($ultimosSanitarios as $s)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2 font-mono text-gray-700 text-xs">{{ $s->codigo }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ \App\Models\Salud::getTipos()[$s->tipo] ?? $s->tipo }}</td>
                        <td class="px-4 py-2 text-gray-800">{{ $s->nombre_producto }}</td>
                        <td class="px-4 py-2 text-gray-700">{{ $s->fecha_aplicacion?->format('d/m/Y') }}</td>
                        <td class="px-4 py-2">
                            @if($s->proxima_aplicacion)
                                <span class="{{ $s->alerta_proxima ? 'text-red-600 font-semibold' : 'text-gray-600' }}">
                                    {{ $s->proxima_aplicacion->format('d/m/Y') }}
                                </span>
                            @else
                                <span class="text-gray-400">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-2 text-center">
                            <a href="{{ route('salud.show', $s) }}"
                               class="text-blue-600 hover:text-blue-800 font-medium text-xs">Ver →</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @else
    <!-- Estado vacío sanidad -->
    <div class="glass-effect rounded-2xl shadow-xl p-6 border-4 border-white/50 mb-6">
        <h2 class="text-2xl font-bold text-gray-800 mb-4 flex items-center">
            <span class="w-10 h-10 bg-gradient-to-br from-green-600 to-green-800 rounded-xl flex items-center justify-center mr-3 shadow-lg">
                <i class="fa-solid fa-syringe text-white"></i>
            </span>
            Historial de Salud
        </h2>
        <div class="text-center py-8">
            <div class="w-16 h-16 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-3 shadow-lg">
                <i class="fa-solid fa-heart-pulse text-gray-400 text-2xl"></i>
            </div>
            <p class="text-gray-600 font-medium mb-1">Sin registros sanitarios</p>
            <p class="text-sm text-gray-500 mb-4">Registra vacunaciones y tratamientos de este animal</p>
            <a href="{{ route('salud.create', ['animal_id' => $animal->id]) }}"
               class="inline-flex items-center gap-2 px-5 py-2.5 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 text-white text-sm font-bold rounded-xl shadow-lg transition-all">
                <i class="fa-solid fa-plus"></i> Agregar primer registro
            </a>
        </div>
    </div>
    @endif

</div>
@endsection