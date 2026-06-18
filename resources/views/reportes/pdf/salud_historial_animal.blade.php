@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── Ficha del animal ── --}}
<div class="section-title">Datos del Animal</div>
<table class="data-table" cellpadding="0" cellspacing="0">
    <tbody>
        <tr>
            <td style="width:20%; font-weight:bold; background:#f3f4f6;">Codigo</td>
            <td style="font-family: monospace;">{{ $animal->codigo }}</td>
            <td style="width:20%; font-weight:bold; background:#f3f4f6;">Nombre</td>
            <td>{{ $animal->nombre ?? '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f3f4f6;">Tipo</td>
            <td>{{ ucfirst($animal->tipo) }}</td>
            <td style="font-weight:bold; background:#f3f4f6;">Raza</td>
            <td>{{ $animal->raza ?? '—' }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f3f4f6;">Finca</td>
            <td>{{ $animal->finca->nombre ?? '—' }}</td>
            <td style="font-weight:bold; background:#f3f4f6;">Estado</td>
            <td>{{ ucfirst($animal->estado) }}</td>
        </tr>
        <tr>
            <td style="font-weight:bold; background:#f3f4f6;">Peso actual</td>
            <td>{{ $animal->peso_actual ? number_format($animal->peso_actual, 1) . ' kg' : '—' }}</td>
            <td style="font-weight:bold; background:#f3f4f6;">Edad</td>
            <td>{{ $animal->edad ?? '—' }}</td>
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
                <div class="kpi-card purple">
                    <div class="kpi-label">Tratamientos</div>
                    <div class="kpi-value">{{ $tratamientos }}</div>
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
    <tbody>
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
            <td style="font-family: monospace; font-size:9px;">{{ $r->codigo }}</td>
            <td><span class="badge {{ $tipoBadge }}">{{ $tiposLabel[$r->tipo] ?? ucfirst($r->tipo) }}</span></td>
            <td>
                <strong>{{ $r->nombre_producto }}</strong>
                @if($r->diagnostico)
                    <br><span style="font-size:9px; color:#6b7280;">{{ $r->diagnostico }}</span>
                @endif
            </td>
            <td>{{ $r->fecha_aplicacion?->format('d/m/Y') ?? '—' }}</td>
            <td style="font-size:9px;">{{ $r->veterinario ?? '—' }}</td>
            <td style="font-size:9px;">{{ $r->dosis ? $r->dosis . ' ' . $r->unidad_dosis : '—' }}</td>
            <td>
                @if($r->dias_carencia > 0)
                    {{ $r->dias_carencia }}d
                    @if($r->en_carencia)
                        <span class="badge badge-yellow">Activa</span>
                    @endif
                @else
                    —
                @endif
            </td>
            <td><span class="badge {{ $estadoBadge }}">{{ $estadosLabel[$r->estado] ?? $r->estado }}</span></td>
            <td>{{ $r->costo ? '$' . number_format($r->costo, 0, ',', '.') : '—' }}</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="8">COSTO TOTAL HISTORICO</td>
            <td>${{ number_format($costoTotal, 0, ',', '.') }}</td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay registros sanitarios para este animal.</div>
@endif

{{-- ── Observaciones del animal ── --}}
@if($animal->observaciones)
<div class="section-title">Observaciones Generales del Animal</div>
<div class="obs-box">{{ $animal->observaciones }}</div>
@endif

@endsection