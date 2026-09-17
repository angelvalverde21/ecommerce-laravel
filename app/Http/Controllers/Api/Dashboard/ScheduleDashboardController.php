<?php

namespace App\Http\Controllers\Api\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\Store;
use Illuminate\Http\Request;

class ScheduleDashboardController extends Controller
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
    // public function update(Request $request, Store $store, $schedule_id)
    // {
    //     try {
    //         Log::info($request);
    //         //
    //         $validated = $request->validate([
    //             'work_type'         => ['required', 'in:home_office,onsite'],
    //             'day_of_week'       => ['nullable', 'integer', 'between:1,7'],
    //             'start_time' => ['nullable', 'date_format:H:i'],
    //             'end_time'   => ['required', 'date_format:H:i', 'after:start_time'],
    //             'allow_extra_hours' => ['required', 'boolean'],
    //             'comments' => ['nullable', 'string'],
    //         ]);

    //         $employee = $store->employees()->findOrFail($employee_id);

    //         $schedule = $employee->schedules()->create([
    //             ...$validated,
    //             'store_id' => $store->id,
    //         ]);

    //         return responseOk($schedule, "se ha creado correctamente el registro de horario");
    //     } catch (\Throwable $th) {
    //         Log::info($th);
    //     }
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Store $store)
    {
        //
    }
}
