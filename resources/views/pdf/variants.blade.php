<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Recepción de Mercadería</title>
    <style>
        @page {
            margin: 110px 40px 60px 40px;
        }

        body {
            font-family: 'DejaVu Serif', 'Times New Roman', 'Georgia', serif;
            font-size: 10px;
            color: #000;
            line-height: 1.45;
        }

        /* ============================================================
           ENCABEZADO FIJO
           ============================================================ */
        header {
            position: fixed;
            top: -80px;
            left: 0;
            right: 0;
            height: 80px;
            border-bottom: 1.5px solid #000;
            padding-bottom: 8px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            padding: 0;
            vertical-align: middle;
        }

        .brand {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: #000;
        }

        .brand-sub {
            font-size: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #000;
            margin-top: 2px;
        }

        .header-doc {
            text-align: right;
            font-size: 9px;
            color: #000;
        }

        .header-doc .doc-title {
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .header-doc .doc-meta {
            margin-top: 3px;
            font-size: 8.5px;
        }

        /* ============================================================
           PIE DE PÁGINA FIJO
           ============================================================ */
        footer {
            position: fixed;
            bottom: -40px;
            left: 0;
            right: 0;
            height: 30px;
            border-top: 0.75px solid #000;
            padding-top: 5px;
            font-size: 8px;
            color: #000;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .page-number:after {
            content: counter(page);
        }

        /* ============================================================
           TÍTULO
           ============================================================ */
        .report-title {
            text-align: center;
            margin: 0 0 20px 0;
        }

        .report-title h1 {
            font-size: 14px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin: 0;
            padding-bottom: 6px;
            border-bottom: 1px solid #000;
            display: inline-block;
        }

        .report-title .subtitle {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 6px;
        }

        /* ============================================================
           INFORMACIÓN GENERAL
           ============================================================ */
        .info-section {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 18px;
        }

        .info-section td {
            border: 1px solid #000;
            padding: 6px 8px;
            font-size: 9px;
            color: #000;
            vertical-align: top;
        }

        .info-label {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 110px;
        }

        /* ============================================================
           TABLA PRINCIPAL
           ============================================================ */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .data-table thead th {
            border: 1px solid #000;
            padding: 8px 5px;
            text-align: left;
            font-size: 8.5px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            background-color: #e8e8e8;
        }

        .data-table tbody td {
            border: 1px solid #000;
            padding: 7px 5px;
            font-size: 9.5px;
            color: #000;
            vertical-align: middle;
            background-color: #ffffff;
        }

        /* ===== FILAS ALTERNAS (STRIPED) ===== */
        .data-table tbody tr:nth-child(even) td {
            background-color: #f2f2f2;
        }

        /* ============================================================
           COLUMNAS
           ============================================================ */
        .col-qty      { width: 48px;  text-align: center; }
        .col-received { width: 70px;  text-align: center; }
        .col-variant  { width: 55px;  text-align: center; }
        .col-image    { width: 52px;  text-align: center; }
        .col-desc     { text-align: left; }
        .col-barcode  { width: 90px;  text-align: left; }
        .col-price    { width: 60px;  text-align: right; }

        .img-cell img {
            width: 36px;
            height: 36px;
            border: 0.75px solid #000;
        }

        /* Celda de recepción: fondo transparente para heredar el striped */
        .received-cell {
            height: 24px;
            background-color: transparent;
        }

        /* ============================================================
           TOTALES
           ============================================================ */
        .data-table tfoot td {
            border: 1px solid #000;
            padding: 9px 5px;
            font-size: 10px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #000;
            background-color: #e8e8e8;
        }

        .data-table tfoot td.total-value {
            text-align: right;
            font-size: 11px;
        }

        /* ============================================================
           RESUMEN
           ============================================================ */
        .summary {
            margin-top: 22px;
            width: 65%;
            border-collapse: collapse;
        }

        .summary td {
            border: 1px solid #000;
            padding: 8px 10px;
            font-size: 10px;
            color: #000;
        }

        .summary .label {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 180px;
        }

        .summary .value {
            text-align: right;
        }

        /* Fila especial para TOTAL RECIBIDOS con espacio para escribir */
        .summary .received-row td {
            height: 28px;
            background-color: #ffffff;
        }

        .summary .received-row .label {
            font-weight: bold;
        }

        /* ============================================================
           OBSERVACIONES
           ============================================================ */
        .notes {
            margin-top: 22px;
            border: 1px solid #000;
            padding: 10px;
            font-size: 9.5px;
            color: #000;
            min-height: 60px;
        }

        .notes .notes-title {
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
            border-bottom: 0.5px solid #000;
            padding-bottom: 3px;
        }

        /* ============================================================
           FIRMAS
           ============================================================ */
        .signature {
            margin-top: 40px;
            width: 100%;
            border-collapse: collapse;
        }

        .signature td {
            border: none;
            text-align: center;
            font-size: 9px;
            color: #000;
            padding-top: 35px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            width: 33.33%;
        }

        .signature .line {
            border-top: 0.75px solid #000;
            width: 180px;
            margin: 0 auto;
            padding-top: 4px;
        }

        .signature .sub {
            font-size: 8px;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }

        /* ============================================================
           IMPRESIÓN: forzar fondos de filas alternas
           ============================================================ */
        @media print {
            .data-table thead th,
            .data-table tfoot td {
                background-color: #e8e8e8 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .data-table tbody tr:nth-child(even) td {
                background-color: #f2f2f2 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .data-table tbody tr:nth-child(odd) td {
                background-color: #ffffff !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>

    {{-- ================= ENCABEZADO FIJO ================= --}}
    <header>
        <table class="header-table">
            <tr>
                <td>
                    <div class="brand">Sorelle Clothing Perú</div>
                    <div class="brand-sub">Sistema de Gestión de Inventario</div>
                </td>
                <td class="header-doc">
                    <div class="doc-title">Recepción de Mercadería</div>
                    <div class="doc-meta">
                        Emitido: {{ now()->format('d/m/Y H:i') }}<br>
                        Documento N.° {{ str_pad($manufacture->id ?? '0', 6, '0', STR_PAD_LEFT) }}
                    </div>
                </td>
            </tr>
        </table>
    </header>

    {{-- ================= PIE DE PÁGINA FIJO ================= --}}
    <footer>
        Documento generado automáticamente &mdash; Página <span class="page-number"></span>
    </footer>

    {{-- ================= TÍTULO ================= --}}
    <div class="report-title" style="margin: 10px 0 0 0">
        <h1>Acta de Recepción de Mercadería</h1>
        <div class="subtitle">Conformidad de entrega del proveedor</div>
    </div>

    {{-- ================= INFORMACIÓN GENERAL ================= --}}
    <table class="info-section">
        <tr>
            <td class="info-label">Proveedor</td>
            <td>{{ $manufacture->supplier->user->name ?? '—' }}</td>
            <td class="info-label">Orden de compra</td>
            <td>{{ str_pad($manufacture->id ?? '0', 6, '0', STR_PAD_LEFT) }}</td>
        </tr>
        <tr>
            <td class="info-label">Almacén</td>
            <td>{{ $warehouse ?? 'Almacén principal' }}</td>
            <td class="info-label">Guía de remisión</td>
            <td>{{ $remission_guide ?? '—' }}</td>
        </tr>
        <tr>
            <td class="info-label">Registros</td>
            <td>{{ count($variants) }}</td>
            <td class="info-label">Moneda</td>
            <td>PEN (S/)</td>
        </tr>
    </table>

    {{-- ================= TABLA PRINCIPAL ================= --}}
    <table class="data-table">
        <thead>
            <tr>
                <th class="col-qty">Cant.</th>
                <th class="col-received">Recepción</th>
                {{-- <th class="col-variant">Var.</th> --}}
                <th class="col-image" style="text-align: center;">Imagen</th>
                <th class="col-desc">Descripción</th>
                <th class="col-barcode">Código Barras</th>
                <th class="col-price" style="text-align: center;">Precio</th>
            </tr>
        </thead>
        <tbody>
            @php
                $totalQuantity = 0;
                $totalPrice    = 0;
            @endphp

            @forelse($variants as $item)
                @php
                    $variant = $item['variant'] ?? null;
                    $product = $variant['product'] ?? null;

                    $quantity = $item['quantity'] ?? 0;
                    $price    = (float) ($item['price'] ?? 0);

                    $productName = $product['name'] ?? 'Sin nombre';
                    $sku         = $variant['sku'] ?? '-';

                    $size = '-';
                    if (!empty($variant['option_values'])) {
                        foreach ($variant['option_values'] as $opt) {
                            $size = $opt['value'];
                        }
                    }

                    $imageUrl = $product['image']['thumbnail']
                             ?? $product['image']['medium']
                             ?? null;

                    $totalQuantity += $quantity;
                    $totalPrice    += ($quantity * $price);
                @endphp
                <tr>
                    <td class="col-qty">{{ $quantity }}</td>
                    <td class="col-received received-cell"></td>
                    {{-- <td class="col-variant">{{ $size }}</td> --}}
                    <td class="col-image img-cell">
                        @if($imageUrl)
                            <img src="{{ $imageUrl }}" alt="Img">
                        @else
                            <span style="font-size: 8px;">N/A</span>
                        @endif
                    </td>
                    <td class="col-desc">{{ $productName }}</td>
                    <td class="col-barcode">{{ $sku }}</td>
                    <td class="col-price">{{ number_format($price, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; padding: 15px;">
                        No se encontraron registros.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td class="col-qty">{{ $totalQuantity }}</td>
                <td colspan="4">Total general</td>
                <td class="col-price total-value">{{ number_format($totalPrice, 2) }}</td>
            </tr>
        </tfoot>
    </table>

    {{-- ================= RESUMEN ================= --}}
    <table class="summary">
        <tr>
            <td class="label">Total de unidades</td>
            <td class="value">{{ $totalQuantity }}</td>
        </tr>
        <tr class="received-row">
            <td class="label">Total recibidos</td>
            <td class="value"></td>
        </tr>
        {{-- <tr>
            <td class="label">Monto total</td>
            <td class="value">S/ {{ number_format($totalPrice, 2) }}</td>
        </tr> --}}
    </table>

    {{-- ================= OBSERVACIONES ================= --}}
    <div class="notes">
        <div class="notes-title">Observaciones</div>
        &nbsp;
    </div>

    {{-- ================= FIRMAS ================= --}}
    <table class="signature">
        <tr>
            <td>
                <div class="line">Entregado por</div>
                <div style="text-align: center"><strong>{{ $manufacture->supplier->user->name }}</strong></div>
                <div class="sub">Proveedor</div>
            </td>
            <td>
                <div class="line">Recibido por</div>
                <div class="sub">Almacén</div>
            </td>
            <td>
                <div class="line">Aprobado por</div>
                <div class="sub">Administración</div>
            </td>
        </tr>
    </table>

</body>
</html>