@extends('reportes.pdf._layout')

@section('contenido')

{{-- ── KPIs ── --}}
<div class="kpi-row">
    <table cellpadding="0" cellspacing="0">
        <tr>
            <td width="25%">
                <div class="kpi-card">
                    <div class="kpi-label">Total Animales</div>
                    <div class="kpi-value"><span data-report-stat="totalAnimales">{{ $totalAnimales }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card green">
                    <div class="kpi-label">Vacas Activas</div>
                    <div class="kpi-value"><span data-report-stat="vacasActivas">{{ $vacasActivas }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card blue">
                    <div class="kpi-label">Vendidos</div>
                    <div class="kpi-value"><span data-report-stat="vendidos">{{ $vendidos }}</span></div>
                </div>
            </td>
            <td width="25%">
                <div class="kpi-card orange">
                    <div class="kpi-label">En Carencia</div>
                    <div class="kpi-value"><span data-report-stat="enCarencia">{{ $enCarencia }}</span></div>
                </div>
            </td>
        </tr>
    </table>
</div>

{{-- ── Tabla de animales ── --}}
<div class="section-title">Listado de Animales</div>

@if($animales->count() > 0)
<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Tipo</th>
            <th>Raza</th>
            <th>Edad</th>
            <th>Peso (kg)</th>
            <th>Finca</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody data-report-collection="animales">
        @foreach($animales as $animal)
        @php
            $estadoBadge = match($animal->estado) {
                'activo'  => 'badge-green',
                'vendido' => 'badge-blue',
                'muerto'  => 'badge-gray',
                default   => 'badge-gray',
            };
            $tipoBadge = match($animal->tipo) {
                'vaca'    => 'badge-red',
                'toro'    => 'badge-blue',
                'ternero' => 'badge-green',
                'novilla' => 'badge-purple',
                default   => 'badge-gray',
            };
        @endphp
        <tr>
            <td style="font-family: monospace;"><span data-offline-owner="animales" data-offline-field="codigo">{{ $animal->codigo }}</span></td>
            <td><span data-offline-owner="animales" data-offline-field="nombre">{{ $animal->nombre ?? '—' }}</span></td>
            <td><span class="badge {{ $tipoBadge }}"><span data-offline-owner="animales" data-offline-field="tipo">{{ ucfirst($animal->tipo) }}</span></span></td>
            <td><span data-offline-owner="animales" data-offline-field="raza">{{ $animal->raza ?? '—' }}</span></td>
            <td><span data-offline-owner="animales" data-offline-field="edad">{{ $animal->edad ?? '—' }}</span></td>
            <td><span data-offline-owner="animales" data-offline-field="peso_actual">{{ $animal->peso_actual ? number_format($animal->peso_actual, 1) : '—' }}</span></td>
            <td><span data-offline-owner="animales" data-offline-field="finca_nombre">{{ $animal->finca->nombre ?? '—' }}</span></td>
            <td><span class="badge {{ $estadoBadge }}"><span data-offline-owner="animales" data-offline-field="estado">{{ ucfirst($animal->estado) }}</span></span></td>
        </tr>
        @endforeach
    </tbody>
</table>
@else
<div class="obs-box">No hay animales registrados para los filtros seleccionados.</div>
@endif

{{-- ── Resumen por tipo ── --}}
<div class="section-title">Resumen por Tipo de Animal</div>

<table class="data-table" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
            <th>Tipo</th>
            <th>Cantidad</th>
            <th>% del total</th>
        </tr>
    </thead>
    <tbody data-report-collection="conteoTipos">
        @php
            $tiposLabel = ['vaca' => 'Vaca', 'toro' => 'Toro', 'ternero' => 'Ternero', 'novilla' => 'Novilla'];
        @endphp
        @foreach($conteoTipos as $tipo => $cantidad)
        <tr>
            <td>{{ $tiposLabel[$tipo] ?? ucfirst($tipo) }}</td>
            <td>{{ $cantidad }}</td>
            <td><span data-report-stat="totalAnimales">{{ $totalAnimales > 0 ? number_format(($cantidad / $totalAnimales) * 100, 1) : 0 }}</span>%</td>
        </tr>
        @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td><span data-report-stat="totalAnimales">{{ $totalAnimales }}</span></td>
            <td>100%</td>
        </tr>
    </tfoot>
</table>

{{-- ── Observaciones ── --}}
<div class="section-title">Observaciones del Periodo</div>
<div class="obs-box">
    @if($totalAnimales > 0)
        Peso promedio del hato: <strong><span data-report-stat="pesoPromedio">{{ $pesoPromedio ? number_format($pesoPromedio, 1) . ' kg' : 'No registrado' }}</span></strong>.
        Total de animales en el inventario: <strong><span data-report-stat="totalAnimales">{{ $totalAnimales }}</span></strong>,
        de los cuales <strong><span data-report-stat="vendidos">{{ $vendidos }}</span></strong> han sido vendidos
        y <strong><span data-report-stat="enCarencia">{{ $enCarencia }}</span></strong> se encuentran actualmente en periodo de carencia sanitaria.
    @else
        No se encontraron animales para el periodo y filtros seleccionados.
    @endif
</div>

@endsection