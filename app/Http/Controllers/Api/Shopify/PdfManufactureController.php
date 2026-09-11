<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Manufacture;
use App\Models\Store;
use App\Services\Dashboard\Crud\ManufactureService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class PdfManufactureController extends Controller
{
    //
    protected $service;

    public function __construct(ManufactureService $manufactureService)
    {
        $this->service = $manufactureService;
    }

    //
    public function report(Store $store, $manufacture_id)
    {

        $pdf = app('dompdf.wrapper');


        $pdf->set_paper('A4', 'portrait'); // 283.465  puntos equivale a 10 cms y 510.236 equivale a 18cms

        $variants = $this->service->variants($store, $manufacture_id);

        $manufacture = Manufacture::with('supplier')->findOrFail($manufacture_id);

        Log::info($variants);
        Log::info($manufacture);

        $pdf = $pdf->loadview('pdf.variants', compact('variants', 'manufacture'));

        // $order->Addstatus('preparando_envio', $current);

        //return $pdf-> download ('prueba.pdf');
        return $pdf->stream(time() . '-' . $manufacture_id . '-reports.pdf');
    }
}
