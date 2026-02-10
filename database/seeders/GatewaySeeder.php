<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $gateways = [

            [
                'name' => 'cash',
                'title' => 'Efectivo',
                'sort_order' => 1,
                'store_id' => 1,
                'status' => '1',
            ],
            [
                'name' => 'yape',
                'title' => 'Yape',
                'sort_order' => 2,
                'store_id' => 1,
                'status' => '1',
            ],
            [
                'name' => 'plin',
                'title' => 'Plin',
                'sort_order' => 3,
                'store_id' => 1,
                'status' => '1',
            ],
             [
                'name' => 'credit_card',
                'title' => 'Tarjeta de credito',
                'sort_order' => 4,
                'store_id' => 1,
                'status' => '1',
            ],
             [
                'name' => 'bank_transfer',
                'title' => 'Transferencia bancaria',
                'sort_order' => 5,
                'store_id' => 1,
                'status' => '1',
            ],
             [
                'name' => 'paypal',
                'title' => 'PayPal',
                'sort_order' => 6,
                'store_id' => 1,
                'status' => '1',
            ]
        ];

        foreach ($gateways as $gateway) {
            \App\Models\Gateway::create($gateway);
        }
        
    }
}
