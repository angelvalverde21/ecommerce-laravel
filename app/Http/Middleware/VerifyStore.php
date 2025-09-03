<?php

namespace App\Http\Middleware;

use App\Models\Store;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class VerifyStore
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        Log::info("VerifyStore");

        // Laravel ya resolvió {store} como modelo si usas type hint
        $base_path = $request->route('store'); // obtiene {store} de la URL
        Log::info($base_path);

        $store = Store::where('slug', $base_path)->first();

        Log::info($store);

        if (!$store) {
            return response()->json(['message' => 'La tienda "' . $base_path . '" no existe'], 404);
        }

        // opcional: guardar en request si quieres acceder siempre con $request->get('store')
        // $request->attributes->set('store', $store);

        return $next($request);
    }
}
