@extends('layouts.dashboard')

@section('title', 'Producción Lechera')

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- Encabezado --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center space-x-4">
                <div class="w-14 h-14 bg-gradient-to-br from-purple-600 to-red-700 rounded-2xl flex items-center justify-center shadow-lg">
                    <i class="fa-solid fa-droplet text-white text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-extrabold text-gray-900">Producción Lechera</h1>
                    <p class="text-gray-500 text-sm mt-1">Registro y seguimiento de producción por animal y finca</p>
                </div>
            </div>
            <a href="{{ route('produccion.create') }}"
               class="flex items-center space-x-2 px-5 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-lg">
                <i class="fa-solid fa-plus"></i>
                <span>Nuevo Registro</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="glass-effect border-4 border-green-400/50 rounded-2xl shadow-xl p-4 flex items-center space-x-3">
            <i class="fa-solid fa-circle-check text-green-600 text-xl"></i>
            <p class="text-green-700 font-semibold">{{ session('success') }}</p>
        </div>
    @endif

    {{-- Tarjetas de estadísticas --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">

        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Hoy</p>
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-sun text-blue-600"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-blue-700">{{ number_format($totalHoy, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">litros registrados hoy</p>
        </div>

        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Esta semana</p>
                <div class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-calendar-week text-green-600"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-green-700">{{ number_format($totalSemana, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">litros esta semana</p>
        </div>

        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Este mes</p>
                <div class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-droplet text-purple-600"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-purple-700">{{ number_format($totalMes, 1) }}</p>
            <p class="text-xs text-gray-500 mt-1">litros este mes</p>
        </div>

        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5">
            <div class="flex items-center justify-between mb-2">
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Registros</p>
                <div class="w-10 h-10 bg-orange-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-clipboard-list text-orange-600"></i>
                </div>
            </div>
            <p class="text-3xl font-extrabold text-orange-700">{{ $totalRegistros }}</p>
            <p class="text-xs text-gray-500 mt-1">total acumulado</p>
        </div>
    </div>

    {{-- Gráfico + Top animales --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Gráfico últimos 7 días --}}
        <div class="lg:col-span-2 glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-800 flex items-center gap-3">
                    <span class="w-9 h-9 bg-gradient-to-br from-purple-600 to-red-700 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-chart-bar text-white text-sm"></i>
                    </span>
                    Producción últimos 7 días
                </h2>
                <div class="flex gap-2">
                    <button onclick="cargarChart(7)" id="btn-7" class="px-3 py-1.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-xs font-bold rounded-lg shadow">7d</button>
                    <button onclick="cargarChart(30)" id="btn-30" class="px-3 py-1.5 bg-white border-2 border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50">30d</button>
                </div>
            </div>
            <div class="h-64">
                <canvas id="chartProduccion"></canvas>
            </div>
        </div>

        {{-- Top animales --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
            <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center gap-3">
                <span class="w-9 h-9 bg-gradient-to-br from-yellow-500 to-orange-600 rounded-xl flex items-center justify-center shadow">
                    <i class="fa-solid fa-trophy text-white text-sm"></i>
                </span>
                Top producción (mes)
            </h2>
            @if($topAnimales->isEmpty())
                <div class="text-center py-8 text-gray-400">
                    <i class="fa-solid fa-cow text-4xl mb-2 block"></i>
                    <p class="text-sm">Sin registros este mes</p>
                </div>
            @else
                <div class="space-y-3">
                    @foreach($topAnimales as $i => $tp)
                    <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                        <span class="w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0
                            {{ $i === 0 ? 'bg-yellow-400 text-white' : ($i === 1 ? 'bg-gray-300 text-gray-700' : ($i === 2 ? 'bg-orange-400 text-white' : 'bg-gray-100 text-gray-600')) }}">
                            {{ $i + 1 }}
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-bold text-gray-800 truncate">
                                {{ $tp->animal->nombre ?? $tp->animal->codigo ?? '—' }}
                            </p>
                            <p class="text-xs text-gray-500">{{ $tp->total_registros }} registros</p>
                        </div>
                        <span class="text-sm font-extrabold text-purple-700 flex-shrink-0">
                            {{ number_format($tp->total_litros, 1) }}L
                        </span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- Filtros --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-5">
        <form action="{{ route('produccion.index') }}" method="GET">
            <div class="grid grid-cols-2 lg:grid-cols-6 gap-3">
                <div class="lg:col-span-2">
                    <select name="finca_id"
                        class="w-full px-3 py-2.5 border-2 border-gray-300 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Todas las fincas</option>
                        @foreach($fincas as $f)
                            <option value="{{ $f->id }}" {{ request('finca_id') == $f->id ? 'selected' : '' }}>{{ $f->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="lg:col-span-2">
                    <select name="animal_id"
                        class="w-full px-3 py-2.5 border-2 border-gray-300 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Todos los animales</option>
                        @foreach($animales as $a)
                            <option value="{{ $a->id }}" {{ request('animal_id') == $a->id ? 'selected' : '' }}>
                                {{ $a->nombre ?? $a->codigo }} ({{ $a->finca->nombre ?? '' }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="sesion"
                        class="w-full px-3 py-2.5 border-2 border-gray-300 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Todas las sesiones</option>
                        @foreach($sesiones as $k => $v)
                            <option value="{{ $k }}" {{ request('sesion') == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <select name="calidad"
                        class="w-full px-3 py-2.5 border-2 border-gray-300 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500">
                        <option value="">Todas las calidades</option>
                        @foreach($calidades as $k => $v)
                            <option value="{{ $k }}" {{ request('calidad') == $k ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <input type="date" name="fecha_desde" value="{{ request('fecha_desde') }}"
                        class="w-full px-3 py-2.5 border-2 border-gray-300 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Desde">
                </div>
                <div>
                    <input type="date" name="fecha_hasta" value="{{ request('fecha_hasta') }}"
                        class="w-full px-3 py-2.5 border-2 border-gray-300 rounded-xl bg-white text-gray-700 text-sm focus:ring-2 focus:ring-red-500 focus:border-red-500"
                        placeholder="Hasta">
                </div>
                <div class="flex gap-2 lg:col-span-2">
                    <button type="submit"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-sm font-bold rounded-xl hover:from-red-700 hover:to-red-800 transition-all shadow-md">
                        <i class="fa-solid fa-filter"></i> Filtrar
                    </button>
                    @if(request()->hasAny(['finca_id','animal_id','sesion','calidad','fecha_desde','fecha_hasta']))
                    <a href="{{ route('produccion.index') }}"
                        class="px-3 py-2.5 bg-gray-200 text-gray-700 text-sm font-bold rounded-xl hover:bg-gray-300 transition-all flex items-center">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                    @endif
                </div>
            </div>
        </form>
    </div>

    {{-- Tabla de registros --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
        @if($registros->count() > 0)
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-gradient-to-r from-red-600 to-red-800 text-white text-xs uppercase tracking-wider">
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Animal</th>
                            <th class="px-4 py-3 text-left">Finca</th>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Sesión</th>
                            <th class="px-4 py-3 text-right">Litros</th>
                            <th class="px-4 py-3 text-left">Calidad</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        @foreach($registros as $r)
                        @php
                            $sesionColor = [
                                'manana' => 'bg-yellow-100 text-yellow-800',
                                'tarde'  => 'bg-orange-100 text-orange-800',
                                'noche'  => 'bg-indigo-100 text-indigo-800',
                            ];
                            $calidadColor = [
                                'normal'    => 'bg-gray-100 text-gray-700',
                                'buena'     => 'bg-blue-100 text-blue-800',
                                'excelente' => 'bg-green-100 text-green-800',
                                'rechazada' => 'bg-red-100 text-red-800',
                            ];
                        @endphp
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 py-3 font-mono text-xs text-gray-600 font-medium">{{ $r->codigo }}</td>
                            <td class="px-4 py-3">
                                <div class="font-semibold text-gray-800">{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</div>
                                <div class="text-xs text-gray-500">{{ ucfirst($r->animal->tipo ?? '') }}</div>
                            </td>
                            <td class="px-4 py-3 text-gray-700">{{ $r->finca->nombre ?? '—' }}</td>
                            <td class="px-4 py-3 text-gray-700">{{ $r->fecha->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">
                                @if($r->sesion)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $sesionColor[$r->sesion] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $r->sesion_formateada }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <span class="text-lg font-extrabold text-purple-700">{{ number_format($r->litros, 1) }}</span>
                                <span class="text-xs text-gray-500 ml-0.5">L</span>
                            </td>
                            <td class="px-4 py-3">
                                @if($r->calidad)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold {{ $calidadColor[$r->calidad] ?? 'bg-gray-100 text-gray-700' }}">
                                        {{ $r->calidad_formateada }}
                                    </span>
                                @else
                                    <span class="text-gray-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-1">
                                    <a href="{{ route('produccion.show', $r) }}"
                                        class="p-1.5 bg-blue-500 hover:bg-blue-600 text-white rounded-lg transition-colors shadow-sm" title="Ver">
                                        <i class="fa-solid fa-eye text-xs"></i>
                                    </a>
                                    <a href="{{ route('produccion.edit', $r) }}"
                                        class="p-1.5 bg-yellow-500 hover:bg-yellow-600 text-white rounded-lg transition-colors shadow-sm" title="Editar">
                                        <i class="fa-solid fa-pen text-xs"></i>
                                    </a>
                                    <form action="{{ route('produccion.destroy', $r) }}" method="POST"
                                        onsubmit="return confirm('¿Eliminar este registro de producción?')">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                            class="p-1.5 bg-red-500 hover:bg-red-600 text-white rounded-lg transition-colors shadow-sm" title="Eliminar">
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
            <div class="py-16 text-center bg-white">
                <div class="w-20 h-20 bg-gradient-to-br from-purple-100 to-purple-200 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                    <i class="fa-solid fa-droplet text-purple-400 text-3xl"></i>
                </div>
                <h3 class="text-xl font-bold text-gray-700 mb-2">Sin registros de producción</h3>
                <p class="text-gray-500 mb-6">
                    {{ request()->hasAny(['finca_id','animal_id','sesion','calidad','fecha_desde','fecha_hasta'])
                        ? 'No hay registros con los filtros aplicados.'
                        : 'Comienza registrando la producción de tus vacas.' }}
                </p>
                <a href="{{ route('produccion.create') }}"
                    class="inline-flex items-center gap-2 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 text-white font-bold rounded-xl shadow-lg hover:from-red-700 hover:to-red-800 transition-all">
                    <i class="fa-solid fa-plus"></i> Primer registro
                </a>
            </div>
        @endif
    </div>

</div>

<script>
var chartInstance = null;
var chartDiasIniciales = {!! json_encode($chartDias) !!};
var chartLitrosIniciales = {!! json_encode($chartLitros) !!};

document.addEventListener('DOMContentLoaded', function () {
    inicializarChart(chartDiasIniciales, chartLitrosIniciales);
});

function inicializarChart(labels, datos) {
    var ctx = document.getElementById('chartProduccion');
    if (!ctx) return;

    if (chartInstance) chartInstance.destroy();

    chartInstance = new Chart(ctx.getContext('2d'), {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Litros',
                data: datos,
                backgroundColor: function(context) {
                    var c = context.chart.ctx;
                    var g = c.createLinearGradient(0, 0, 0, 300);
                    g.addColorStop(0, '#9333ea');
                    g.addColorStop(1, '#dc2626');
                    return g;
                },
                borderRadius: 8,
                borderSkipped: false,
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return context.parsed.y.toFixed(1) + ' L';
                        }
                    }
                }
            },
            scales: {
                y: { beginAtZero: true, ticks: { callback: function(v) { return v + 'L'; } } }
            }
        }
    });
}

function cargarChart(dias) {
    document.getElementById('btn-7').className  = dias === 7
        ? 'px-3 py-1.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-xs font-bold rounded-lg shadow'
        : 'px-3 py-1.5 bg-white border-2 border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50';
    document.getElementById('btn-30').className = dias === 30
        ? 'px-3 py-1.5 bg-gradient-to-r from-red-600 to-red-700 text-white text-xs font-bold rounded-lg shadow'
        : 'px-3 py-1.5 bg-white border-2 border-gray-300 text-gray-700 text-xs font-bold rounded-lg hover:bg-gray-50';

    fetch('{{ route("api.produccion.chart") }}?dias=' + dias, {
        headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content }
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        inicializarChart(data.map(function(d) { return d.fecha; }), data.map(function(d) { return d.litros; }));
    });
}
</script>
@endsection