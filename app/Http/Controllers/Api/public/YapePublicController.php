<?php

namespace App\Http\Controllers\Api\public;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YapePublicController extends Controller
{
    //
    public function store(Request $request)
    {
        // // 🔐 seguridad
        // if ($request->header('X-API-KEY') !== env('YAPE_API_KEY')) {
        //     return response()->json(['error' => 'No autorizado'], 401);
        // }

        // 🧹 limpiar datos
        $nombre = $request->nombre;
        $monto = floatval($request->monto);

        // 🧠 guardar (ejemplo)
        Log::info('YAPE:', [
            'nombre' => $nombre,
            'monto' => $monto
        ]);

        // 👉 aquí puedes:
        // - crear venta
        // - marcar pedido pagado
        // - etc

        return response()->json(['ok' => true]);
    }
}
