@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Tratamientos Activos</div>
                    <div class="kpi-value"><span data-report-stat="totalActivos">{{ $totalActivos }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card green">
                    <div class="kpi-label">Completados</div>
                    <div class="kpi-value"><span data-report-stat="totalCompletados">{{ $totalCompletados }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card orange">
                    <div class="kpi-label">En Carencia</div>
                    <div class="kpi-value"><span data-report-stat="enCarenciaCount">{{ $enCarenciaCount }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card">
                    <div class="kpi-label">Costo Total</div>
                    <div class="kpi-value" style="font-size:12px;">$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span></div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Tratamientos activos ── --}}
<div class="section-title">Tratamientos Activos (en_tratamiento)</div>
@if($activos->count() > 0)
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Animal</th>
            <th>Finca</th>
            <th>Producto / Diagnostico</th>
            <th>Fecha Inicio</th>
            <th>Veterinario</th>
            <th>Carencia</th>
            <th>Costo</th>
        </tr>
    </thead>
    <tbody data-report-collection="activos">
        @foreach($activos as $t)
        <tr @if($t->en_carencia) class="highlight-row" @endif>
            <td style="font-family: monospace; font-size:9px;"><span data-offline-owner="salud" data-offline-field="codigo">{{ $t->codigo }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $t->animal->nombre ?? $t->animal->codigo ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="finca_nombre">{{ $t->finca->nombre ?? '—' }}</span></td>
            <td>
                <strong><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $t->nombre_producto }}</span></strong>
                @if($t->diagnostico)
                    <br><span style="font-size:9px; color:#6b7280;"><span data-offline-owner="salud" data-offline-field="diagnostico">{{ $t->diagnostico }}</span></span>
                @endif
            </td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $t->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="veterinario">{{ $t->veterinario ?? '—' }}</span></td>
            <td>
                @if($t->en_carencia)
                    <span class="badge badge-yellow"><span data-offline-owner="salud" data-offline-field="dias_carencia_restantes">{{ $t->dias_carencia_restantes }}</span>d restantes</span>
                @elseif($t->dias_carencia > 0)
                    <span class="badge badge-green">Finalizada</span>
                @else
                    —
                @endif
            </td>
            <td><span data-offline-owner="salud" data-offline-field="costo">{{ $t->costo ? '$' . number_format($t->costo, 0, ',', '.') : '—' }}</span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="obs-box">No hay tratamientos activos en el periodo seleccionado.</div>
@endif

{{-- ── Historial de tratamientos ── --}}
<div class="section-title">Historial de Tratamientos del Periodo</div>
@if($historial->count() > 0)
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
            <th>Diagnostico</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Costo</th>
        </tr>
    </thead>
    <tbody data-report-collection="historial">
        @foreach($historial as $h)
        @php
            $estadoBadge = match($h->estado) {
                'completado'     => 'badge-green',
                'en_tratamiento' => 'badge-orange',
                'pendiente'      => 'badge-yellow',
                'cancelado'      => 'badge-gray',
                default          => 'badge-gray',
            };
            $tipoBadge = match($h->tipo) {
                'tratamiento' => 'badge-purple',
                'cirugia'     => 'badge-red',
                'revision'    => 'badge-yellow',
                default       => 'badge-gray',
            };
        @endphp
        <tr>
            <td style="font-family: monospace; font-size:9px;"><span data-offline-owner="salud" data-offline-field="codigo">{{ $h->codigo }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $h->animal->nombre ?? $h->animal->codigo ?? '—' }}</span></td>
            <td><span class="badge {{ $tipoBadge }}"><span data-offline-owner="salud" data-offline-field="tipo">{{ $tiposLabel[$h->tipo] ?? ucfirst($h->tipo) }}</span></span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $h->nombre_producto }}</span></td>
            <td style="font-size:9px;"><span data-offline-owner="salud" data-offline-field="diagnostico">{{ $h->diagnostico ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $h->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span class="badge {{ $estadoBadge }}"><span data-offline-owner="salud" data-offline-field="estado">{{ $estadosLabel[$h->estado] ?? $h->estado }}</span></span></td>
            <td><span data-offline-owner="salud" data-offline-field="costo">{{ $h->costo ? '$' . number_format($h->costo, 0, ',', '.') : '—' }}</span></td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7">COSTO TOTAL</td>
            <td>$<span data-report-stat="costoTotal">{{ \App\Services\Money::format($costoTotal) }}</span></td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay tratamientos registrados para los filtros seleccionados.</div>
@endif

@endsection