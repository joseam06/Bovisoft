@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card blue">
                    <div class="kpi-label">Total Vacunaciones</div>
                    <div class="kpi-value"><span data-report-stat="totalVacunaciones">{{ $totalVacunaciones }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card teal">
                    <div class="kpi-label">Desparasitaciones</div>
                    <div class="kpi-value"><span data-report-stat="totalDesparasitaciones">{{ $totalDesparasitaciones }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card orange">
                    <div class="kpi-label">Proximas 7 dias</div>
                    <div class="kpi-value"><span data-report-stat="proximasSiete">{{ $proximasSiete }}</span></div>
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

{{-- ── Calendario proximas dosis ── --}}
@if($proximasDosis->count() > 0)
<div class="section-title">Calendario de Proximas Dosis (proximos 60 dias)</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Animal</th>
            <th>Finca</th>
            <th>Producto</th>
            <th>Tipo</th>
            <th>Ultima Aplicacion</th>
            <th>Proxima Dosis</th>
            <th>Dias Restantes</th>
        </tr>
    </thead>
    <tbody data-report-collection="proximasDosis">
        @foreach($proximasDosis as $d)
        @php $dias = now()->diffInDays($d->proxima_aplicacion, false); @endphp
        <tr @if($dias <= 7) class="highlight-row" @endif>
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $d->animal->nombre ?? $d->animal->codigo ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="finca_nombre">{{ $d->finca->nombre ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $d->nombre_producto }}</span></td>
            <td>
                <span class="badge {{ $d->tipo === 'vacunacion' ? 'badge-blue' : 'badge-teal' }}">
                    <span data-offline-owner="salud" data-offline-field="tipo">{{ $d->tipo === 'vacunacion' ? 'Vacuna' : 'Desparasitacion' }}</span>
                </span>
            </td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $d->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="proxima_aplicacion">{{ $d->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td>
                @if($dias <= 0)
                    <span class="badge badge-red">Vencida</span>
                @elseif($dias <= 7)
                    <span class="badge badge-yellow"><span data-offline-owner="salud" data-offline-field="dias_proxima">{{ $dias }}</span> dias</span>
                @else
                    <span class="badge badge-green"><span data-offline-owner="salud" data-offline-field="dias_proxima">{{ $dias }}</span> dias</span>
                @endif
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="obs-box">No hay proximas dosis programadas en los proximos 60 dias.</div>
@endif

{{-- ── Historial de vacunaciones ── --}}
<div class="section-title">Historial de Vacunaciones y Desparasitaciones</div>
@if($registros->count() > 0)
@php $tiposLabel = \App\Models\Salud::getTipos(); @endphp
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Animal</th>
            <th>Tipo</th>
            <th>Producto</th>
            <th>Lote</th>
            <th>Fecha Aplicacion</th>
            <th>Prox. Dosis</th>
            <th>Costo</th>
        </tr>
    </thead>
    <tbody data-report-collection="registros">
        @foreach($registros as $r)
        <tr>
            <td style="font-family: monospace; font-size:9px;"><span data-offline-owner="salud" data-offline-field="codigo">{{ $r->codigo }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="animal_nombre">{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</span></td>
            <td>
                <span class="badge {{ $r->tipo === 'vacunacion' ? 'badge-blue' : 'badge-teal' }}">
                    <span data-offline-owner="salud" data-offline-field="tipo">{{ $tiposLabel[$r->tipo] ?? ucfirst($r->tipo) }}</span>
                </span>
            </td>
            <td><span data-offline-owner="salud" data-offline-field="nombre_producto">{{ $r->nombre_producto }}</span></td>
            <td style="font-size:9px; color:#6b7280;"><span data-offline-owner="salud" data-offline-field="lote_medicamento">{{ $r->lote_medicamento ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="fecha_aplicacion">{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="proxima_aplicacion">{{ $r->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</span></td>
            <td><span data-offline-owner="salud" data-offline-field="costo">{{ $r->costo ? '$' . number_format($r->costo, 0, ',', '.') : '—' }}</span></td>
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
<div class="obs-box">No hay registros para los filtros seleccionados.</div>
@endif

@endsection