<?php

namespace App\Http\Controllers\Api\public;

use App\Http\Controllers\Controller;
use App\Models\ShopifyVariant;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AndroidController extends Controller
{
    //
    public function price(Store $store, $variant_id){

        
        try {
            
            $variant = Variant::with(['product', 'kardexes',  'variant_option_values.optionValue'])->findOrFail($variant_id);

            $variant_option_value = $variant->variant_option_values->first();

            Log::info($variant_option_value);

            Log::info($variant);

            $product = $variant->product;

            $shopify_variant = ShopifyVariant::where('shopify_product_id', $product->id)->first();

            $product = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $shopify_variant->price_etiqueta ?? 0,
                'oferta' => $shopify_variant->price_oferta ?? 0,
                'size' =>  $variant_option_value->option_value->value ?? 0,
            ];
        
            return response()->json($product);
            
        } catch (\Throwable $th) {
            //throw $th;

            return "Error";

        }
        

    }
}
