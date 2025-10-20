<?php

namespace App\Http\Controllers\Api\Shopify;

use App\Http\Controllers\Controller;
use App\Services\ShopifyReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ReportShopifyController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function __construct(protected ShopifyReportService $shopifyService) {}

    // public function topProducts(Request $request)
    // {
    //     $from = $request->input('from', '2025-07-01T00:00:00Z');
    //     $to = $request->input('to', '2025-10-07T23:59:59Z');

    //     $data = $this->shopifyService->topSellingProducts($from, $to);

    //     return response()->json(['top_products' => $data]);
    // }

    public function index(Request $request)
    {
        $startDate = $request->query('start_date', now()->subMonth()->toDateString());
        $endDate = $request->query('end_date', now()->toDateString());

        $orders = $this->shopifyService->getOrdersBetween($startDate, $endDate);

        $formatted = collect($orders)->map(function ($order) {
            return [
                'order_id' => $order['id'],
                'order_name' => $order['name'],
                'date' => $order['date'],
                'total' => $order['total'],
                'currency' => $order['currency'],
                'items' => $order['items'],
            ];
        });

        return response()->json([
            'status' => 'success',
            'start_date' => $startDate,
            'end_date' => $endDate,
            'total_orders' => $formatted->count(),
            'orders' => $formatted->values(),
        ]);
    }

    public function topProducts(Request $request)
    {

        // Obtener fechas desde la query o usar valores por defecto
        $startDate = $request->query('start_date', now()->subMonths(5)->toDateString());
        $endDate   = $request->query('end_date', now()->toDateString());

        // Crear una clave de caché única basada en el rango de fechas
        $cacheKey = sprintf('top_products_%s_%s__a', $startDate, $endDate);

        // Recuperar del caché o generar y guardar por 7 días (168 horas)
        $products = Cache::remember(
            $cacheKey,
            now()->addDays(7),
            fn() => $this->shopifyService->getTopProductsBetween($startDate, $endDate)
        );

        // Construir respuesta JSON
        return response()->json([
            'status'         => 'success',
            'start_date'     => $startDate,
            'end_date'       => $endDate,
            'total_products' => count($products),
            'top_products'   => $products,
        ]);
    }

    public function dailyOrders(Request $request, $days = 14)
    {
        $report = $this->shopifyService->getDailyOrdersReport($days);

        return response()->json($report);
    }
    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
