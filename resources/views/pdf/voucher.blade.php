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

    function extractPhoneWithoutArea($input = ""): ?string
    {
        if ($input === null || $input === "") {
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
            font-size: 9pt;
            width: 75mm;
            box-sizing: border-box;
            margin: 0;
            padding: 0 5px;
        }

        .text-center {
            text-align: center;
        }

        /* .bold { font-weight: bold; } */
        /* hr { border: 0; border-top: 1px dashed #000; } */

        .container {
            width: 75mm;
            /* border: 1px solid #ccc; */
            margin: 0;
        }

        ul {
            margin: 0;
            padding: 0;
        }

        li {
            list-style: none;
        }

        .list-group {
            padding: 0 0 0 10px;
            /* border: 1px solid #ccc; */
        }

        h2 {
            /* border: 1px solid #ccc; */
        }

        .flex {
            margin: 10px 0;
            display: flex;
            justify-content: center;
            align-items: center;
            border: 1px solid #ccc;
            text-align: center;
            flew
        }
    </style>
    <title>Ticket de envio.</title>
</head>

<body>
    <div class="container">
        <div style="margin: 10px 0; text-align: center;">
            <img src="https://sorelleclothingperu.com/cdn/shop/files/Logo_2024.png" alt="" height="20">
        </div>
        <ul class="list-group">
            <li class="list-group-item">Pedido: <strong>{{ $order->name }}</strong></li>
            <li class="list-group-item"> Nombre:
                {{ titleCaseName($order->shippingAddress->firstName . ' ' . $order->shippingAddress->lastName) }} </li>
            <li class="list-group-item"> DNI: {{ $order->shippingAddress->company }} </li>
            <li class="list-group-item"> Telefono: {{ extractPhoneWithoutArea($order->shippingAddress?->phone) }} </li>
            <li class="list-group-item" style="margin: 2px 0 0 0">
                Direccion: {{ normalize_text($order->shippingAddress->address1) }} </li>
            <li class="list-group-item" style="margin: 2px 0 0 0;">
                {{ normalize_text($order->shippingAddress->address2) }}, {{ titleCaseName($order->shippingAddress->city) }}
            </li>
            <li class="list-group-item" style="margin: 2px 0 0 0;">
               Registro: 
            </li>
        </ul>
        <h3 class="text-center"
            style="
                margin: 2px auto;
                position: absolute;
                bottom: 5px;
                left: 0;
                right: 0;
                text-align: center;
            ">
            {{ Str::upper($courier) }}
        </h3>
        {{-- <p class="text-center">62516516566616</p> --}}
    </div>

</body>

</html>
