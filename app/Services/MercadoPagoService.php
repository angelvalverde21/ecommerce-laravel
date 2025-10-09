<?php

namespace App\Services;

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
}