@extends('layouts.dashboard')

@section('title', 'Salud Animal')

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- ── Header ── --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white flex items-center gap-4">
                <span class="w-12 h-12 bg-white rounded-xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-heart-pulse text-red-700 text-2xl"></i>
                </span>
                Salud Animal
            </h1>
            <p class="text-red-100 mt-1 ml-16">Control de vacunaciones, tratamientos y eventos sanitarios</p>
        </div>
        <a href="{{ route('salud.create') }}"
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

    {{-- ── Estadísticas ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
        @php
            $stats = [
                ['val' => $estadisticas['total'],          'label' => 'Total Registros',   'color' => 'text-gray-800'],
                ['val' => $estadisticas['vacunaciones'],   'label' => 'Vacunaciones',       'color' => 'text-blue-700'],
                ['val' => $estadisticas['tratamientos'],   'label' => 'Tratamientos',       'color' => 'text-purple-700'],
                ['val' => $estadisticas['proximas_7dias'], 'label' => 'Próximas (7 días)',  'color' => 'text-yellow-600'],
                ['val' => $estadisticas['en_carencia'],    'label' => 'En Carencia',        'color' => 'text-red-600'],
            ];
        @endphp
        @foreach($stats as $i => $s)
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl text-center
                    {{ $i >= 3 && $s['val'] > 0 ? 'ring-2 ring-yellow-400' : '' }}
                    {{ $i === 4 && $s['val'] > 0 ? 'ring-red-400' : '' }}">
            <p class="text-3xl font-bold {{ $s['color'] }}">{{ $s['val'] }}</p>
            <p class="text-gray-600 text-xs font-medium mt-1">{{ $s['label'] }}</p>
        </div>
        @endforeach
    </div>

    {{-- ── Filtros ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl">
        <form action="{{ route('salud.index') }}" method="GET">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <input type="text" name="buscar" value="{{ request('buscar') }}"
                    placeholder="Buscar código, producto, animal..."
                    class="sm:col-span-2 lg:col-span-1 w-full px-3 py-2 rounded-xl border-2 border-gray-300
                           bg-white text-gray-800 placeholder-gray-400 text-sm focus:outline-none focus:border-red-500">

                <select name="finca_id"
                    class="w-full px-3 py-2 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-red-500">
                    <option value="">Todas las fincas</option>
                    @foreach($fincas as $f)
                        <option value="{{ $f->id }}" {{ request('finca_id') == $f->id ? 'selected' : '' }}>{{ $f->nombre }}</option>
                    @endforeach
                </select>

                <select name="tipo"
                    class="w-full px-3 py-2 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-red-500">
                    <option value="">Todos los tipos</option>
                    @foreach($tipos as $k => $v)
                        <option value="{{ $k }}" {{ request('tipo') == $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>

                <select name="estado"
                    class="w-full px-3 py-2 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-red-500">
                    <option value="">Todos los estados</option>
                    @foreach($estados as $k => $v)
                        <option value="{{ $k }}" {{ request('estado') == $k ? 'selected' : '' }}>{{ $v }}</option>
                    @endforeach
                </select>

                <div class="flex gap-2">
                    <button type="submit"
                        class="flex-1 px-4 py-2 rounded-xl font-bold text-white text-sm transition-all
                               bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800">
                        <i class="fa-solid fa-filter mr-1"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['buscar','finca_id','tipo','estado']))
                    <a href="{{ route('salud.index') }}"
                       class="px-3 py-2 rounded-xl bg-gray-200 text-gray-700 hover:bg-gray-300 text-sm font-medium transition-all">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- ── Tabla ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-xl shadow-xl overflow-hidden">
        @if($registros->count() > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase tracking-wider">
                        <th class="px-4 py-3 text-left">Código</th>
                        <th class="px-4 py-3 text-left">Animal</th>
                        <th class="px-4 py-3 text-left">Tipo</th>
                        <th class="px-4 py-3 text-left">Producto</th>
                        <th class="px-4 py-3 text-left">Fecha</th>
                        <th class="px-4 py-3 text-left">Próxima dosis</th>
                        <th class="px-4 py-3 text-left">Estado</th>
                        <th class="px-4 py-3 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @foreach($registros as $r)
                    @php
                        $tc = ['vacunacion'=>'bg-blue-100 text-blue-800','desparasitacion'=>'bg-teal-100 text-teal-800',
                               'tratamiento'=>'bg-purple-100 text-purple-800','cirugia'=>'bg-red-100 text-red-800',
                               'revision'=>'bg-yellow-100 text-yellow-800','otro'=>'bg-gray-100 text-gray-800'];
                        $ec = ['completado'=>'bg-green-100 text-green-800','en_tratamiento'=>'bg-orange-100 text-orange-800',
                               'pendiente'=>'bg-yellow-100 text-yellow-800','cancelado'=>'bg-gray-100 text-gray-800'];
                    @endphp
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 font-mono font-medium text-gray-700 text-xs">{{ $r->codigo }}</td>
                        <td class="px-4 py-3">
                            <div class="font-semibold text-gray-800">{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</div>
                            <div class="text-gray-500 text-xs">{{ $r->finca->nombre ?? '—' }}</div>
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $tc[$r->tipo] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Salud::getTipos()[$r->tipo] ?? $r->tipo }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-gray-800">{{ $r->nombre_producto }}</td>
                        <td class="px-4 py-3 text-gray-700">{{ $r->fecha_aplicacion?->format('d/m/Y') }}</td>
                        <td class="px-4 py-3">
                            @if($r->proxima_aplicacion)
                                <div class="text-gray-700">{{ $r->proxima_aplicacion->format('d/m/Y') }}</div>
                                <div class="text-xs {{ $r->alerta_proxima ? 'text-red-600 font-semibold' : 'text-gray-500' }}">
                                    {{ $r->dias_para_proxima }}
                                </div>
                            @else
                                <span class="text-gray-400 text-xs">Sin próxima dosis</span>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $ec[$r->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ \App\Models\Salud::getEstados()[$r->estado] ?? $r->estado }}
                            </span>
                            @if($r->en_carencia)
                            <div class="mt-1">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-700">
                                    <i class="fa-solid fa-triangle-exclamation mr-0.5"></i> Carencia: {{ $r->dias_carencia_restantes }}d
                                </span>
                            </div>
                            @endif
                        </td>
                        <td class="px-4 py-3">
                            <div class="flex items-center justify-center gap-2">
                                <a href="{{ route('salud.show', $r) }}"
                                   class="p-1.5 rounded-lg bg-blue-500 hover:bg-blue-600 text-white transition-colors" title="Ver">
                                    <i class="fa-solid fa-eye text-xs"></i>
                                </a>
                                <a href="{{ route('salud.edit', $r) }}"
                                   class="p-1.5 rounded-lg bg-yellow-500 hover:bg-yellow-600 text-white transition-colors" title="Editar">
                                    <i class="fa-solid fa-pen text-xs"></i>
                                </a>
                                <form action="{{ route('salud.destroy', $r) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar este registro de salud?')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="p-1.5 rounded-lg bg-red-500 hover:bg-red-600 text-white transition-colors" title="Eliminar">
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
        <div class="py-16 text-center">
            <div class="w-20 h-20 bg-gradient-to-br from-gray-100 to-gray-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                <i class="fa-solid fa-heart-pulse text-gray-400 text-3xl"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-700 mb-2">Sin registros de salud</h3>
            <p class="text-gray-500 mb-6">Comienza registrando vacunaciones y tratamientos de tu ganado.</p>
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