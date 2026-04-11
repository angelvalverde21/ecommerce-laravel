<!DOCTYPE html>
<html lang="en">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @page {
            margin: 0mm;
        }

        .label {
            width: 100%;
            height: 100%;
        }

        .page-break {
            page-break-after: always;
        }

        ul {
            margin: 0;
            padding: 0;
            list-style: none;
            text-align: center;
        }

        ul li {
            margin: 0;
            padding: 0;
            font-size: 9px;
            margin: 5px 0 0 0;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }
    </style>
</head>

<body>

    @php
        $total = collect($variants)->sum('quantity');
        $current = 0;
    @endphp

    @foreach ($variants as $variant)
        @for ($i = 0; $i < $variant['quantity']; $i++)
            @php $current++; @endphp

            <div class="label">
                <ul>
                    <li>{{ Str::upper($variant->product->name) }}</li>
                </ul>

                @php 
                //Este archivo lo llama
                //app\Http\Controllers\Api\Dashboard\BarcodeDashboardController.php 
                @endphp

                <div style="text-align:center; margin-top:5px; margin-left:5px; margin-right:5px;">
                    <img src="data:image/png;base64,{{ \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG(
                        $variant['id'] . '-' . $variant['sku'],
                        'C128',
                        0.70, // 👈 grosor más pequeño
                        24, // 👈 altura más pequeña
                    ) }}"
                        style="display:block; margin: 0 auto;">
                </div>

                <ul>
                    <li>SKU: {{ $variant['id'] }}-{{ $variant['sku'] }}</li>
                </ul>
            </div>

            @if ($current < $total)
                <div class="page-break"></div>
            @endif
        @endfor
    @endforeach

</body>

</html>
