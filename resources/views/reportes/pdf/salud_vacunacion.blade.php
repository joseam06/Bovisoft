@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card blue">
                    <div class="kpi-label">Total Vacunaciones</div>
                    <div class="kpi-value">{{ $totalVacunaciones }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card teal">
                    <div class="kpi-label">Desparasitaciones</div>
                    <div class="kpi-value">{{ $totalDesparasitaciones }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card orange">
                    <div class="kpi-label">Proximas 7 dias</div>
                    <div class="kpi-value">{{ $proximasSiete }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Costo Total</div>
                    <div class="kpi-value" style="font-size:12px;">${{ number_format($costoTotal, 0, ',', '.') }}</div>
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
    <tbody>
        @foreach($proximasDosis as $d)
        @php $dias = now()->diffInDays($d->proxima_aplicacion, false); @endphp
        <tr @if($dias <= 7) class="highlight-row" @endif>
            <td>{{ $d->animal->nombre ?? $d->animal->codigo ?? '—' }}</td>
            <td>{{ $d->finca->nombre ?? '—' }}</td>
            <td>{{ $d->nombre_producto }}</td>
            <td>
                <span class="badge {{ $d->tipo === 'vacunacion' ? 'badge-blue' : 'badge-teal' }}">
                    {{ $d->tipo === 'vacunacion' ? 'Vacuna' : 'Desparasitacion' }}
                </span>
            </td>
            <td>{{ $d->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $d->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td>
                @if($dias <= 0)
                    <span class="badge badge-red">Vencida</span>
                @elseif($dias <= 7)
                    <span class="badge badge-yellow">{{ $dias }} dias</span>
                @else
                    <span class="badge badge-green">{{ $dias }} dias</span>
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
    <tbody>
        @foreach($registros as $r)
        <tr>
            <td style="font-family: monospace; font-size:9px;">{{ $r->codigo }}</td>
            <td>{{ $r->animal->nombre ?? $r->animal->codigo ?? '—' }}</td>
            <td>
                <span class="badge {{ $r->tipo === 'vacunacion' ? 'badge-blue' : 'badge-teal' }}">
                    {{ $tiposLabel[$r->tipo] ?? ucfirst($r->tipo) }}
                </span>
            </td>
            <td>{{ $r->nombre_producto }}</td>
            <td style="font-size:9px; color:#6b7280;">{{ $r->lote_medicamento ?? '—' }}</td>
            <td>{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $r->proxima_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $r->costo ? '$' . number_format($r->costo, 0, ',', '.') : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="7">COSTO TOTAL</td>
            <td>${{ number_format($costoTotal, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay registros para los filtros seleccionados.</div>
@endif

@endsection