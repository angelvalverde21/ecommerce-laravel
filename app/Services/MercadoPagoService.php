<?php

namespace App\Services;

use App\Models\Store;
use Illuminate\Support\Facades\Http;

class MercadoPagoService
{

    protected $accessToken;

    public function __construct()
    {
        $this->accessToken = config('mercadopago.token');
    }

    /**
     * Crear un link de pago con los datos mínimos
     *
     * @param float $monto
     * @param string $titulo
     * @return array
     */
    public function crearLinkPago(float $monto, string $titulo = 'Pago'): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $this->accessToken,
            'Content-Type' => 'application/json'
        ])->post('https://api.mercadopago.com/checkout/preferences', [
            'items' => [
                [
                    'title' => $titulo,
                    'quantity' => 1,
                    'currency_id' => 'PEN',
                    'unit_price' => $monto
                ]
            ]
        ]);

        if ($response->successful()) {
            $data = $response->json();
            return [
                'success' => true,
                'link_pago' => $data['init_point'] ?? null,
                'sandbox_link' => $data['sandbox_init_point'] ?? null
            ];
        }

        return [
            'success' => false,
            'error' => $response->body()
        ];
    }

    public function transactions($limit = 10)
    {

        $response = Http::withToken($this->accessToken)->get('https://api.mercadopago.com/v1/payments/search', [
            'sort'     => 'date_created',
            'criteria' => 'desc',  // más recientes primero
            'limit'    => $limit,
            'offset'   => 0,
        ]);

        if ($response->failed()) {
            return response()->json(['error' => 'Error al consultar MercadoPago', 'details' => $response->json()], 500);
        }

        $data = $response->json();

        return response()->json([
            'total'   => $data['paging']['total'] ?? 0,
            'results' => $data['results'] ?? [],
        ]);
    }

    public function transactionById(Store $store, $operationId)
    {
        $response = Http::withToken($this->accessToken)
            ->get("https://api.mercadopago.com/v1/payments/{$operationId}");

        if ($response->failed()) {
            return response()->json([
                'error'   => 'Error al consultar el número de operación',
                'details' => $response->json(),
            ], 500);
        }

        return response()->json($response->json());
    }
}
