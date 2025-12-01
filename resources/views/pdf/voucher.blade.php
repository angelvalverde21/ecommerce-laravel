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
            font-size: 10pt;
            width: 75mm;
            /* Para impresora térmica de 58mm */
            margin: 0;
            padding: 0;
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
    <title>Ticket de envio</title>
</head>

<body>
    <div class="container">
        <div style="margin: 10px 0; text-align: center;">
            <table style="margin: 0 auto;">
                <tr>
                    <td>
                        <img src="https://sorelleclothingperu.com/cdn/shop/files/Logo_2024.png" alt=""
                            height="25">
                    </td>
                    <td style="padding-left: 6px; font-size: 14px; font-weight: bold; vertical-align: middle;">
                        {{ $order->name }}
                    </td>
                </tr>
            </table>
        </div>
        <ul class="list-group">
            <li class="list-group-item"> Nombre:
                {{ Str::upper($order->shippingAddress->firstName . ' ' . $order->shippingAddress->lastName) }} </li>
            <li class="list-group-item"> DNI: {{ $order->shippingAddress->company }} </li>
            <li class="list-group-item"> Telefono: {{ $order->shippingAddress->phone }} </li>
            <li class="list-group-item" style="margin: 5px 0 0 0">
                Direccion: {{ $order->shippingAddress->address1 }} </li>
            <li class="list-group-item" style="margin: 5px 0 0 0;">
                <strong>{{ $order->shippingAddress->address2 }}</strong>
            </li>
        </ul>
        <h3 class="text-center" style="margin: 5px 0">
            {{ Str::upper($courier) }}
        </h3>
        {{-- <p class="text-center">62516516566616</p> --}}
    </div>

</body>

</html>
