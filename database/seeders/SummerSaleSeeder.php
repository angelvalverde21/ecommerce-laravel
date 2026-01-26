<?php

namespace Database\Seeders;

use App\Models\ShopifyVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class SummerSaleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $json = File::get(database_path('data/summer_sale_new.json'));
        $data = json_decode($json, true);

        $i = 0;

        foreach ($data as $product) {

            // $i++;

            // // seguridad básica
            // if (!isset($product['id'], $product['price_sale'])) {
            //     continue;
            // }

            // DB::table('shopify_variants')
            //     ->where('shopify_product_id', (int) $product['id'])
            //     ->update([
            //         'price_sale' => $product['price_sale'],
            //         'updated_at' => now(),
            //     ]);
            // $this->command->info($product['id']);

            // if ($i == 10) {
            //     break;
            // }

            DB::table('shopify_variants')
                ->where('shopify_product_id', (int) $product['id'])
                ->update([
                    'price_sale' => $product['price_sale'],
                    'updated_at' => now(),
                ]);
        }


        // $this->command->info("Producto {$product['id']} → {$updated} variantes actualizadas");


    }
}
