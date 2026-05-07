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

            <div class="label" style="position: relative; left: 5px;">
                <ul>
                    <li style="font-size: 8px">{{ Str::upper($variant->product->name) }}</li>
                </ul>

                @php
                    //Este archivo lo llama
                    //app\Http\Controllers\Api\Dashboard\BarcodeDashboardController.php
                @endphp

                <div
                    style="
                        width: 26mm;
                        height: 8mm;
                        padding: 1mm 0 1mm 2mm;
                        box-sizing: border-box;
                        text-align: center;
                    ">

                    <img src="data:image/png;base64,{{ \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG(strtoupper((string) $variant['id']), 'C39', 2, 40) }}"
                        style="
                            width: 100%;
                            height: 6mm;
                            object-fit: contain;
                        ">

                    <div style="font-size: 8px; margin-top: 1mm;">
                        <div>{{ $variant['id'] }}-{{ Str::upper($variant->optionValues[0]->value) }}</div>
                    </div>

                </div>

                {{-- <ul>
                    <li>{{ strtoupper($variant['id']) }}-{{ $variant['sku'] }}</li>
                </ul> --}}
            </div>

            @if ($current < $total)
                <div class="page-break"></div>
            @endif
        @endfor
    @endforeach

</body>

</html>
