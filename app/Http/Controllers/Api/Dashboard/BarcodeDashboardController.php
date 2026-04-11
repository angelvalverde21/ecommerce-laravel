<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BarcodeDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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

    public function print(Store $store, Request $request)
    {

        Log::info($request);

        try {

            $variants = $request->validate([
                '*.id' => 'required|integer|exists:variants,id',
                '*.sku' => 'required|string',
                '*.quantity' => 'required|integer|min:1'
            ]);

            $variants = collect($variants)->map(function ($variant) {

                $model = Variant::with(['product', 'optionValues'])
                    ->findOrFail($variant['id']);

                // 👇 sobrescribes con el valor del request
                $model->quantity = $variant['quantity'];

                return $model;
            });

            $pdf = app('dompdf.wrapper');

            $pdf->set_paper([0, 0, 85.0393698, 56.6929132]); // 28.3464566  puntos equivale a 1 cms, por tanto 212.598425 es 7.5cms y 141.732283 es 5 cms

            $pdf = $pdf->loadview('pdf.barcode', compact(['variants']));

            return $pdf->stream(time() . '-barcode.pdf');
        } catch (\Throwable $th) {
            Log::info($th);
        }

        // exit();
    }

    /**
     * Display the specified resource.
     */
    public function show(Store $store)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Store $store)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Store $store)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
