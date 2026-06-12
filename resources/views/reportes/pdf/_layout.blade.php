<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $titulo }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #ffffff;
        }

        /* ── HEADER ─────────────────────────────────────────────── */
        .header {
            background-color: #b91c1c;
            color: #ffffff;
            padding: 18px 24px 14px 24px;
            margin-bottom: 0;
        }

        .header-top {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-brand {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 1px;
        }

        .header-sub {
            font-size: 10px;
            color: #fecaca;
            margin-top: 2px;
        }

        .header-title {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .header-meta {
            font-size: 10px;
            color: #fecaca;
        }

        .header-meta span {
            margin-right: 16px;
        }

        /* Línea decorativa */
        .divider {
            height: 3px;
            background-color: #991b1b;
            margin-bottom: 20px;
        }

        /* ── KPI CARDS ───────────────────────────────────────────── */
        .kpi-row {
            width: 100%;
            margin-bottom: 18px;
        }

        .kpi-row table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px 0;
        }

        .kpi-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #b91c1c;
            padding: 10px 12px;
            text-align: center;
        }

        .kpi-label {
            font-size: 9px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .kpi-value {
            font-size: 18px;
            font-weight: bold;
            color: #b91c1c;
        }

        .kpi-card.green  { border-left-color: #15803d; }
        .kpi-card.green .kpi-value { color: #15803d; }

        .kpi-card.blue   { border-left-color: #1d4ed8; }
        .kpi-card.blue .kpi-value { color: #1d4ed8; }

        .kpi-card.purple { border-left-color: #7e22ce; }
        .kpi-card.purple .kpi-value { color: #7e22ce; }

        .kpi-card.orange { border-left-color: #c2410c; }
        .kpi-card.orange .kpi-value { color: #c2410c; }

        /* ── SECCIONES ───────────────────────────────────────────── */
        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1f2937;
            background-color: #f3f4f6;
            border-left: 4px solid #b91c1c;
            padding: 6px 10px;
            margin-bottom: 8px;
            margin-top: 16px;
        }

        /* ── TABLAS ──────────────────────────────────────────────── */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 10px;
        }

        .data-table thead tr {
            background-color: #b91c1c;
            color: #ffffff;
        }

        .data-table thead th {
            padding: 7px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        .data-table tbody tr {
            border-bottom: 1px solid #e5e7eb;
        }

        .data-table tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        .data-table tbody tr.carencia-row {
            background-color: #fefce8;
        }

        .data-table tbody td {
            padding: 6px 8px;
            color: #374151;
            vertical-align: middle;
        }

        .data-table tfoot tr {
            background-color: #1f2937;
            color: #ffffff;
            font-weight: bold;
        }

        .data-table tfoot td {
            padding: 7px 8px;
            color: #ffffff;
        }

        /* ── BADGE ───────────────────────────────────────────────── */
        .badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .badge-green  { background-color: #d1fae5; color: #065f46; }
        .badge-red    { background-color: #fee2e2; color: #991b1b; }
        .badge-blue   { background-color: #dbeafe; color: #1e40af; }
        .badge-yellow { background-color: #fef9c3; color: #92400e; }
        .badge-gray   { background-color: #f3f4f6; color: #374151; }
        .badge-purple { background-color: #ede9fe; color: #5b21b6; }
        .badge-orange { background-color: #ffedd5; color: #9a3412; }

        /* ── OBSERVACIONES ───────────────────────────────────────── */
        .obs-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 10px 12px;
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
            margin-bottom: 16px;
        }

        /* ── BARRAS PROPORCIONALES ───────────────────────────────── */
        .bar-wrap {
            background-color: #e5e7eb;
            height: 8px;
            border-radius: 4px;
            width: 100%;
        }

        .bar-fill-green {
            background-color: #15803d;
            height: 8px;
            border-radius: 4px;
        }

        .bar-fill-red {
            background-color: #b91c1c;
            height: 8px;
            border-radius: 4px;
        }

        /* ── BALANCE ─────────────────────────────────────────────── */
        .balance-positivo { color: #15803d; font-weight: bold; }
        .balance-negativo { color: #b91c1c; font-weight: bold; }

        /* ── FOOTER ──────────────────────────────────────────────── */
        /*
         * DomPDF requiere position:fixed en el footer para que aparezca
         * en todas las páginas. Los contadores de página se inyectan con
         * las variables de script {PAGE_NUM} y {PAGE_COUNT}.
         */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 2px solid #b91c1c;
            padding: 6px 24px;
            font-size: 9px;
            color: #6b7280;
            background: #ffffff;
        }

        .footer-left  { float: left; }
        .footer-right { float: right; }
        .footer-clear { clear: both; }
    </style>
</head>
<body>

    {{-- Footer fijo en todas las páginas --}}
    <div class="footer">
        <span class="footer-left">Generado el {{ $generadoEl }} &mdash; BoviSoft Sistema Ganadero</span>
        <span class="footer-right">Pagina <span class="page">{PAGE_NUM}</span> de <span class="page">{PAGE_COUNT}</span></span>
        <div class="footer-clear"></div>
    </div>

    {{-- Header --}}
    <div class="header">
        <table class="header-top" cellpadding="0" cellspacing="0">
            <tr>
                <td>
                    <div class="header-brand">BoviSoft</div>
                    <div class="header-sub">Sistema de Gestion Ganadera</div>
                </td>
                <td style="text-align: right; vertical-align: top;">
                    <div class="header-title">{{ $titulo }}</div>
                    <div class="header-meta">
                        <span><strong>Finca:</strong> {{ $fincaNombre }}</span>
                        <span><strong>Periodo:</strong> {{ $periodo }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>
    <div class="divider"></div>

    {{-- Contenido del reporte --}}
    @yield('contenido')

</body>
</html>