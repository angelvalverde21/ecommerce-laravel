<?php

namespace App\Http\Controllers\Api\public;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Yape;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class YapePublicController extends Controller
{
    //
    public function store(Store $store,Request $request)
    {
        // // 🔐 seguridad
        // if ($request->header('X-API-KEY') !== env('YAPE_API_KEY')) {
        //     return response()->json(['error' => 'No autorizado'], 401);
        // }

        Log::info($request);

        // 🧹 limpiar datos
        $nombre = $request->nombre;
        $monto = floatval($request->monto);

        //guardar (ejemplo)
        Log::info('YAPE:', [
            'nombre' => $nombre,
            'monto' => $monto
        ]);

        Yape::create([
            'amount' => $monto,
            'comment' => $request,
            'status' => 'paid',
            'date' => now(),
            'direction' => 'in',
            'store_id' => $store->id,	
        ]);
        // 👉 aquí puedes:
        // - crear venta
        // - marcar pedido pagado
        // - etc

        return response()->json(['ok' => true]);

    }
}
