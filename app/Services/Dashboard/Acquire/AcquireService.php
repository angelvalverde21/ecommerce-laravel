<?php

namespace App\Services\Dashboard\Acquire;

use App\Models\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AcquireService
{

    public function show(Store $store, int $acquire_id)
    {
        //
        $acquire = $store->acquires()->with(['variants.variant.product.image'])
            ->findOrFail($acquire_id);

        return $acquire;
    }

    public function index(Store $store)
    {
        return $store->acquires()
            ->with('supplier', 'user')
            ->get();
    }

    public function store(Store $store, Request $request)
    {
        //

        $data = $this->validate($request);

        $acquire = $store->acquires()->create(
            [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                // 'quantity_total' => $data['quantity_total'],
                'user_id' => Auth::id(),
                'supplier_id' => $data['supplier_id'],
                'date_start' => $data['date_start'],
                'date_end' => $data['date_end'],
            ]
        );

        return $acquire;
    }

    public function search(Store $store, Request $request, $search)
    {
        //
        if (trim($search) === '') {
            return $this->index($store);
        }

        $search = pluralToSingular($search);

        $result = $store->acquires()
            ->with(['user'])
            ->withSum('acquireVariants as sum_products', 'quantity')
            ->withSum('purchases as sum_purchases', 'total')
            ->search($search)->limit(10)->get();

        return $result;
        
        // $products = $store->products;

    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Store $store, Request $request, $acquire_id)
    {
        //
        $data = $this->validate($request);

        $acquire = $store->acquires()->findOrFail($acquire_id);

        $acquire->update($data);

        return $acquire;
    }

    public function validate(Request $request)
    {

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'quantity_total' => 'nullable|integer',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'date_start' => 'nullable|date',
            'date_end' => 'nullable|date|after_or_equal:date_start',
        ]);

        return $data;

    }
}