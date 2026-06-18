<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $titulo }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #1f2937;
            background: #ffffff;
        }

        /* ── HEADER ── */
        .header {
            background-color: #b91c1c;
            color: #ffffff;
            padding: 14px 24px 12px 24px;
            margin-bottom: 0;
        }

        .header-table { width: 100%; border-collapse: collapse; }

        .header-logo-cell { width: 85px; vertical-align: middle; padding-right: 0px;}

        .header-logo {
    width: 78px;
    height: 70px;
    background-color: transparent; /* o eliminar esta propiedad */
    border-radius: 8px;
    padding: 4px;
}

        .header-brand-cell { vertical-align: middle; }

        .header-brand-name {
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
            color: #ffffff;
        }

        .header-brand-sub {
            font-size: 9px;
            color: #fecaca;
            margin-top: 1px;
        }

        .header-info-cell { text-align: right; vertical-align: top; }

        .header-titulo {
            font-size: 15px;
            font-weight: bold;
            color: #ffffff;
            margin-bottom: 4px;
        }

        .header-meta {
            font-size: 9px;
            color: #fecaca;
            line-height: 1.6;
        }

        /* Linea decorativa */
        .divider { height: 3px; background-color: #991b1b; margin-bottom: 18px; }

        /* ── KPI CARDS ── */
        .kpi-row { width: 100%; margin-bottom: 16px; }
        .kpi-row table { width: 100%; border-collapse: separate; border-spacing: 6px 0; }

        .kpi-card {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-left: 4px solid #b91c1c;
            padding: 9px 10px;
            text-align: center;
        }
        .kpi-label { font-size: 9px; color: #6b7280; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px; }
        .kpi-value { font-size: 17px; font-weight: bold; color: #b91c1c; }

        .kpi-card.green  { border-left-color: #15803d; }
        .kpi-card.green .kpi-value  { color: #15803d; }
        .kpi-card.blue   { border-left-color: #1d4ed8; }
        .kpi-card.blue .kpi-value   { color: #1d4ed8; }
        .kpi-card.purple { border-left-color: #7e22ce; }
        .kpi-card.purple .kpi-value { color: #7e22ce; }
        .kpi-card.orange { border-left-color: #c2410c; }
        .kpi-card.orange .kpi-value { color: #c2410c; }
        .kpi-card.teal   { border-left-color: #0f766e; }
        .kpi-card.teal .kpi-value   { color: #0f766e; }

        /* ── SECCIONES ── */
        .section-title {
            font-size: 11px;
            font-weight: bold;
            color: #1f2937;
            background-color: #f3f4f6;
            border-left: 4px solid #b91c1c;
            padding: 6px 10px;
            margin-bottom: 7px;
            margin-top: 14px;
        }

        /* ── TABLAS ── */
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 14px; font-size: 10px; }

        .data-table thead tr { background-color: #b91c1c; color: #ffffff; }
        .data-table thead th {
            padding: 6px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .data-table tbody tr { border-bottom: 1px solid #e5e7eb; }
        .data-table tbody tr:nth-child(even) { background-color: #f9fafb; }
        .data-table tbody tr.highlight-row   { background-color: #fefce8; }
        .data-table tbody tr.danger-row      { background-color: #fff1f2; }
        .data-table tbody td { padding: 6px 8px; color: #374151; vertical-align: middle; }
        .data-table tfoot tr { background-color: #1f2937; color: #ffffff; font-weight: bold; }
        .data-table tfoot td { padding: 6px 8px; color: #ffffff; }

        /* ── BADGES ── */
        .badge { display: inline-block; padding: 2px 6px; border-radius: 3px; font-size: 9px; font-weight: bold; }
        .badge-green  { background-color: #d1fae5; color: #065f46; }
        .badge-red    { background-color: #fee2e2; color: #991b1b; }
        .badge-blue   { background-color: #dbeafe; color: #1e40af; }
        .badge-yellow { background-color: #fef9c3; color: #92400e; }
        .badge-gray   { background-color: #f3f4f6; color: #374151; }
        .badge-purple { background-color: #ede9fe; color: #5b21b6; }
        .badge-orange { background-color: #ffedd5; color: #9a3412; }
        .badge-teal   { background-color: #ccfbf1; color: #134e4a; }
        .badge-pink   { background-color: #fce7f3; color: #9d174d; }

        /* ── CAJAS DE TEXTO ── */
        .obs-box {
            background-color: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 4px;
            padding: 9px 12px;
            font-size: 10px;
            color: #6b7280;
            margin-top: 4px;
            margin-bottom: 14px;
        }

        .alert-box {
            border-radius: 4px;
            padding: 9px 12px;
            font-size: 10px;
            margin-bottom: 8px;
        }
        .alert-red    { background-color: #fee2e2; border-left: 4px solid #dc2626; color: #7f1d1d; }
        .alert-yellow { background-color: #fef9c3; border-left: 4px solid #ca8a04; color: #713f12; }

        /* ── BARRAS PROPORCIONALES ── */
        .bar-wrap { background-color: #e5e7eb; height: 8px; border-radius: 4px; width: 100%; }
        .bar-fill-green { background-color: #15803d; height: 8px; border-radius: 4px; }
        .bar-fill-red   { background-color: #b91c1c; height: 8px; border-radius: 4px; }

        /* ── BALANCE ── */
        .balance-positivo { color: #15803d; font-weight: bold; }
        .balance-negativo { color: #b91c1c; font-weight: bold; }

        /* ── LINEA SEPARADORA ── */
        .line-sep { border: none; border-top: 1px solid #e5e7eb; margin: 12px 0; }

        /* ── FOOTER ── */
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            border-top: 2px solid #b91c1c;
            padding: 5px 24px;
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

    {{-- Footer fijo en todas las paginas --}}
    <div class="footer">
        <span class="footer-left">
            BoviSoft &mdash; Sistema de Gestion Ganadera &mdash; Generado el {{ $generadoEl }}
        </span>
        <span class="footer-right">
            Pagina <span class="page">{PAGE_NUM}</span> de <span class="page">{PAGE_COUNT}</span>
        </span>
        <div class="footer-clear"></div>
    </div>

    {{-- Header con logo --}}
    <div class="header">
        <table class="header-table" cellpadding="0" cellspacing="0">
            <tr>
                
                {{-- Logo --}}
<td class="header-logo-cell">
    <img src="{{ str_replace('\\', '/', public_path('images/logowhite.png')) }}" alt="BoviSoft" class="header-logo" />
</td>


                {{-- Nombre de la app --}}
                <td class="header-brand-cell">
                    <div class="header-brand-name">BoviSoft</div>
                    <div class="header-brand-sub">Sistema de Gestion Ganadera</div>
                </td>

                {{-- Info del reporte --}}
                <td class="header-info-cell">
                    <div class="header-titulo">{{ $titulo }}</div>
                    <div class="header-meta">
                        <strong>Finca:</strong> {{ $fincaNombre }}<br>
                        <strong>Propietario:</strong> {{ $propietario }}<br>
                        <strong>Periodo:</strong> {{ $periodo }}<br>
                        <strong>Generado por:</strong> {{ $usuarioNombre }}
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