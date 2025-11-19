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

        if ($order->shippingLines) {
            $courier = match_courier($order->shippingLines[0]->title, $keywords);

            $courier = $courier === "olva" ? "Olva Courier" : $courier;
            $courier = $courier === "confianza" ? "Courier de confianza" : $courier;

            Log::info($courier); // "olva courier"
        } else {
            $courier = "";
        }

        //return view('livewire.erp.orders.pdf.voucher', compact('order'));

        Log::info(json_encode($order));


        $pdf = $pdf->loadview('pdf.voucher', compact(['order', 'courier']));


        return $pdf->stream(time() . '-voucher-' . $order_id . '.pdf');

        // exit();

    }

    public function test()
    {

        $pdf = app('dompdf.wrapper');

        $pdf = $pdf->loadview('pdf.voucher');


        return $pdf->stream(time() . '-voucher-.pdf');

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
        return $pdf->download(time() . '-' . $order_id . '-packing.pdf');
    }
}
