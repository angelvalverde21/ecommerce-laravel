<?php

namespace App\Http\Controllers\Api\Pasarelas;

use App\Http\Controllers\Controller;
use App\Services\MercadoPagoService;
use Illuminate\Http\Request;

class MercadoPagoController extends Controller
{
    //
    protected $mpService;

    public function __construct(MercadoPagoService $mpService)
    {
        $this->mpService = $mpService;
    }

    public function createLink(Request $request)
    {
        $monto = $request->input('amount', 10); // valor por defecto 100
        $title = $request->input('title', 'Sorelle');

        $link = $this->mpService->crearLinkPago($monto, $title);

        return response()->json($link);
    }

    public function transactions(Request $request)
    {
        $result = $this->mpService->transactions(10);

        return response()->json($result);
    }
}
