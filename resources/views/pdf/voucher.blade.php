<!DOCTYPE html>
<html lang="es">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
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

        .list-group{
            padding: 0 0 0 10px;
            /* border: 1px solid #ccc; */
        }

        h2{
            /* border: 1px solid #ccc; */
        }
    </style>
    <title>Ticket de envio</title>
</head>

<body>
    <div class="container">
        <h2 class="text-center" style="margin: 10px 0">
            Sorelle
            <small>{{ $order->name }}</small>
        </h2>
        <ul class="list-group">
            <li class="list-group-item"> Nombre: {{ Str::upper($order->shippingAddress->firstName . ' ' . $order->shippingAddress->lastName)}} </li>
            <li class="list-group-item"> DNI: {{ $order->shippingAddress->company  }} </li>
            <li class="list-group-item"> Telefono: {{ $order->shippingAddress->phone  }} </li>
            <li class="list-group-item" style="margin: 5px 0 0 0; letter-spacing: -1px; word-spacing: -2px;">{{ $order->shippingAddress->address1 }} </li>
            <li class="list-group-item" style="margin: 5px 0 0 0;"> <strong>{{ $order->shippingAddress->address2 }}</strong> </li>
        </ul>
        <h3 class="text-center" style="margin: 5px 0">
            {{  Str::upper($courier)  }}
        </h3>
        {{-- <p class="text-center">62516516566616</p> --}}
    </div>

</body>

</html>