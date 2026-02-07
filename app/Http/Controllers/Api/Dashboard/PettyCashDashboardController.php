<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PettyCashDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Store $store)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Store $store, Request $request)
    {
        //
        $request->validate([
            'employe_id' => 'required|exists:employes,id',
            'amount_assigned' => 'required|numeric|min:0',
            'method' => 'required|in:cash,yape,plin,credit_card,bank_transfer,paypal',
        ]);

        $pettyCash = $store->pettyCashes()->create(
            [
                'employe_id' => $request->employe_id,
                'amount_assigned' => $request->amount_assigned,
                'balance' => $request->amount_assigned,
                'opened_at' => now(),
            ]
        );

        $pettyCash->payments()->create(
            [
                'store_id' => $store->id,
                'user_id' => Auth::id(),
                'amount' => $request->amount_assigned,
                'method' => $request->method,
                'status' => 'paid',
                'date' => now(),
                'comment' => 'Apertura de caja chica',
                'direction' => 'in',
            ]
        );
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
