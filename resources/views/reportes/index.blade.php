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

    @if(session('success'))
    <div id="alert-ok" class="flex items-center gap-3 p-4 rounded-xl glass-effect border-4 border-white/50 text-green-700 shadow-xl">
        <i class="fa-solid fa-circle-check text-xl shrink-0"></i>
        <span class="font-medium">{{ session('success') }}</span>
    </div>
    @endif

    {{-- ── Estadisticas rapidas ── --}}
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
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Periodo</label>
                <select id="sel-periodo"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="dia">Hoy</option>
                    <option value="semana">Esta semana</option>
                    <option value="mes" selected>Este mes</option>
                    <option value="anio">Este ano</option>
                    <option value="todos">Todos los registros</option>
                    <option value="custom">Rango personalizado</option>
                </select>
            </div>

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

            {{-- Animal (para historial clinico) --}}
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Animal (historial)</label>
                <select id="sel-animal"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Seleccionar animal</option>
                    @foreach($animales as $a)
                        <option value="{{ $a->id }}">{{ $a->codigo }}{{ $a->nombre ? ' - ' . $a->nombre : '' }}</option>
                    @endforeach
                </select>
            </div>

            <div id="div-fecha-inicio" class="hidden">
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha inicio</label>
                <input type="date" id="inp-fecha-inicio"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <div id="div-fecha-fin" class="hidden">
                <label class="block text-sm font-bold text-gray-700 mb-2">Fecha fin</label>
                <input type="date" id="inp-fecha-fin"
                    class="w-full px-4 py-2.5 bg-white border-2 border-gray-300 rounded-xl text-gray-800 text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>
    </div>

    {{-- ── Tarjetas de reportes ── --}}

    {{-- Fila 1: reportes principales --}}
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
                        <p class="text-purple-100 text-sm">Historial sanitario completo</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> KPIs separados: vacunas, desparasitaciones, tratamientos</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Alertas vencidas y proximas 7 dias</li>
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
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Tablas detalladas con barras proporcionales</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Costos de salud cruzados</li>
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

    {{-- Fila 2: reportes nuevos --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">

        {{-- VACUNACION --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 p-5">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-syringe text-white text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-white">Vacunacion</h3>
                        <p class="text-blue-100 text-sm">Calendario de proximas dosis</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Vacunaciones y desparasitaciones separadas</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Calendario de proximas dosis (60 dias)</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Lote, laboratorio y costo por aplicacion</li>
                </ul>
                <div class="flex gap-2">
                    <button onclick="generarReporte('vacunacion', true)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-bold rounded-xl text-sm transition-all shadow-lg">
                        <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                    <button onclick="generarReporte('vacunacion', false)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-blue-300 text-blue-700 hover:bg-blue-50 font-bold rounded-xl text-sm transition-all">
                        <i class="fa-solid fa-download"></i> Descargar
                    </button>
                </div>
            </div>
        </div>

        {{-- TRATAMIENTOS --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-orange-600 to-orange-800 p-5">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-pills text-white text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-white">Tratamientos</h3>
                        <p class="text-orange-100 text-sm">Tratamientos activos y clinicos</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Tratamientos en curso destacados</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Diagnostico y veterinario responsable</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Historial de cirugias y revisiones</li>
                </ul>
                <div class="flex gap-2">
                    <button onclick="generarReporte('tratamientos', true)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-orange-600 to-orange-700 hover:from-orange-700 hover:to-orange-800 text-white font-bold rounded-xl text-sm transition-all shadow-lg">
                        <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                    <button onclick="generarReporte('tratamientos', false)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-orange-300 text-orange-700 hover:bg-orange-50 font-bold rounded-xl text-sm transition-all">
                        <i class="fa-solid fa-download"></i> Descargar
                    </button>
                </div>
            </div>
        </div>

        {{-- HISTORIAL POR ANIMAL --}}
        <div class="glass-effect border-4 border-white/50 rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-teal-600 to-teal-800 p-5">
                <div class="flex items-center gap-3">
                    <span class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center shadow">
                        <i class="fa-solid fa-file-medical text-white text-2xl"></i>
                    </span>
                    <div>
                        <h3 class="text-xl font-bold text-white">Historial Clinico</h3>
                        <p class="text-teal-100 text-sm">Por animal individual</p>
                    </div>
                </div>
            </div>
            <div class="p-5">
                <ul class="text-sm text-gray-700 space-y-1.5 mb-5">
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Ficha completa del animal</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Todo el historial sanitario individual</li>
                    <li class="flex items-center gap-2"><i class="fa-solid fa-check-circle text-green-500 text-xs"></i> Selecciona el animal en los filtros</li>
                </ul>
                <div class="flex gap-2">
                    <button onclick="generarReporte('historial', true)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-gradient-to-r from-teal-600 to-teal-700 hover:from-teal-700 hover:to-teal-800 text-white font-bold rounded-xl text-sm transition-all shadow-lg">
                        <i class="fa-solid fa-eye"></i> Vista previa
                    </button>
                    <button onclick="generarReporte('historial', false)"
                        class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-white border-2 border-teal-300 text-teal-700 hover:bg-teal-50 font-bold rounded-xl text-sm transition-all">
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
            <p class="text-gray-600 mt-0.5">Selecciona el <strong>periodo</strong> y la <strong>finca</strong>, luego haz clic en <strong>Vista previa</strong> o <strong>Descargar</strong>. Para el historial clinico debes seleccionar el <strong>animal</strong> en los filtros primero.</p>
        </div>
    </div>

</div>

<script>
    document.getElementById('sel-periodo').addEventListener('change', function () {
        var isCustom = this.value === 'custom';
        document.getElementById('div-fecha-inicio').classList.toggle('hidden', !isCustom);
        document.getElementById('div-fecha-fin').classList.toggle('hidden', !isCustom);
    });

    function buildUrl(tipo, preview) {
        var periodo  = document.getElementById('sel-periodo').value;
        var fincaId  = document.getElementById('sel-finca').value;
        var animalId = document.getElementById('sel-animal').value;
        var fechaIni = document.getElementById('inp-fecha-inicio').value;
        var fechaFin = document.getElementById('inp-fecha-fin').value;

        var base = preview
            ? '{{ url("/reportes/preview") }}/' + tipo
            : '{{ url("/reportes/descargar") }}/' + tipo;

        var params = '?periodo=' + encodeURIComponent(periodo);

        if (fincaId)  params += '&finca_id='     + encodeURIComponent(fincaId);
        if (animalId) params += '&animal_id='    + encodeURIComponent(animalId);

        if (periodo === 'custom') {
            if (fechaIni) params += '&fecha_inicio=' + encodeURIComponent(fechaIni);
            if (fechaFin) params += '&fecha_fin='    + encodeURIComponent(fechaFin);
        }

        return base + params;
    }

    function generarReporte(tipo, preview) {
        if (tipo === 'historial') {
            var animalId = document.getElementById('sel-animal').value;
            if (!animalId) {
                alert('Selecciona un animal para generar el historial clinico.');
                return;
            }
        }
        var url = buildUrl(tipo, preview);
        if (preview) {
            window.open(url, '_blank');
        } else {
            window.location.href = url;
        }
    }

    setTimeout(function () {
        var a = document.getElementById('alert-ok');
        if (a) { a.style.opacity = '0'; a.style.transition = 'opacity 0.5s'; setTimeout(function () { a.remove(); }, 500); }
    }, 5000);
</script>
@endsection