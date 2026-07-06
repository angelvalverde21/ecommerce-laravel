<?php

namespace App\Services;

use App\Models\Store;

class KardexService
{
    public function registerEntry($amount)
    {
        // Aquí iría la lógica para procesar un pago
        return "Registrar Kardex";
    }

    public function registerExit($amount)
    {
        // Aquí iría la lógica para procesar un pago
        return "Registrar Kardex";
    }

    private function index(Store $store, int $manufacture_id){
        
        return "Listado de Kardexes";
    }
}