@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card purple">
                    <div class="kpi-label">Tratamientos Activos</div>
                    <div class="kpi-value">{{ $totalActivos }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card green">
                    <div class="kpi-label">Completados</div>
                    <div class="kpi-value">{{ $totalCompletados }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card orange">
                    <div class="kpi-label">En Carencia</div>
                    <div class="kpi-value">{{ $enCarenciaCount }}</div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card">
                    <div class="kpi-label">Costo Total</div>
                    <div class="kpi-value" style="font-size:12px;">${{ number_format($costoTotal, 0, ',', '.') }}</div>
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
    <tbody>
        @foreach($activos as $t)
        <tr @if($t->en_carencia) class="highlight-row" @endif>
            <td style="font-family: monospace; font-size:9px;">{{ $t->codigo }}</td>
            <td>{{ $t->animal->nombre ?? $t->animal->codigo ?? '—' }}</td>
            <td>{{ $t->finca->nombre ?? '—' }}</td>
            <td>
                <strong>{{ $t->nombre_producto }}</strong>
                @if($t->diagnostico)
                    <br><span style="font-size:9px; color:#6b7280;">{{ $t->diagnostico }}</span>
                @endif
            </td>
            <td>{{ $t->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td>{{ $t->veterinario ?? '—' }}</td>
            <td>
                @if($t->en_carencia)
                    <span class="badge badge-yellow">{{ $t->dias_carencia_restantes }}d restantes</span>
                @elseif($t->dias_carencia > 0)
                    <span class="badge badge-green">Finalizada</span>
                @else
                    —
                @endif
            </td>
            <td>{{ $t->costo ? '$' . number_format($t->costo, 0, ',', '.') : '—' }}</td>
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
    <tbody>
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
            <td style="font-family: monospace; font-size:9px;">{{ $h->codigo }}</td>
            <td>{{ $h->animal->nombre ?? $h->animal->codigo ?? '—' }}</td>
            <td><span class="badge {{ $tipoBadge }}">{{ $tiposLabel[$h->tipo] ?? ucfirst($h->tipo) }}</span></td>
            <td>{{ $h->nombre_producto }}</td>
            <td style="font-size:9px;">{{ $h->diagnostico ?? '—' }}</td>
            <td>{{ $h->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td><span class="badge {{ $estadoBadge }}">{{ $estadosLabel[$h->estado] ?? $h->estado }}</span></td>
            <td>{{ $h->costo ? '$' . number_format($h->costo, 0, ',', '.') : '—' }}</td>
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
<div class="obs-box">No hay tratamientos registrados para los filtros seleccionados.</div>
@endif

@endsection