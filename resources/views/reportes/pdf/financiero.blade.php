@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="33%">
                <div class="kpi-card green">
                    <div class="kpi-label">Total Ingresos</div>
                    <div class="kpi-value" style="font-size:14px;">${{ number_format($totalIngresos, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="kpi-card">
                    <div class="kpi-label">Total Egresos</div>
                    <div class="kpi-value" style="font-size:14px;">${{ number_format($totalEgresos, 0, ',', '.') }}</div>
                </div>
            </td>
            <td width="33%">
                <div class="kpi-card {{ $balance >= 0 ? 'green' : '' }}">
                    <div class="kpi-label">Balance</div>
                    <div class="kpi-value {{ $balance >= 0 ? 'balance-positivo' : 'balance-negativo' }}" style="font-size:14px;">
                        {{ $balance >= 0 ? '+' : '' }}${{ number_format($balance, 0, ',', '.') }}
                    </div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Tabla Ingresos ── --}}
<div class="section-title">Detalle de Ingresos</div>

@php $tiposIngLabel = \App\Models\Ingreso::getTipos(); @endphp

@if($ingresos->count() > 0)
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Tipo</th>
            <th>Finca</th>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Barra proporcional</th>
        </tr>
    </thead>
    <tbody>
        @foreach($ingresos as $ing)
        @php $pct = $maxMonto > 0 ? min(100, ($ing->monto / $maxMonto) * 100) : 0; @endphp
        <tr>
            <td style="font-family: monospace; font-size:9px;">{{ $ing->codigo }}</td>
            <td><span class="badge badge-green">{{ $tiposIngLabel[$ing->tipo] ?? ucfirst($ing->tipo) }}</span></td>
            <td>{{ $ing->finca->nombre ?? '—' }}</td>
            <td>{{ $ing->fecha->format('d/m/Y') }}</td>
            <td style="font-weight:bold; color:#15803d;">${{ number_format($ing->monto, 0, ',', '.') }}</td>
            <td style="width:100px;">
                <div class="bar-wrap">
                    <div class="bar-fill-green" style="width:{{ number_format($pct, 1) }}%;"></div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL INGRESOS</td>
            <td>${{ number_format($totalIngresos, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay ingresos registrados para los filtros seleccionados.</div>
@endif

{{-- ── Tabla Egresos ── --}}
<div class="section-title">Detalle de Egresos</div>

@php $catsEgrLabel = \App\Models\Egreso::getCategorias(); @endphp

@if($egresos->count() > 0)
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Categoria</th>
            <th>Finca</th>
            <th>Fecha</th>
            <th>Monto</th>
            <th>Barra proporcional</th>
        </tr>
    </thead>
    <tbody>
        @foreach($egresos as $egr)
        @php $pct = $maxMonto > 0 ? min(100, ($egr->monto / $maxMonto) * 100) : 0; @endphp
        <tr>
            <td style="font-family: monospace; font-size:9px;">{{ $egr->codigo }}</td>
            <td><span class="badge badge-red">{{ $catsEgrLabel[$egr->categoria] ?? ucfirst($egr->categoria) }}</span></td>
            <td>{{ $egr->finca->nombre ?? '—' }}</td>
            <td>{{ $egr->fecha->format('d/m/Y') }}</td>
            <td style="font-weight:bold; color:#b91c1c;">${{ number_format($egr->monto, 0, ',', '.') }}</td>
            <td style="width:100px;">
                <div class="bar-wrap">
                    <div class="bar-fill-red" style="width:{{ number_format($pct, 1) }}%;"></div>
                </div>
            </td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="4">TOTAL EGRESOS</td>
            <td>${{ number_format($totalEgresos, 0, ',', '.') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@else
<div class="obs-box">No hay egresos registrados para los filtros seleccionados.</div>
@endif

{{-- ── Costos de salud cruzados ── --}}
@if($costosSalud > 0)
<div class="section-title">Costos de Salud Animal (cruzados desde modulo Salud)</div>
<div class="obs-box">
    En el periodo seleccionado se registraron costos sanitarios por un total de
    <strong>${{ number_format($costosSalud, 0, ',', '.') }} COP</strong>.
    Estos costos provienen del modulo de Salud y pueden haberse registrado tambien como egresos de categoria "Salud Animal".
</div>
@endif

{{-- ── Resumen final ── --}}
<div class="section-title">Resumen Financiero del Periodo</div>

<table class="data-table" cellpadding="0" cellspacing="0">
    <tbody>
        <tr>
            <td style="width:60%; font-weight:bold;">Total Ingresos del periodo</td>
            <td style="color:#15803d; font-weight:bold;">${{ number_format($totalIngresos, 0, ',', '.') }} COP</td>
        </tr>
        <tr>
            <td style="font-weight:bold;">Total Egresos del periodo</td>
            <td style="color:#b91c1c; font-weight:bold;">${{ number_format($totalEgresos, 0, ',', '.') }} COP</td>
        </tr>
        @if($costosSalud > 0)
        <tr>
            <td>Costos de salud (referencia)</td>
            <td style="color:#7e22ce;">${{ number_format($costosSalud, 0, ',', '.') }} COP</td>
        </tr>
        @endif
        <tr style="background-color: {{ $balance >= 0 ? '#d1fae5' : '#fee2e2' }};">
            <td style="font-weight:bold; font-size:12px;">BALANCE (Ingresos - Egresos)</td>
            <td class="{{ $balance >= 0 ? 'balance-positivo' : 'balance-negativo' }}" style="font-size:13px;">
                {{ $balance >= 0 ? '+' : '' }}${{ number_format($balance, 0, ',', '.') }} COP
            </td>
        </tr>
    </tbody>
</table>

@endsection