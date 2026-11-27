@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="16%">
                <div class="kpi-card">
                    <div class="kpi-label">Total Registros</div>
                    <div class="kpi-value"><span data-report-stat="totalRegistros">{{ $totalRegistros }}</span></div>
                </div>
            </td>
            <td width="16%">
                <div class="kpi-card blue">
                    <div class="kpi-label">Vacunaciones</div>
                    <div class="kpi-value"><span data-report-stat="vacunaciones">{{ $vacunaciones }}</span></div>
                </div>
            </td>
            <td width="16%">
                <div class="kpi-card teal">
                    <div class="kpi-label">Desparasitaciones</div>
                    <div class="kpi-value"><span data-report-stat="desparasitaciones">{{ $desparasitaciones }}</span></div>
                </div>
            </td>
            <td width="16%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Tratamientos</div>
                    <div class="kpi-value"><span data-report-stat="tratamientosActivos">{{ $tratamientosActivos }}</span></div>
                </div>
            </td>
            <td width="16%">
                <div class="kpi-card orange">
                    <div class="kpi-label">En Carencia</div>
                    <div class="kpi-value"><span data-report-stat="enCarenciaCount">{{ $enCarenciaCount }}</span></div>
                </div>
            </td>
            <td width="16%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Costo Total</div>
                    <div class="kpi-value" style="font-size:11px;">$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span></div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Alertas sanitarias ── --}}
@if($alertasVencidas->count() > 0)
<div class="alert-box alert-red">
    <strong>ALERTAS VENCIDAS ({{ $alertasVencidas->count() }}):</strong> Los siguientes animales tienen dosis pendientes vencidas.
</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Animal</th>
            <th>Producto</th>
            <th>Proxima Dosis</th>
            <th>Dias Vencido</th>
            <th>Finca</th>
        </tr>
    </thead>
    <tbody data-report-collection="alertasVencidas">
        @foreach($alertasVencidas as $a)
        <tr class="danger-row">
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $a->animal->nombre ?? $a->animal->codigo ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $a->nombre_producto }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="proxima_aplicacion">{{ $a->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span class="badge badge-red"><span data-offline-owner="salud" data-offline-field="dias_proxima">{{ abs($a->proxima_aplicacion->diffInDays(now())) }}</span> dias</span></td>
            <td><span data-offline-owner="salud" data-offline-field="finca_nombre">{{ $a->finca->nombre ?? '—' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

@if($alertasProximas->count() > 0)
<div class="alert-box alert-yellow">
    <strong>PROXIMAS DOSIS (7 dias) — {{ $alertasProximas->count() }} animales:</strong>
</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Animal</th>
            <th>Producto</th>
            <th>Fecha Proxima Dosis</th>
            <th>Dias Restantes</th>
            <th>Finca</th>
        </tr>
    </thead>
    <tbody data-report-collection="alertasProximas">
        @foreach($alertasProximas as $a)
        <tr class="highlight-row">
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $a->animal->nombre ?? $a->animal->codigo ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $a->nombre_producto }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="proxima_aplicacion">{{ $a->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span class="badge badge-yellow"><span data-offline-owner="salud" data-offline-field="dias_proxima">{{ $a->proxima_aplicacion->diffInDays(now()) }}</span> dias</span></td>
            <td><span data-offline-owner="salud" data-offline-field="finca_nombre">{{ $a->finca->nombre ?? '—' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ── Tratamientos activos ── --}}
@if($tratamientosActivos > 0)
<div class="section-title">Tratamientos Activos (en_tratamiento)</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Animal</th>
            <th>Producto</th>
            <th>Diagnostico</th>
            <th>Fecha Inicio</th>
            <th>Veterinario</th>
            <th>Costo</th>
        </tr>
    </thead>
    <tbody data-report-collection="registrosTratamientos">
        @foreach($registrosTratamientos as $t)
        <tr>
            <td style="font-family: monospace; font-size:9px;"><span data-offline-owner="salud" data-offline-field="codigo">{{ $t->codigo }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $t->animal->nombre ?? $t->animal->codigo ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $t->nombre_producto }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="diagnostico">{{ $t->diagnostico ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $t->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="veterinario">{{ $t->veterinario ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="costo">{{ $t->costo ? '$' . number_format($t->costo, 0, ',', '.') : '—' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- ── Tabla completa de registros ── --}}
<div class="section-title">Registros Sanitarios del Periodo</div>

@if($registros->count() > 0)
@php
    $tiposLabel   = \App\Models\Salud::getTipos();
    $estadosLabel = \App\Models\Salud::getEstados();
@endphp
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Animal</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>Fecha Aplic.</th>
            <th>Prox. Dosis</th>
            <th>Costo</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody data-report-collection="registros">
        @foreach($registros as $r)
        @php
            $enCarenciaFila = $r->en_carencia;
            $tipoBadge = match($r->tipo) {
                'vacunacion'      => 'badge-blue',
                'desparasitacion' => 'badge-teal',
                'tratamiento'     => 'badge-purple',
                'cirugia'         => 'badge-red',
                'revision'        => 'badge-yellow',
                default           => 'badge-gray',
            };
            $estadoBadge = match($r->estado) {
                'completado'     => 'badge-green',
                'en_tratamiento' => 'badge-orange',
                'pendiente'      => 'badge-yellow',
                'cancelado'      => 'badge-gray',
                default          => 'badge-gray',
            };
        @endphp
        <tr @if($enCarenciaFila) style="background-color:#fefce8;" @endif>
            <td style="font-family: monospace; font-size:9px;"><span data-offline-owner="salud" data-offline-field="codigo">{{ $r->codigo }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</span></td>
            <td><span class="badge {{ $tipoBadge }}"><span data-offline-owner="salud" data-offline-field="tipo">{{ $tiposLabel[$r->tipo] ?? ucfirst($r->tipo) }}</span></span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $r->nombre_producto }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td>
                @if($r->proxima_aplicacion)
                    <span data-offline-owner="salud" data-offline-field="proxima_aplicacion">{{ $r->proxima_aplicacion->format('d/m/Y') }}</span>
                    @if($r->alerta_proxima)
                        <span class="badge badge-red">Pronto</span>
                    @endif
                @else
                    —
                @endif
            </td>
            <td><span data-offline-owner="salud" data-offline-field="costo">{{ $r->costo ? '$' . number_format($r->costo, 0, ',', '.') : '—' }}</span></td>
            <td>
                <span class="badge {{ $estadoBadge }}"><span data-offline-owner="salud" data-offline-field="estado">{{ $estadosLabel[$r->estado] ?? $r->estado }}</span></span>
                @if($enCarenciaFila)
                    <br><span class="badge badge-yellow">Carencia <span data-offline-owner="salud" data-offline-field="dias_carencia_restantes">{{ $r->dias_carencia_restantes }}</span>d</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">COSTO TOTAL DEL PERIODO</td>
            <td>$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span></td>
            <td></td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay registros sanitarios para los filtros seleccionados.</div>
@endif

{{-- ── Animales en carencia ── --}}
@if($enCarencia->count() > 0)
<div class="section-title">Animales Actualmente en Periodo de Carencia</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Animal</th>
            <th>Finca</th>
            <th>Producto</th>
            <th>Fin Carencia</th>
            <th>Dias Restantes</th>
        </tr>
    </thead>
    <tbody data-report-collection="enCarencia">
        @foreach($enCarencia as $c)
        <tr class="highlight-row">
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $c->animal->nombre ?? $c->animal->codigo ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="finca_nombre">{{ $c->finca->nombre ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $c->nombre_producto }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="fin_carencia">{{ $c->fin_carencia?->format('d/m/Y') ?? '—' }}</span></td>
            <td><strong><span data-offline-owner="salud" data-offline-field="dias_carencia_restantes">{{ $c->dias_carencia_restantes }}</span> dias</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>
<div class="obs-box" style="border-left: 4px solid #d97706;">
    <strong>Atencion:</strong> Los animales en periodo de carencia no deben comercializarse (leche ni carne) hasta cumplir la fecha indicada.
</div>
@endif

{{-- ── Resumen ── --}}
<div class="section-title">Resumen de Costos Sanitarios</div>
<div class="obs-box">
    Se registraron <strong><span data-report-stat="totalRegistros">{{ $totalRegistros }}</span></strong> eventos sanitarios en el periodo,
    con un costo total de <strong>$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span> COP</strong>.
    Vacunaciones: <strong><span data-report-stat="vacunaciones">{{ $vacunaciones }}</span></strong> &mdash;
    Desparasitaciones: <strong><span data-report-stat="desparasitaciones">{{ $desparasitaciones }}</span></strong> &mdash;
    Tratamientos activos: <strong><span data-report-stat="tratamientosActivos">{{ $tratamientosActivos }}</span></strong> &mdash;
    Animales en carencia: <strong><span data-report-stat="enCarenciaCount">{{ $enCarenciaCount }}</span></strong>.
</div>

@endsection