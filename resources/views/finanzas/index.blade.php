@extends('layouts.dashboard')

@section('title', 'Finanzas')

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- ── Header ── --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-emerald-600 to-emerald-800 p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-6 -mr-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-money-bill-trend-up text-white text-2xl"></i>
                    </span>
                    Finanzas
                </h1>
                <p class="text-emerald-100 mt-1 ml-15">Control de ingresos, egresos y flujo de caja</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('finanzas.ingresos.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-emerald-800 bg-white hover:bg-emerald-50 shadow-xl transition-all hover:-translate-y-0.5">
                    <i class="fa-solid fa-circle-plus"></i> Nuevo Ingreso
                </a>
                <a href="{{ route('finanzas.egresos.create') }}"
                   class="inline-flex items-center gap-2 px-5 py-3 rounded-xl font-bold text-white bg-red-500 hover:bg-red-600 border-2 border-white/40 shadow-xl transition-all hover:-translate-y-0.5">
                    <i class="fa-solid fa-circle-minus"></i> Nuevo Egreso
                </a>
            </div>
        </div>
    </div>

    {{-- ── Alert ── --}}
    @if(session('success'))
    <div id="alert-ok" class="flex items-center gap-3 p-4 rounded-xl glass-effect border-4 border-white/50 text-green-700 shadow-xl">
        <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ── Filtros de período ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl">
        <form action="{{ route('finanzas.index') }}" method="GET">
            <div class="flex flex-col sm:flex-row gap-3 items-end">
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wider">Período</label>
                    <div class="flex gap-2">
                        @foreach(['semana' => 'Esta semana', 'mes' => 'Este mes', 'anio' => 'Este año'] as $val => $label)
                        <a href="{{ route('finanzas.index', array_merge(request()->query(), ['periodo' => $val])) }}"
                           class="px-4 py-2 rounded-xl text-sm font-bold transition-all
                                  {{ $periodo === $val ? 'bg-gradient-to-r from-emerald-600 to-emerald-700 text-white shadow-lg' : 'bg-white border-2 border-gray-200 text-gray-700 hover:border-emerald-400' }}">
                            {{ $label }}
                        </a>
                        @endforeach
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-gray-600 mb-1 uppercase tracking-wider">Finca</label>
                    <select name="finca_id" onchange="this.form.submit()"
                            class="px-4 py-2 rounded-xl border-2 border-gray-300 bg-white text-gray-700 text-sm focus:outline-none focus:border-emerald-500">
                        <option value="">Todas las fincas</option>
                        @foreach($fincas as $f)
                            <option value="{{ $f->id }}" {{ $fincaId == $f->id ? 'selected' : '' }}>{{ $f->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <input type="hidden" name="periodo" value="{{ $periodo }}">
                <p class="text-xs text-gray-500 pb-2">
                    {{ $fechaInicio->format('d/m/Y') }} — {{ $fechaFin->format('d/m/Y') }}
                </p>
            </div>
        </form>
    </div>

    {{-- ── Tarjetas de resumen ── --}}
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        {{-- Ingresos --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl p-5 shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Ingresos</p>
                <span class="w-10 h-10 bg-green-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-arrow-trend-up text-green-600"></i>
                </span>
            </div>
            <p class="text-3xl font-bold text-green-700">
                ${{ number_format($totalIngresos, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 mt-1">COP en el período</p>
        </div>

        {{-- Egresos --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl p-5 shadow-xl">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Egresos</p>
                <span class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-arrow-trend-down text-red-600"></i>
                </span>
            </div>
            <p class="text-3xl font-bold text-red-700">
                ${{ number_format($totalEgresos, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 mt-1">COP en el período</p>
        </div>

        {{-- Balance --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl p-5 shadow-xl
                    {{ $balance >= 0 ? 'ring-2 ring-green-300' : 'ring-2 ring-red-300' }}">
            <div class="flex items-center justify-between mb-3">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider">Balance</p>
                <span class="w-10 h-10 {{ $balance >= 0 ? 'bg-emerald-100' : 'bg-red-100' }} rounded-xl flex items-center justify-center">
                    <i class="fa-solid fa-scale-balanced {{ $balance >= 0 ? 'text-emerald-600' : 'text-red-600' }}"></i>
                </span>
            </div>
            <p class="text-3xl font-bold {{ $balance >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                {{ $balance >= 0 ? '+' : '' }}${{ number_format($balance, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 mt-1">Ingresos − Egresos</p>
        </div>
    </div>

    {{-- ── Gráfica de flujo de caja + desglose ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Gráfica (2/3) --}}
        <div class="lg:col-span-2 glass-effect border-4 border-white/50 rounded-2xl p-6 shadow-xl">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-chart-line text-emerald-600"></i>
                Flujo de caja — últimos 6 meses
            </h2>
            <div class="h-64">
                <canvas id="flujoCajaChart"></canvas>
            </div>
        </div>

        {{-- Desglose ingresos (1/3) --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl p-6 shadow-xl">
            <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-list text-emerald-600"></i>
                Ingresos por tipo
            </h2>
            @php $tiposLabel = \App\Models\Ingreso::getTipos(); @endphp
            @forelse($ingresosPorTipo as $tipo => $monto)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm text-gray-700">{{ $tiposLabel[$tipo] ?? ucfirst($tipo) }}</span>
                <span class="text-sm font-bold text-green-700">${{ number_format($monto, 0, ',', '.') }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">Sin ingresos en este período</p>
            @endforelse

            <h2 class="text-lg font-bold text-gray-800 mt-5 mb-3 flex items-center gap-2">
                <i class="fa-solid fa-list text-red-500"></i>
                Egresos por categoría
            </h2>
            @php $catsLabel = \App\Models\Egreso::getCategorias(); @endphp
            @forelse($egresosPorCategoria as $cat => $monto)
            <div class="flex items-center justify-between py-2 border-b border-gray-100 last:border-0">
                <span class="text-sm text-gray-700">{{ $catsLabel[$cat] ?? ucfirst($cat) }}</span>
                <span class="text-sm font-bold text-red-700">${{ number_format($monto, 0, ',', '.') }}</span>
            </div>
            @empty
            <p class="text-sm text-gray-400 text-center py-4">Sin egresos en este período</p>
            @endforelse
        </div>
    </div>

    {{-- ── Balance por finca ── --}}
    @if(count($balancePorFinca) > 0)
    <div class="glass-effect border-4 border-white/50 rounded-2xl p-6 shadow-xl">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-map-location-dot text-emerald-600"></i>
            Balance por finca
        </h2>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gradient-to-r from-emerald-600 to-emerald-800 text-white text-xs uppercase">
                        <th class="px-4 py-3 text-left">Finca</th>
                        <th class="px-4 py-3 text-right">Ingresos</th>
                        <th class="px-4 py-3 text-right">Egresos</th>
                        <th class="px-4 py-3 text-right">Balance</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($balancePorFinca as $row)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 font-medium text-gray-800">{{ $row['finca'] }}</td>
                        <td class="px-4 py-3 text-right text-green-700 font-semibold">${{ number_format($row['ingresos'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-red-700 font-semibold">${{ number_format($row['egresos'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right font-bold {{ $row['balance'] >= 0 ? 'text-emerald-700' : 'text-red-700' }}">
                            {{ $row['balance'] >= 0 ? '+' : '' }}${{ number_format($row['balance'], 0, ',', '.') }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ── Últimos movimientos ── --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Últimos ingresos --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-circle-plus text-green-600"></i>
                    Últimos ingresos
                </h2>
                <a href="{{ route('finanzas.ingresos.create') }}"
                   class="text-xs font-bold text-emerald-600 hover:text-emerald-800">+ Nuevo</a>
            </div>
            @forelse($ultimosIngresos as $ing)
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 bg-green-100 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-arrow-trend-up text-green-600 text-sm"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ \App\Models\Ingreso::getTipos()[$ing->tipo] ?? $ing->tipo }}</p>
                        <p class="text-xs text-gray-500">{{ $ing->finca->nombre ?? '—' }} · {{ $ing->fecha->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-green-700">${{ number_format($ing->monto, 0, ',', '.') }}</p>
                    <a href="{{ route('finanzas.ingresos.show', $ing) }}"
                       class="text-xs text-gray-400 hover:text-emerald-600">Ver →</a>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Sin ingresos registrados</div>
            @endforelse
        </div>

        {{-- Últimos egresos --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                <h2 class="text-base font-bold text-gray-800 flex items-center gap-2">
                    <i class="fa-solid fa-circle-minus text-red-600"></i>
                    Últimos egresos
                </h2>
                <a href="{{ route('finanzas.egresos.create') }}"
                   class="text-xs font-bold text-red-600 hover:text-red-800">+ Nuevo</a>
            </div>
            @forelse($ultimosEgresos as $egr)
            <div class="flex items-center justify-between px-5 py-3 border-b border-gray-50 hover:bg-gray-50 transition-colors">
                <div class="flex items-center gap-3">
                    <span class="w-9 h-9 bg-red-100 rounded-xl flex items-center justify-center shrink-0">
                        <i class="fa-solid fa-arrow-trend-down text-red-500 text-sm"></i>
                    </span>
                    <div>
                        <p class="text-sm font-semibold text-gray-800">{{ \App\Models\Egreso::getCategorias()[$egr->categoria] ?? $egr->categoria }}</p>
                        <p class="text-xs text-gray-500">{{ $egr->finca->nombre ?? '—' }} · {{ $egr->fecha->format('d/m/Y') }}</p>
                    </div>
                </div>
                <div class="text-right">
                    <p class="text-sm font-bold text-red-700">${{ number_format($egr->monto, 0, ',', '.') }}</p>
                    <a href="{{ route('finanzas.egresos.show', $egr) }}"
                       class="text-xs text-gray-400 hover:text-red-600">Ver →</a>
                </div>
            </div>
            @empty
            <div class="px-5 py-8 text-center text-gray-400 text-sm">Sin egresos registrados</div>
            @endforelse
        </div>
    </div>

    {{-- ── Info costos salud cruzados ── --}}
    @if($costosSalud > 0)
    <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl flex items-center gap-4">
        <span class="w-10 h-10 bg-purple-100 rounded-xl flex items-center justify-center shrink-0">
            <i class="fa-solid fa-heart-pulse text-purple-600"></i>
        </span>
        <div>
            <p class="text-sm font-bold text-gray-800">
                Costos de salud animal en el período:
                <span class="text-purple-700">${{ number_format($costosSalud, 0, ',', '.') }} COP</span>
            </p>
            <p class="text-xs text-gray-500">Registrados en el módulo Salud. Puedes importarlos como egreso usando el botón "Nuevo Egreso → Salud Animal".</p>
        </div>
        <a href="{{ route('finanzas.egresos.create') }}"
           class="ml-auto shrink-0 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white text-xs font-bold rounded-xl transition-all">
            Registrar egreso
        </a>
    </div>
    @endif

</div>


<script>
(function () {
    var flujoCaja = @json($flujoCaja);

    var labels   = flujoCaja.map(function(m){ return m.mes; });
    var ingresos = flujoCaja.map(function(m){ return m.ingresos; });
    var egresos  = flujoCaja.map(function(m){ return m.egresos; });

    function iniciarChart() {
        var ctx = document.getElementById('flujoCajaChart');
        if (!ctx) return;

        new Chart(ctx.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [
                    {
                        label: 'Ingresos',
                        data: ingresos,
                        backgroundColor: 'rgba(5, 150, 105, 0.7)',
                        borderColor: 'rgba(5, 150, 105, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                    },
                    {
                        label: 'Egresos',
                        data: egresos,
                        backgroundColor: 'rgba(220, 38, 38, 0.7)',
                        borderColor: 'rgba(220, 38, 38, 1)',
                        borderWidth: 2,
                        borderRadius: 6,
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top' },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                return ctx.dataset.label + ': $' +
                                    new Intl.NumberFormat('es-CO').format(ctx.parsed.y);
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(v) {
                                return '$' + new Intl.NumberFormat('es-CO').format(v);
                            }
                        }
                    }
                }
            }
        });
    }

    // Compatibilidad: si el DOM ya está listo ejecutar ahora, si no esperar
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', iniciarChart);
    } else {
        iniciarChart();
    }

    // Auto-cerrar alerta
    setTimeout(function() {
        var a = document.getElementById('alert-ok');
        if (a) { a.style.opacity = '0'; a.style.transition = 'opacity 0.5s'; setTimeout(function(){ a.remove(); }, 500); }
    }, 5000);
})();
</script>

@endsection