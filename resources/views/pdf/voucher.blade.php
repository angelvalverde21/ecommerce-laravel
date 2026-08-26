@php
    function normalize_text($text)
    {
        $replacements = [
            'á' => 'a',
            'Á' => 'A',
            'é' => 'e',
            'É' => 'E',
            'í' => 'i',
            'Í' => 'I',
            'ó' => 'o',
            'Ó' => 'O',
            'ú' => 'u',
            'Ú' => 'U',
            'ñ' => 'n',
            'Ñ' => 'N',
        ];

        // Reemplazar tildes
        $text = strtr($text, $replacements);

        // Pasar a minúsculas
        $text = strtolower($text);

        // Primera letra en mayúscula
        return ucfirst($text);
    }
    function titleCaseName($text)
    {
        // Pasar todo a minúsculas primero
        $text = strtolower($text);

        // Convertir cada palabra a mayúscula inicial
        return ucwords($text);
    }

    function extractPhoneWithoutArea($input = ''): ?string
    {
        if ($input === null || $input === '') {
            return '';
        }

        // 1. Extraer solo dígitos
        $digits = preg_replace('/\D+/', '', $input);

        // 2. Si empieza con 51, lo eliminamos (código de Perú)
        if (str_starts_with($digits, '51')) {
            $digits = substr($digits, 2);
        }

        // 3. Limpiar ceros adicionales por si los hubiera
        $digits = ltrim($digits, '0');

        // 4. Validar número móvil peruano (9 dígitos, empieza en 9)
        if (preg_match('/^9\d{8}$/', $digits)) {
            return $digits;
        }

        return null; // No es un número móvil válido
    }
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        @page {
            margin: 0mm;
        }

        body {
            font-family: Arial, sans-serif;
            /* font-family: monospace; */
            /* font-weight: 100; */
            letter-spacing: -0.35px;
            font-size: 0.9rem;
            width: 100mm;
            box-sizing: border-box;
            margin: 0;
            padding: 0px;
        }

        .text-center {
            text-align: center;
        }

        /* .bold { font-weight: bold; } */
        /* hr { border: 0; border-top: 1px dashed #000; } */

        .container {
            width: 100mm;
            /* border: 1px solid #ccc; */
            margin: 0;
        }

        ul {
            margin: 0 10px 10px 10px !important;
            padding: 0 10px !important;
            border: 0px solid #ccc;
        }

        li {
            list-style: none;
            padding: 2px 0
        }

        /* .list-group { */
            /* padding: 0 0 0 10px; */
            /* border: 1px solid #ccc; */
        /* } */

        h2 {
            /* border: 1px solid #ccc; */
        }

        .flex {
            margin: 10px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 0px solid #ccc;
            text-align: center;
            flew
        }
    </style>
    <title>Ticket de envio.</title>
</head>

<body>
    <div class="container">

        <div class="w-100 d-flex justify-content-between"
            style="margin: 10px 0; text-align: center; border-bottom: 1px solid #ccc;">
            <img src="https://sorelleclothingperu.com/cdn/shop/files/Logo_2024.png" alt="" height="50">
        </div>

        @if (Str::upper($order->shippingAddress->firstName) === 'SHOWROOM')
            <h1 style="text-align: center; margin: 80px 0">SHOWROOM</h1>
        @else

            <ul class="list-group">
                <li class="list-group-item">
                    {{ Str::upper(titleCaseName($order->shippingAddress->firstName . ' ' . $order->shippingAddress->lastName)) }}
                </li>
                <li class="list-group-item">DNI: {{ $order->shippingAddress->company }}</li>
                <li class="list-group-item">Teléfono: {{ extractPhoneWithoutArea($order->shippingAddress?->phone) }}
                </li>

            </ul>

            <ul class="list-group" style="border: 1px solid #000; padding: 10px !important; border-radius: 5px">

                <li class="list-group-item" style="margin: 2px 0 0 0">
                    Enviar a: {{ normalize_text($order->shippingAddress->address1) }}
                </li>

                <li class="list-group-item" style="margin: 2px 0 0 0;">
                    @if ($order->shippingAddress->address2)
                        {{ normalize_text($order->shippingAddress->address2) }},
                        {{ titleCaseName($order->shippingAddress->city) }}
                    @else
                        @if ($order->shippingAddress->city)
                            {{ titleCaseName($order->shippingAddress->city) }}
                        @endif
                    @endif

                </li>

            </ul>
        @endif

        <div
            style="
                margin: 2px 0;
                position: absolute;
                bottom: 10px;
                left: 0;
                right: 0;
            ">

            <div style="width: 100%; border: 0 px solid #ccc; margin: 0px 0; padding: 0px 0; text-align: center;">
                <img src="data:image/png;base64,{{ \Milon\Barcode\Facades\DNS1DFacade::getBarcodePNG(
                    preg_replace('/[^A-Z0-9]/', '', strtoupper((string) $order->name)),
                    'C39',
                    3,
                    45,
                ) }}"
                    style="display: block; width: 80%; object-fit: contain; border: 0px solid #ccc; margin: 0 auto;">
                <div style="font-size: 1rem; padding: 5px 0">{{ $order->name }}</div>
            </div>

            @if (Str::upper(str_replace(' ', '', $courier)) == 'OLVACOURIER')
                <div style="padding: 0 10px; font-size: 0.85rem">
                    REGISTRO:
                </div>
            @endif

            <h3 style="text-align: center;">
                --- {{ Str::upper($courier) }} ---
            </h3>
        </div>

        {{-- <p class="text-center">62516516566616</p> --}}
    </div>

</body>

</html>
