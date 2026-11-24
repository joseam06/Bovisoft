@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── Ficha del animal ── --}}
<div class="section-title">Datos del Animal</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <tbody>
        <tr>
            <td style="width:20%; font-weight:bold; background:#f3f4f6;">Codigo</td>
            <td style="font-family: monospace;"><span data-offline-owner="animales" data-offline-field="codigo">{{ $animal->codigo }}</span></td>
            <td style="width:20%; font-weight:bold; background:#f3f4f6;">Nombre</td>
            <td><span data-offline-owner="animales" data-offline-field="nombre">{{ $animal->nombre ?? '—' }}</span></td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f3f4f6;">Tipo</td>
            <td><span data-offline-owner="animales" data-offline-field="tipo">{{ ucfirst($animal->tipo) }}</span></td>
            <td style="font-weight:bold; background:#f3f4f6;">Raza</td>
            <td><span data-offline-owner="animales" data-offline-field="raza">{{ $animal->raza ?? '—' }}</span></td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f3f4f6;">Finca</td>
            <td><span data-offline-owner="animales" data-offline-field="finca_nombre">{{ $animal->finca->nombre ?? '—' }}</span></td>
            <td style="font-weight:bold; background:#f3f4f6;">Estado</td>
            <td><span data-offline-owner="animales" data-offline-field="estado">{{ ucfirst($animal->estado) }}</span></td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f3f4f6;">Peso actual</td>
            <td><span data-offline-owner="animales" data-offline-field="peso_actual">{{ $animal->peso_actual ? number_format($animal->peso_actual, 1) . ' kg' : '—' }}</span></td>
            <td style="font-weight:bold; background:#f3f4f6;">Edad</td>
            <td><span data-offline-owner="animales" data-offline-field="edad">{{ $animal->edad ?? '—' }}</span></td>
        </tr>
    </tbody>
</table>

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card">
                    <div class="kpi-label">Total Eventos</div>
                    <div class="kpi-value"><span data-report-stat="totalRegistros">{{ $totalRegistros }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card blue">
                    <div class="kpi-label">Vacunaciones</div>
                    <div class="kpi-value"><span data-report-stat="vacunaciones">{{ $vacunaciones }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Tratamientos</div>
                    <div class="kpi-value"><span data-report-stat="tratamientos">{{ $tratamientos }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Costo Total</div>
                    <div class="kpi-value" style="font-size:12px;">$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span></div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Historial completo ── --}}
<div class="section-title">Historial Clinico Completo</div>
@if($registros->count() > 0)
@php
    $tiposLabel   = \App\Models\Salud::getTipos();
    $estadosLabel = \App\Models\Salud::getEstados();
@endphp
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Tipo</th>
            <th>Producto / Diagnostico</th>
            <th>Fecha Aplic.</th>
            <th>Veterinario</th>
            <th>Dosis</th>
            <th>Carencia</th>
            <th>Estado</th>
            <th>Costo</th>
        </tr>
    </thead>
    <tbody data-report-collection="registros">
        @foreach($registros as $r)
        @php
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
        <tr @if($r->en_carencia) class="highlight-row" @endif>
            <td style="font-family: monospace; font-size:9px;"><span data-offline-owner="salud" data-offline-field="codigo">{{ $r->codigo }}</span></td>
            <td><span class="badge {{ $tipoBadge }}"><span data-offline-owner="salud" data-offline-field="tipo">{{ $tiposLabel[$r->tipo] ?? ucfirst($r->tipo) }}</span></span></td>
            <td>
                <strong><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $r->nombre_producto }}</span></strong>
                @if($r->diagnostico)
                    <br><span style="font-size:9px; color:#6b7280;"><span data-offline-owner="salud" data-offline-field="diagnostico">{{ $r->diagnostico }}</span></span>
                @endif
            </td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td style="font-size:9px;"><span data-offline-owner="salud" data-offline-field="veterinario">{{ $r->veterinario ?? '—' }}</span></td>
            <td style="font-size:9px;"><span data-offline-owner="salud" data-offline-field="dosis">{{ $r->dosis ? $r->dosis . ' ' . $r->unidad_dosis : '—' }}</span></td>
            <td>
                @if($r->dias_carencia > 0)
                    <span data-offline-owner="salud" data-offline-field="dias_carencia">{{ $r->dias_carencia }}</span>d
                    @if($r->en_carencia)
                        <span class="badge badge-yellow">Activa</span>
                    @endif
                @else
                    —
                @endif
            </td>
            <td><span class="badge {{ $estadoBadge }}"><span data-offline-owner="salud" data-offline-field="estado">{{ $estadosLabel[$r->estado] ?? $r->estado }}</span></span></td>
            <td><span data-offline-owner="salud" data-offline-field="costo">{{ $r->costo ? '$' . number_format($r->costo, 0, ',', '.') : '—' }}</span></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8">COSTO TOTAL HISTORICO</td>
            <td>$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span></td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay registros sanitarios para este animal.</div>
@endif

{{-- ── Observaciones del animal ── --}}
@if($animal->observaciones)
<div class="section-title">Observaciones Generales del Animal</div>
<div class="obs-box"><span data-offline-owner="animales" data-offline-field="observaciones">{{ $animal->observaciones }}</span></div>
@endif

@endsection