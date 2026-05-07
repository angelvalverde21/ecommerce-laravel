<?php

namespace App\Http\Controllers\Api\public;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Yape;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class YapePublicController extends Controller
{
    //
    public function store(Store $store, Request $request)
    {
        // 🔐 Validar API KEY
        // if ($request->header('X-API-KEY') !== config('app.yape_api_key')) {
        //     return response()->json(['error' => 'Unauthorized'], 401);
        // }

        $request->validate([
            'raw_text' => 'required|string',
            'timestamp' => 'required|numeric',
        ]);

        $raw = $request->raw_text;

        // 🔐 Generar hash único
        $hash = sha1($raw . $request->timestamp);

        // 🚫 Evitar duplicados
        if (Yape::where('hash', $hash)->exists()) {
            return response()->json(['status' => 'duplicate']);
        }

        // 🎯 Procesar texto
        $monto = null;
        $nombre = null;

        if (str_contains($raw, 'te envió un pago')) {

            preg_match('/S\/\s?(\d+(\.\d+)?)/', $raw, $montoMatch);
            $monto = $montoMatch[1] ?? null;

            preg_match('/Yape!\s(.+?)\ste envió/', $raw, $nombreMatch);
            $nombre = $nombreMatch[1] ?? null;
        }


        DB::beginTransaction();

        try {

            // 💾 Guardar notificación cruda

            // 💰 Crear registro financiero REAL
            if ($monto) {

                Yape::create([
                    'amount'    => $monto,
                    'comment'   => $raw, // mejor guardar raw_text aquí
                    'status'    => 'paid',
                    'date'      => now(),
                    'direction' => 'in',
                    'store_id'  => $store->id,
                ]);
            }

            DB::commit();

        } catch (\Exception $e) {

            DB::rollBack();

            return response()->json([
                'error' => 'Server error',
                'message' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'status' => 'stored',
            'amount' => $monto,
            'customer' => $nombre
        ]);
    }
}
