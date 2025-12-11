<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\ShopifyOrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfShopifyController extends Controller
{
    //
    protected $shopifyOrderService;

    public function __construct(ShopifyOrderService $shopifyOrderService)
    {
        $this->shopifyOrderService = $shopifyOrderService;
    }

    //
    public function voucher(Store $store, $order_id)
    {

        // Log::info($order);

        // dd('inicio pdf');

        $order_id = str_replace("#", "", $order_id);


        $order = $this->shopifyOrderService->getOrderByName($order_id);

        // Log::info($order);
        // Log::info(json_encode($order));

        $pdf = app('dompdf.wrapper');

        $pdf->set_paper([0, 0, 212.598425, 141.732283]); // 28.3464566  puntos equivale a 1 cms, por tanto 212.598425 es 7.5cms y 141.732283 es 5 cms

        // $order->changes()->create([
        //     'name'=>'print_vaucher',
        //     'content'=> []
        // ]);

        $keywords = ['shalom', 'dinsides', 'indriver', 'olva', 'olva courier', 'gama', 'gamma', 'express', 'showroom', 'confianza'];
        // $text = "ENVIOS GRATIS / OLVA COURIER ( POR COMPRAS MAYORES A S/299 )";

        Log::info(json_encode($order->lineItems));

        //Calculo del monto monto total

        /*
            {
                "name":"Vestido Bilbao Azul - Fit regular",
                "quantity":1,
                "originalUnitPriceSet":{
                    "shopMoney":{
                        "amount":"129.9",
                        "currencyCode":"PEN"
                    }
                },
                "variant":{
                    "id":"gid:\/\/shopify\/ProductVariant\/48379845050592",
                    "title":"Fit regular",
                    "price":"129.90",
                    "product":{
                        "title":"Vestido Bilbao Azul",
                        "featuredImage":{
                        "url":"https:\/\/cdn.shopify.com\/s\/files\/1\/0667\/7204\/1952\/files\/049A8C4A-A693-41A9-8792-773D488702CF.jpg?v=1765036710"
                        }
                    }
                }
            },
        */

        $sumPrice = 0;

        foreach ($order->lineItems as $item) {

            $compareAtPrice = $item->originalUnitPriceSet->shopMoney->amount;
            $price = $item->variant?->price;

            if($price != null){
                $sumPrice = $sumPrice + $price;
            }else{
                $sumPrice = $sumPrice + $compareAtPrice;
            }
        }
        
        Log::info($sumPrice);

        if ($order->shippingLines) {

            $courier = match_courier($order->shippingLines[0]->title, $keywords);

            $courier = $courier === "olva" ? "Olva Courier" : $courier;
            $courier = $courier === "confianza" ? "Courier de confianza" : $courier;

            if($sumPrice > 299){
                $courier = "Olva Courier";
            }

            // Log::info($courier); // "olva courier"
        } else {
            $courier = "";
        }

        //return view('livewire.erp.orders.pdf.voucher', compact('order'));


        $pdf = $pdf->loadview('pdf.voucher', compact(['order', 'courier']));


        return $pdf->stream(time() . '-voucher-' . $order_id . '.pdf');

        // exit();

    }

    public function packing(Store $store, $order_id)
    {

        $pdf = app('dompdf.wrapper');


        $pdf->set_paper('A4', 'portrait'); // 283.465  puntos equivale a 10 cms y 510.236 equivale a 18cms

        $order = $this->shopifyOrderService->getOrderByName($order_id);


        $pdf = $pdf->loadview('livewire.erp.components.orders.pdf.packing-label', compact('order'));

        // $order->Addstatus('preparando_envio', $current);

        //return $pdf-> download ('prueba.pdf');
        return $pdf->stream(time() . '-' . $order_id . '-packing.pdf');
    }
}
