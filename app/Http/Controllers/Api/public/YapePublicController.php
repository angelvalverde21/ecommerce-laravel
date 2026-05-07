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
        // 🔐 Validar API KEY (si quieres activarlo)
        /*
        if ($request->header('X-API-KEY') !== config('app.yape_api_key')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        */

        Log::info('Yape raw request', $request->all());

        $request->validate([
            'raw_text'  => 'required|string',
            'timestamp' => 'required|numeric',
        ]);

        $raw = trim($request->raw_text);

        // 🔐 Generar hash fuerte
        $hash = hash('sha256', $raw . $request->timestamp);

        // 🚫 Evitar duplicados (a nivel financiero)
        if (Yape::where('hash', $hash)->exists()) {
            Log::info('Yape duplicado detectado', ['hash' => $hash]);
            return response()->json(['status' => 'duplicate']);
        }

        // 🎯 Procesar texto (más robusto)
        $monto = null;
        $nombre = null;

        // Validación mínima inteligente
        if (str_contains($raw, 'Yape!') && str_contains($raw, 'S/')) {

            // Extraer monto (más robusto)
            preg_match('/S\/\s?(\d+(?:\.\d+)?)/', $raw, $montoMatch);
            $monto = isset($montoMatch[1]) ? (float) $montoMatch[1] : null;

            // Extraer nombre
            preg_match('/Yape!\s(.+?)\ste envió/i', $raw, $nombreMatch);
            $nombre = $nombreMatch[1] ?? null;

            // Normalizar nombre
            if ($nombre) {
                $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
            }
        }

        if (!$monto || $monto <= 0) {
            Log::warning('Yape sin monto válido', ['raw' => $raw]);
            return response()->json(['status' => 'ignored']);
        }

        try {

            DB::beginTransaction();

            Yape::create([
                'amount'    => $monto,
                'comment'   => $raw,
                'hash'      => $hash,
                'status'    => 'paid',
                'date'      => now(),
                'direction' => 'in',
                'store_id'  => $store->id,
            ]);

            DB::commit();
        } catch (\Exception $e) {

            DB::rollBack();

            Log::error('Error guardando Yape', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'error' => 'Server error'
            ], 500);
        }

        return response()->json([
            'status'   => 'stored',
            'amount'   => $monto,
            'customer' => $nombre,
        ]);
    }
}
