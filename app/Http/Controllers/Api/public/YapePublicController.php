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
        /*
        // 🔐 API KEY opcional
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
    
        // 🔐 Hash fuerte anti-duplicado
        $hash = hash('sha256', $raw . $request->timestamp);
    
        if (Yape::where('hash', $hash)->exists()) {
            Log::info('Yape duplicado detectado', ['hash' => $hash]);
            return response()->json(['status' => 'duplicate']);
        }
    
        /*
        |--------------------------------------------------------------------------
        | 🎯 PROCESAMIENTO INTELIGENTE DEL MENSAJE
        |--------------------------------------------------------------------------
        */
    
        $monto = null;
        $nombre = null;
        $codigoSeguridad = null;
    
        // ✅ 1️⃣ Extraer monto (funciona en ambos formatos)
        preg_match('/S\/\s?(\d+(?:\.\d+)?)/', $raw, $montoMatch);
        $monto = isset($montoMatch[1]) ? (float) $montoMatch[1] : null;
    
        // ✅ 2️⃣ Extraer nombre (funciona con o sin "Yape!")
        preg_match('/Pago(?:\sYape!)?\s(.+?)\ste envió/i', $raw, $nombreMatch);
        $nombre = $nombreMatch[1] ?? null;
    
        if ($nombre) {
            $nombre = trim(preg_replace('/\s+/', ' ', $nombre));
        }
    
        // ✅ 3️⃣ Extraer código de seguridad si existe
        preg_match('/c[oó]d\.\sde\sseguridad\s(?:es:)?\s?(\d+)/i', $raw, $codigoMatch);
        $codigoSeguridad = $codigoMatch[1] ?? null;
    
        // 🚫 Validación final del monto
        if (!$monto || $monto <= 0) {
            Log::warning('Yape sin monto válido', ['raw' => $raw]);
            return response()->json(['status' => 'ignored']);
        }
    
        try {
    
            DB::beginTransaction();
    
            Yape::create([
                'amount'        => $monto,
                'comment'       => $raw, // texto completo
                'hash'          => $hash,
                'reference_code' => $codigoSeguridad, // 👈 nuevo campo
                'status'        => 'paid',
                'date'          => now(),
                'direction'     => 'in',
                'store_id'      => $store->id,
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
            'status'        => 'stored',
            'amount'        => $monto,
            'customer'      => $nombre,
            'reference_code' => $codigoSeguridad,
        ]);
    }
}
