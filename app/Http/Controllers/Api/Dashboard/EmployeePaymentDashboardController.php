<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Store;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class EmployeePaymentDashboardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Store $store, $employee_id)
    {

        $employee = $store->employees()
            ->with('user')
            ->findOrFail($employee_id);

        $payments = $employee->user
            ->payments()
            ->with(['images', 'gateway'])
            ->orderBy('date', 'desc')
            ->get()
            ->groupBy(function ($payment) {
                return Str::plural(
                    strtolower(class_basename($payment->paymentable_type)) . '_payments'
                );
            });

        return responseOk($payments, "Pagos del empleado obtenidos correctamente.");
    }

    public function search(Store $store, $employee_id, Request $request)
    {
        $validated = $request->validate([
            'search'     => 'nullable|string|max:255',
            'start_date' => 'nullable|date',
            'end_date'   => 'nullable|date|after_or_equal:start_date',
        ]);

        $search    = trim($validated['search'] ?? '');
        $startDate = $validated['start_date'] ?? null;
        $endDate   = $validated['end_date'] ?? null;

        $employee = $store->employees()
            ->with('user')
            ->findOrFail($employee_id);

        $query = $employee->user
            ->payments()
            ->with(['images', 'gateway']);

        // 👉 Filtro por búsqueda SOLO si hay texto
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('comment', 'like', "%{$search}%");
                // ->orWhere('description', 'like', "%{$search}%");
            });
        } else {
            Log::info("Búsqueda sin término, solo se aplicará filtro por fechas (si existe) para el store ID: {$store->id}");
        }

        // 👉 Filtro por fechas (independiente del search)
        if ($startDate && $endDate) {
            $query->whereBetween('created_at', [
                $startDate . ' 00:00:00',
                $endDate   . ' 23:59:59'
            ]);
        }

        $payments = $query->get()->groupBy(function ($payment) {
            return Str::plural(
                strtolower(class_basename($payment->paymentable_type)) . '_payments'
            );
        });

        return responseOk(
            $payments,
            "Pagos del empleado obtenidos correctamente"
        );
    }

    // return $employee->user
    //     ->payments()->with(
    //         [
    //             'images',
    //             'gateway',
    //             'paymentable' => function (MorphTo $morphTo) {
    //                 $morphTo->morphWith([
    //                     Purchase::class => ['items'],
    //                 ]);
    //             }
    //         ]
    //     )
    //     ->latest()
    //     ->paginate(15);

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
