<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Shopify\ShopifyReportService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;

class ReportShopifyController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(
        protected ShopifyReportService $shopifyService,
    ) {}

    public function monthAll(Store $store)
    {

        //El cache esta en el servicio
        $report = $this->shopifyService->getReportSalesByYearMonth();

        return response()->json($report);
    }


    public function reportCashWeekly(Store $store)
    {

        //El cache esta en el servicio
        $report = $this->shopifyService->reportCashWeekly();

        return response()->json($report);
    }

    public function reportBarDailys(Store $store, $days = 7)
    {
        $cacheKey = "report_bar_dailys_{$store->id}_{$days}";

        $report = Cache::remember($cacheKey, now()->addHours(24), function () use ($days) {
            return $this->shopifyService->getReportBarOrders($days);
        });

        return response()->json($report);
    }

    public function reportBarMonths(Store $store, $days = 7)
    {
        $report = $this->shopifyService->getReportBarMonths($days); //reporte en barras

        return response()->json($report);
    }


    public function reportTopSellingProducts(Store $store, Request $request)
    {

        // Días de duración del cache (por defecto 7)

        // Clave única por tienda + días
        $cacheKey = "report_top_selling_products_{$store->id}";

        $report = Cache::remember($cacheKey, now()->addHours(24), function () {
            return $this->shopifyService->getReportTopSellingProducts();
        });

        return response()->json($report);
    }

    public function inventory(Store $store, Request $request)
    {

        $cacheKey = "report_inventory_selling_products_{$store->id}";

        $report = Cache::remember($cacheKey, now()->addHours(24), function () {
            return $this->shopifyService->getReportTopSellingProducts();
        });

        return response()->json($report);

    }
}
