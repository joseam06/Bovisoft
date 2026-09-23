@extends('layouts.dashboard')

@section('title', 'Salud - ' . $titulo)

@php
    // Configuración visual según categoría
    $config = [
        'preventivo' => [
            'gradienteHeader' => 'from-blue-600 to-blue-800',
            'colorTexto'      => 'text-blue-100',
            'icono'           => 'fa-shield-virus',
            'colorBadge'      => 'bg-blue-100 text-blue-800',
        ],
        'clinico' => [
            'gradienteHeader' => 'from-purple-600 to-purple-800',
            'colorTexto'      => 'text-purple-100',
            'icono'           => 'fa-stethoscope',
            'colorBadge'      => 'bg-purple-100 text-purple-800',
        ],
        'reproductivo' => [
            'gradienteHeader' => 'from-pink-600 to-pink-800',
            'colorTexto'      => 'text-pink-100',
            'icono'           => 'fa-venus-mars',
            'colorBadge'      => 'bg-pink-100 text-pink-800',
        ],
        'seguimiento' => [
            'gradienteHeader' => 'from-yellow-500 to-yellow-700',
            'colorTexto'      => 'text-yellow-100',
            'icono'           => 'fa-calendar-check',
            'colorBadge'      => 'bg-yellow-100 text-yellow-800',
        ],
    ][$categoria] ?? [
        'gradienteHeader' => 'from-red-600 to-red-800',
        'colorTexto'      => 'text-red-100',
        'icono'           => 'fa-heart-pulse',
        'colorBadge'      => 'bg-red-100 text-red-800',
    ];

    // Pequeñas estadísticas basadas en la consulta (solo de la página actual, puedes mejorarlo pasando datos desde el controlador)
    $totalRegistros = $registros->total();
    $pendientes     = $registros->where('estado', 'pendiente')->count();
    $proximas       = $registros->filter(fn($r) => $r->alerta_proxima)->count();
    $enCarencia     = $registros->filter(fn($r) => $r->en_carencia)->count();
@endphp

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- ── Botón de regreso ── --}}
    <div class="flex justify-end">
    <a href="{{ route('salud.index') }}" 
       class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white shadow-xl transition-all
              bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 hover:-translate-y-0.5">
        <i class="fa-solid fa-arrow-left"></i> Regresar al panel de salud
    </a>
</div>

    {{-- ── Header con gradiente de categoría ── --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r {{ $config['gradienteHeader'] }} p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-6 -mr-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
            <div>
                <nav class="flex items-center space-x-2 text-sm {{ $config['colorTexto'] }} mb-3">
                    <a href="{{ route('salud.index') }}" class="hover:text-white transition-colors font-medium">
                        <i class="fa-solid fa-heart-pulse mr-1"></i> Salud
                    </a>
                    <span class="opacity-50">/</span>
                    <span class="text-white font-semibold">{{ $titulo }}</span>
                </nav>
                <div class="flex items-center gap-4">
                    <span class="w-14 h-14 bg-white/20 backdrop-blur-sm rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fa-solid {{ $config['icono'] }} text-white text-2xl"></i>
                    </span>
                    <div>
                        <h1 class="text-3xl font-bold text-white">{{ $titulo }}</h1>
                        <p class="{{ $config['colorTexto'] }} mt-1">{{ $descripcion }}</p>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-4 py-2 bg-white/20 backdrop-blur-sm rounded-xl text-white font-bold text-lg shadow">
                    <i class="fa-solid fa-database mr-2"></i>{{ $totalRegistros }} registro(s)
                </span>
                <a href="{{ route('salud.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-white shadow-xl transition-all
                          bg-white/20 hover:bg-white/30 backdrop-blur-sm border-2 border-white/40 hover:-translate-y-0.5">
                    <i class="fa-solid fa-plus"></i> Nuevo Registro
                </a>
            </div>
        </div>
    </div>

    {{-- ── Alert ── --}}
    @if(session('success'))
    <div id="alert-ok"
         class="flex items-center gap-3 p-4 rounded-xl glass-effect border-4 border-white/50 text-green-700 shadow-xl">
        <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ── Mini tarjetas de resumen ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-lg flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Pendientes</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $pendientes }}</p>
            </div>
            <span class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-clock text-yellow-600"></i>
            </span>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-lg flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Próximas (7d)</p>
                <p class="text-2xl font-bold text-blue-600">{{ $proximas }}</p>
            </div>
            <span class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-calendar-day text-blue-600"></i>
            </span>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-lg flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">En carencia</p>
                <p class="text-2xl font-bold text-red-600">{{ $enCarencia }}</p>
            </div>
            <span class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-triangle-exclamation text-red-600"></i>
            </span>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-lg flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total</p>
                <p class="text-2xl font-bold text-gray-700">{{ $totalRegistros }}</p>
            </div>
            <span class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-list-ol text-gray-600"></i>
            </span>
        </div>
    </div>

    {{-- ── Filtros mejorados ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl">
        <form action="{{ url()->current() }}" method="GET">
            <div class="flex flex-col lg:flex-row gap-3">
                <div class="relative flex-grow">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fa-solid fa-magnifying-glass text-gray-400"></i>
                    </span>
                    <input type="text" name="buscar" value="{{ request('buscar') }}"
                           placeholder="Buscar código, producto, animal..."
                           class="w-full pl-10 pr-4 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-gray-800 placeholder-gray-400 text-sm focus:outline-none focus:border-red-500">
                </div>
                <div class="grid grid-cols-3 lg:grid-cols-3 gap-3 flex-shrink-0">
                    <select name="finca_id"
                            class="w-full px-3 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-red-500">
                        <option value="">Todas las fincas</option>
                        @foreach($fincas as $f)
                            <option value="{{ $f->id }}" {{ request('finca_id') == $f->id ? 'selected' : '' }}>{{ $f->nombre }}</option>
                        @endforeach
                    </select>
                    <select name="tipo"
                            class="w-full px-3 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-red-500">
                        <option value="">Todos los tipos</option>
                        @foreach($tipos as $k => $v)
                            <option value="{{ $k }}" {{ request('tipo') == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                    <select name="estado"
                            class="w-full px-3 py-2.5 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-red-500">
                        <option value="">Todos los estados</option>
                        @foreach($estados as $k => $v)
                            <option value="{{ $k }}" {{ request('estado') == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit"
                            class="px-5 py-2.5 rounded-xl font-bold text-white text-sm transition-all
                                   bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 shadow-lg">
                        <i class="fa-solid fa-filter mr-1"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['buscar','finca_id','tipo','estado']))
                    <a href="{{ url()->current() }}"
                       class="px-4 py-2.5 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm font-medium transition-all flex items-center">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- ── Tabla de registros ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-xl shadow-xl overflow-hidden">
        @if($registros->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-barcode mr-1"></i>Código
                        </th>
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-cow mr-1"></i>Animal
                        </th>
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-tag mr-1"></i>Tipo
                        </th>
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-capsules mr-1"></i>Producto
                        </th>
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-calendar mr-1"></i>Fecha
                        </th>
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-calendar-plus mr-1"></i>Próxima dosis
                        </th>
                        <th class="px-4 py-3 text-left">
                            <i class="fa-solid fa-circle-info mr-1"></i>Estado
                        </th>
                        <th class="px-4 py-3 text-center">
                            <i class="fa-solid fa-gears mr-1"></i>Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($registros as $r)
                    @php
                        // Colores de tipo ampliados
                        $tc = [
                            'vacunacion'      => 'bg-blue-100 text-blue-800',
                            'vitaminizacion'  => 'bg-cyan-100 text-cyan-800',
                            'desparasitacion' => 'bg-teal-100 text-teal-800',
                            'bioseguridad'    => 'bg-green-100 text-green-800',
                            'enfermedad'      => 'bg-red-100 text-red-800',
                            'tratamiento'     => 'bg-purple-100 text-purple-800',
                            'diagnostico'     => 'bg-orange-100 text-orange-800',
                            'cirugia'         => 'bg-pink-100 text-pink-800',
                            'revision'        => 'bg-yellow-100 text-yellow-800',
                            'sincronizacion'  => 'bg-indigo-100 text-indigo-800',
                            'hormonas'        => 'bg-fuchsia-100 text-fuchsia-800',
                            'protocolo'       => 'bg-rose-100 text-rose-800',
                            'preparacion_iatf'=> 'bg-violet-100 text-violet-800',
                            'alerta'          => 'bg-amber-100 text-amber-800',
                            'control'         => 'bg-lime-100 text-lime-800',
                            'carencia'        => 'bg-red-100 text-red-800',
                            'otro'            => 'bg-gray-100 text-gray-800',
                        ];
                        $ec = [
                            'completado'     => 'bg-green-100 text-green-800',
                            'en_tratamiento' => 'bg-orange-100 text-orange-800',
                            'pendiente'      => 'bg-yellow-100 text-yellow-800',
                            'cancelado'      => 'bg-gray-100 text-gray-800',
                        ];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono font-medium text-gray-700 text-xs">{{ $r->codigo }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</div>
                            <div class="text-gray-500 text-xs">{{ $r->finca->nombre ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $tc[$r->tipo] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Salud::getTipos()[$r->tipo] ?? $r->tipo }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-800">{{ $r->nombre_producto }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if($r->proxima_aplicacion)
                                <div class="text-gray-700 font-medium">{{ $r->proxima_aplicacion->format('d/m/Y') }}</div>
                                <div class="text-xs {{ $r->alerta_proxima ? 'text-red-600 font-semibold animate-pulse' : 'text-gray-500' }}">
                                    <i class="fa-solid {{ $r->alerta_proxima ? 'fa-bell' : 'fa-clock' }} mr-1"></i>
                                    {{ $r->dias_para_proxima }}
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">Sin próxima dosis</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex flex-col gap-1">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold {{ $ec[$r->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                    <i class="fa-solid {{ $r->estado === 'completado' ? 'fa-circle-check' : ($r->estado === 'en_tratamiento' ? 'fa-syringe' : ($r->estado === 'pendiente' ? 'fa-hourglass-half' : 'fa-circle-xmark')) }} text-xs"></i>
                                    {{ \App\Models\Salud::getEstados()[$r->estado] ?? $r->estado }}
                                </span>
                                @if($r->en_carencia)
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Carencia: {{ $r->dias_carencia_restantes }}d
                                </span>
                                @endif
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-1">
                                <a href="{{ route('salud.show', $r) }}"
                                   class="p-1.5 rounded-lg bg-blue-500 hover:bg-blue-600 text-white transition-colors shadow-sm"
                                   title="Ver detalle">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('salud.edit', $r) }}"
                                   class="p-1.5 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white transition-colors shadow-sm"
                                   title="Editar registro">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>
                                <form action="{{ route('salud.destroy', $r) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar este registro de salud?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="p-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors shadow-sm"
                                            title="Eliminar registro">
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
            {{ $registros->appends(request()->query())->links() }}
        </div>
        @else
        <div class="py-16 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fa-solid {{ $config['icono'] }} text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">Sin registros en {{ $titulo }}</h3>
            <p class="text-gray-500 mb-6 max-w-md mx-auto">No hay eventos sanitarios registrados en esta categoría. Crea el primero para mantener un mejor control de tu ganado.</p>
            <a href="{{ route('salud.create') }}"
               class="inline-flex items-center gap-2 px-6 py-3 rounded-xl font-bold text-white shadow-lg transition-all
                      bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800">
                <i class="fa-solid fa-plus"></i> Nuevo Registro
            </a>
        </div>
        @endif
    </div>

</div>

@push('scripts')
<script>
    setTimeout(() => { const a = document.getElementById('alert-ok'); if(a) a.style.display='none'; }, 5000);
</script>
@endpush
@endsection