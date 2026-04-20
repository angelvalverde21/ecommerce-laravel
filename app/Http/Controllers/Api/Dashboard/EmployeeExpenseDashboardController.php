<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;

class EmployeeExpenseDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $employee_id)
    {
        //
        $employee = $store->employees()->findOrFail($employee_id);

        return $employee->user
            ->payments()->with(
                [
                    'images',
                    'gateway',
                    'paymentable' => function (MorphTo $morphTo) {
                        $morphTo->morphWith([
                            Purchase::class => ['items'],
                        ]);
                    }
                ]
            )
            ->latest()
            ->paginate(15);}

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
