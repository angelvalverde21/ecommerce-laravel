<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ShopifyProduct;
use App\Models\ShopifyVariant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ShopifyVariantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //
        $json = File::get("database/data/shopify_variants.json");

        $data = json_decode($json);

        foreach ($data as $variant) {

            ShopifyVariant::updateOrCreate(
                [
                    'shopify_variant_id' => $variant->shopify_variant_id
                ],
                [
                    "price_etiqueta" => $variant->price_etiqueta ?? 0,
                    "price_oferta" => $variant->price_oferta ?? 0,
                    "price_sale" => $variant->price_sale ?? 0,
                    "price_wholesaler" => $variant->price_wholesaler ?? 0,
                    "price_live" => $variant->price_live ?? 0,
                    "price_blackfriday" => $variant->price_blackfriday ?? 0,
                    "price_feria" => $variant->price_feria ?? 0,
                ]
            );
        }
    }
}
