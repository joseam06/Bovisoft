@extends('layouts.dashboard')

@section('title', 'Reportes')

@section('content')
<div class="animate-slide-up space-y-6">

    {{-- ── Header ── --}}
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-blue-600 to-blue-800 p-6 shadow-xl">
        <div class="absolute top-0 right-0 -mt-6 -mr-6 w-32 h-32 bg-white/10 rounded-full blur-2xl"></div>
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 relative z-10">
            <div>
                <h1 class="text-3xl font-bold text-white flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow-lg">
                        <i class="fa-solid fa-file-chart-column text-white text-2xl"></i>
                    </span>
                    Reportes
                </h1>
                <p class="text-blue-100 mt-1 ml-15">Genera reportes en PDF de tu operacion ganadera</p>
            </div>
        </div>
    </div>

    {{-- ── Flash ── --}}
    @if(session('success'))
    <div id="alert-ok" class="flex items-center gap-3 p-4 rounded-xl glass-effect border-4 border-white/50 text-green-700 shadow-xl">
        <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ── Estadísticas rápidas ── --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Animales</p>
                <p class="text-2xl font-bold text-red-700">{{ $totalAnimales }}</p>
            </div>
            <span class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-cow text-red-600"></i>
            </span>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Registros Salud</p>
                <p class="text-2xl font-bold text-purple-700">{{ $totalSalud }}</p>
            </div>
            <span class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-heart-pulse text-purple-600"></i>
            </span>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Ingresos</p>
                <p class="text-2xl font-bold text-green-700">${{ number_format($totalIngresos, 0, ',', '.') }}</p>
            </div>
            <span class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-arrow-trend-up text-green-600"></i>
            </span>
        </div>
        <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl flex items-center justify-between">
            <div>
                <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total Egresos</p>
                <p class="text-2xl font-bold text-red-700">${{ number_format($totalEgresos, 0, ',', '.') }}</p>
            </div>
            <span class="w-10 h-10 bg-red-100 rounded-lg flex items-center justify-center">
                <i class="fa-solid fa-arrow-trend-down text-red-600"></i>
            </span>
        </div>
    </div>

    {{-- ── Filtros globales ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl p-6">
        <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
            <i class="fa-solid fa-filter text-blue-600"></i> Filtros del reporte
        </h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4" id="filtros-container">

            {{-- Período --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Período</label>
                <select id="sel-periodo"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="dia">Hoy</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes" selected>Este mes</option>
                    <option value="anio">Este año</option>
                    <option value="todos">Todos los registros</option>
                    <option value="custom">Rango personalizado</option>
                </select>
            </div>

            {{-- Finca --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Finca</label>
                <select id="sel-finca"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Todas las fincas</option>
                    @foreach($fincas as $f)
                        <option value="{{ $f->id }}">{{ $f->nombre }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Fecha inicio (custom) --}}
            <div id="div-fecha-inicio" class="hidden">
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha inicio</label>
                <input type="date" id="inp-fecha-inicio"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            {{-- Fecha fin (custom) --}}
            <div id="div-fecha-fin" class="hidden">
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha fin</label>
                <input type="date" id="inp-fecha-fin"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    {{-- ── Tarjetas de reportes ── --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- INVENTARIO --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-red-600 to-red-800 p-5">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-cow text-white text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-white">Inventario Animal</h3>
                        <p class="text-red-100 text-sm">Listado completo del ganado</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> KPIs: total, vacas activas, vendidos, en carencia</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Tabla con codigo, tipo, raza, edad y peso</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Resumen estadistico por tipo</li>
                </ul>
                <div class="flex gap-2">
                    <button onclick="generarReporte('inventario', true)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl text-sm transition-all shadow-lg">
                        <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                    <button onclick="generarReporte('inventario', false)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-red-300 text-red-700 hover:bg-red-50 font-bold rounded-xl text-sm transition-all">
                        <i class="fa-solid fa-download"></i> Descargar
                    </button>
                </div>
            </div>
        </div>

        {{-- SALUD --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-purple-600 to-purple-800 p-5">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-heart-pulse text-white text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-white">Salud Animal</h3>
                        <p class="text-purple-100 text-sm">Historial sanitario y vacunaciones</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> KPIs: total, vacunaciones, en carencia, costo</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Tabla con producto, fecha aplicacion y costo</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Animales en carencia destacados</li>
                </ul>
                <div class="flex gap-2">
                    <button onclick="generarReporte('salud', true)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-600 to-purple-700 hover:from-purple-700 hover:to-purple-800 text-white font-bold rounded-xl text-sm transition-all shadow-lg">
                        <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                    <button onclick="generarReporte('salud', false)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-purple-300 text-purple-700 hover:bg-purple-50 font-bold rounded-xl text-sm transition-all">
                        <i class="fa-solid fa-download"></i> Descargar
                    </button>
                </div>
            </div>
        </div>

        {{-- FINANCIERO --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-emerald-600 to-emerald-800 p-5">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-money-bill-trend-up text-white text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-white">Reporte Financiero</h3>
                        <p class="text-emerald-100 text-sm">Ingresos, egresos y balance</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> KPIs: total ingresos, egresos y balance</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Tablas detalladas de ingresos y egresos</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Barras proporcionales por monto</li>
                </ul>
                <div class="flex gap-2">
                    <button onclick="generarReporte('financiero', true)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-emerald-700 hover:from-emerald-700 hover:to-emerald-800 text-white font-bold rounded-xl text-sm transition-all shadow-lg">
                        <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                    <button onclick="generarReporte('financiero', false)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-emerald-300 text-emerald-700 hover:bg-emerald-50 font-bold rounded-xl text-sm transition-all">
                        <i class="fa-solid fa-download"></i> Descargar
                    </button>
                </div>
            </div>
        </div>

    </div>

    {{-- ── Info de uso ── --}}
    <div class="glass-effect border-4 border-white/50 rounded-xl p-4 shadow-xl flex items-center gap-4">
        <span class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center shrink-0">
            <i class="fa-solid fa-circle-info text-blue-600"></i>
        </span>
        <div class="text-sm text-gray-700">
            <p class="font-bold">Como usar los reportes</p>
            <p class="text-gray-600 mt-0.5">Selecciona el <strong>periodo</strong> y la <strong>finca</strong> que deseas analizar, luego haz clic en <strong>Vista previa</strong> para ver el PDF en el navegador o en <strong>Descargar</strong> para guardarlo.</p>
        </div>
    </div>

</div>

<script>
    // Mostrar / ocultar campos de rango personalizado
    document.getElementById('sel-periodo').addEventListener('change', function () {
        var isCustom = this.value === 'custom';
        document.getElementById('div-fecha-inicio').classList.toggle('hidden', !isCustom);
        document.getElementById('div-fecha-fin').classList.toggle('hidden', !isCustom);
    });

    function buildUrl(tipo, preview) {
        var periodo    = document.getElementById('sel-periodo').value;
        var fincaId    = document.getElementById('sel-finca').value;
        var fechaIni   = document.getElementById('inp-fecha-inicio').value;
        var fechaFin   = document.getElementById('inp-fecha-fin').value;

        var base = preview
            ? '{{ url("/reportes/preview") }}/' + tipo
            : '{{ url("/reportes/descargar") }}/' + tipo;

        var params = '?periodo=' + encodeURIComponent(periodo);

        if (fincaId)  params += '&finca_id='     + encodeURIComponent(fincaId);
        if (periodo === 'custom') {
            if (fechaIni) params += '&fecha_inicio=' + encodeURIComponent(fechaIni);
            if (fechaFin) params += '&fecha_fin='    + encodeURIComponent(fechaFin);
        }

        return base + params;
    }

    function generarReporte(tipo, preview) {
        var url = buildUrl(tipo, preview);
        if (preview) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }

    // Auto cerrar flash
    setTimeout(function () {
        var a = document.getElementById('alert-ok');
        if (a) { a.style.opacity = '0'; a.style.transition = 'opacity 0.5s'; setTimeout(function () { a.remove(); }, 500); }
    }, 5000);
</script>
@endsection