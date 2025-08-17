<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
// use App\Models\Batch;
// use App\Models\Purchase;
// use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function getDashboard()
    {
        //

        // $totalPurchases = Purchase::sum('total');
        // $totalBatchs    = Batch::count();
        // $totalSuppliers = Supplier::count();

        //Mas rapida


        try {
            $stats = DB::table('purchases')
                ->selectRaw('(SELECT SUM(total) FROM purchases) as sum_total_purchases')
                ->selectRaw('(SELECT COUNT(*) FROM batches) as total_batches')
                ->selectRaw('(SELECT COUNT(*) FROM suppliers) as total_suppliers')
                ->first();

            // $arrayStats = [
            //     'totalPurchases' => $stats->totalPurchases,
            //     'totalBatchs'    => $stats->totalBatchs,
            //     'totalSuppliers' => $stats->totalSuppliers,
            // ];

            return responseOk($stats, "Se obtuvo correctamente los stats");

        } catch (\Throwable $e) {
            // Registrar el error en logs de Laravel
            // Log::error('Error obteniendo estadísticas del dashboard: ' . $e->getMessage());

            // Valores por defecto para que no falle la vista

            return responseError($e, "Ha ocurrido un error al obtener los datos");
        }
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
