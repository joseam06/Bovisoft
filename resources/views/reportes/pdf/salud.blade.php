@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card">
                    <div class="kpi-label">Total Registros</div>
                    <div class="kpi-value">{{ $totalRegistros }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card blue">
                    <div class="kpi-label">Vacunaciones</div>
                    <div class="kpi-value">{{ $vacunaciones }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card orange">
                    <div class="kpi-label">En Carencia</div>
                    <div class="kpi-value">{{ $enCarenciaCount }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Costo Total</div>
                    <div class="kpi-value" style="font-size:13px;">${{ number_format($costoTotal, 0, ',', '.') }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Tabla de registros de salud ── --}}
<div class="section-title">Registros Sanitarios del Periodo</div>

@if($registros->count() > 0)

{{-- Los catálogos se resuelven una sola vez, fuera del foreach --}}
@php
    $tiposLabel  = \App\Models\Salud::getTipos();
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
    <tbody>
        @foreach($registros as $r)
        @php
            $enCarenciaFila = $r->en_carencia;

            $tipoBadge = match($r->tipo) {
                'vacunacion'      => 'badge-blue',
                'desparasitacion' => 'badge-green',
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
            <td style="font-family: monospace; font-size:9px;">{{ $r->codigo }}</td>
            <td>{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</td>
            <td><span class="badge {{ $tipoBadge }}">{{ $tiposLabel[$r->tipo] ?? ucfirst($r->tipo) }}</span></td>
            <td>{{ $r->nombre_producto }}</td>
            <td>{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td>
                @if($r->proxima_aplicacion)
                    {{ $r->proxima_aplicacion->format('d/m/Y') }}
                    @if($r->alerta_proxima)
                        <span class="badge badge-red">Pronto</span>
                    @endif
                @else
                    —
                @endif
            </td>
            <td>{{ $r->costo ? '$' . number_format($r->costo, 0, ',', '.') : '—' }}</td>
            <td>
                <span class="badge {{ $estadoBadge }}">
                    {{ $estadosLabel[$r->estado] ?? $r->estado }}
                </span>
                @if($enCarenciaFila)
                    <br><span class="badge badge-yellow">Carencia {{ $r->dias_carencia_restantes }}d</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">COSTO TOTAL DEL PERIODO</td>
            <td>${{ number_format($costoTotal, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay registros sanitarios para los filtros seleccionados.</div>
@endif

{{-- ── Animales en carencia actualmente ── --}}
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
    <tbody>
        @foreach($enCarencia as $c)
        <tr class="carencia-row">
            <td>{{ $c->animal->nombre ?? $c->animal->codigo ?? '—' }}</td>
            <td>{{ $c->finca->nombre ?? '—' }}</td>
            <td>{{ $c->nombre_producto }}</td>
            <td>{{ $c->fin_carencia?->format('d/m/Y') ?? '—' }}</td>
            <td><strong>{{ $c->dias_carencia_restantes }} dias</strong></td>
        </tr>
        @endforeach
    </tbody>
</table>

<div class="obs-box" style="border-left: 4px solid #d97706;">
    <strong>Atencion:</strong> Los animales en periodo de carencia no deben comercializarse (leche ni carne) hasta cumplir la fecha indicada.
</div>
@endif

{{-- ── Resumen de costos ── --}}
<div class="section-title">Resumen de Costos Sanitarios</div>
<div class="obs-box">
    Se registraron <strong>{{ $totalRegistros }}</strong> eventos sanitarios en el periodo,
    con un costo total de <strong>${{ number_format($costoTotal, 0, ',', '.') }} COP</strong>.
    De estos, <strong>{{ $vacunaciones }}</strong> corresponden a vacunaciones y
    <strong>{{ $enCarenciaCount }}</strong> animales se encuentran actualmente en carencia.
</div>

@endsection